<?php

namespace App\Http\Controllers\Api\Vm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vm\ProyectoStoreRequest;
use App\Http\Resources\Vm\VmProcesoResource;
use App\Http\Resources\Vm\VmProyectoResource;
use App\Models\ExpedienteAcademico;
use App\Models\PeriodoAcademico;
use App\Models\VmParticipacion;
use App\Models\VmProyecto;
use App\Services\Auth\EpScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ProyectoController extends Controller
{
    /** GET /api/vm/proyectos (gestión) */
    public function index(Request $request): JsonResponse
    {
        $user   = $request->user();
        $q      = trim((string) $request->get('q', ''));
        $nivel  = $request->filled('nivel') ? (int) $request->get('nivel') : null;
        $estado = $request->get('estado');
        $perId  = $request->get('periodo_id');
        $epId   = $request->get('ep_sede_id');

        // Nuevo: expand flags -> "expand=procesos,sesiones" o "expand=arbol"
        $expand = collect(
            array_filter(array_map('trim', explode(',', strtolower($request->query('expand', '')))))
        );
        $withTree   = $request->boolean('with_tree', false) || $expand->contains('arbol');
        $withProcs  = $withTree || $expand->contains('procesos');
        $withSess   = $withTree || $expand->contains('sesiones');

        $query = VmProyecto::query()
            ->with(['imagenes' => fn ($q2) => $q2->latest()->limit(5)])
            ->withCount('imagenes as imagenes_total');

        // Limita por EP_SEDE gestionadas por el usuario
        $ids = EpScopeService::epSedesIdsManagedBy((int) $user->id);
        if (!empty($ids)) {
            $query->whereIn('ep_sede_id', $ids);
        } else {
            // sin permisos: no devuelve nada
            $query->whereRaw('1=0');
        }

        if ($q !== '') {
            $query->where(fn($qq) => $qq
                ->where('titulo', 'like', "%{$q}%")
                ->orWhere('codigo', 'like', "%{$q}%")
            );
        }
        if (!is_null($nivel))   $query->where('nivel', $nivel);
        if (!empty($estado))    $query->where('estado', $estado);
        if (!empty($perId))     $query->where('periodo_id', $perId);
        if (!empty($epId))      $query->where('ep_sede_id', $epId);

        // Opcional: engancha árbol (procesos/sesiones) sólo si lo piden
        if ($withProcs) {
            $query->with(['procesos' => function ($q) {
                $q->orderBy('orden')->orderBy('id');
            }]);
        }
        if ($withSess) {
            $query->with(['procesos.sesiones' => function ($q) {
                $q->orderBy('fecha')->orderBy('hora_inicio');
            }]);
        }

        $page = $query->latest('id')->paginate(15);

        // Transform: si pidieron árbol, devuelve mismo shape que "show"
        $page->getCollection()->transform(function ($item) use ($request, $withProcs, $withSess) {
            $base = (new VmProyectoResource($item))->toArray($request);

            if ($withProcs || $withSess) {
                // Igual que "show": { proyecto, procesos: [VmProcesoResource...] }
                return [
                    'proyecto' => $base,
                    'procesos' => VmProcesoResource::collection($item->procesos)->toArray($request),
                ];
            }

            // Sin árbol: sólo el proyecto plano (ligero)
            return $base;
        });

        return response()->json(['ok' => true, 'data' => $page], 200);
    }


    public function show(VmProyecto $proyecto): JsonResponse
    {
        $user = request()->user();

        // 1) Debe pertenecer a la misma EP_SEDE del proyecto
        if (!EpScopeService::userBelongsToEpSede($user->id, (int) $proyecto->ep_sede_id)) {
            return response()->json(['ok'=>false,'message'=>'No perteneces a esta EP_SEDE.'], 403);
        }

        // 2) Si está en PLANIFICADO, solo staff o alumno inscrito lo ven
        $inscrito = $proyecto->participaciones()
            ->where('expediente_id', EpScopeService::expedienteId($user->id)) // ajusta si tu servicio expone ese método
            ->exists();

        if ($proyecto->estado === 'PLANIFICADO'
            && !$inscrito
            && !EpScopeService::userManagesEpSede($user->id, (int)$proyecto->ep_sede_id)) {
            return response()->json(['ok'=>false,'message'=>'Proyecto aún no publicado.'], 403);
        }

        $proyecto->load([
            'imagenes',
            'procesos' => fn($q) => $q->orderBy('orden')->orderBy('id'),
            'procesos.sesiones' => fn($q) => $q->orderBy('fecha')->orderBy('hora_inicio'),
        ]);

        return response()->json([
            'ok'   => true,
            'data' => [
                'proyecto' => new VmProyectoResource($proyecto),
                'procesos' => VmProcesoResource::collection($proyecto->procesos),
            ],
        ]);
    }

    /** GET /api/vm/proyectos/alumno (listado para estudiante) */
    public function indexAlumno(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1) Expediente del alumno (tolerante a nombre de columna)
        $expQuery = ExpedienteAcademico::query();
        if (Schema::hasColumn('expedientes_academicos', 'user_id')) {
            $expQuery->where('user_id', $user->id);
        } elseif (Schema::hasColumn('expedientes_academicos', 'usuario_id')) {
            $expQuery->where('usuario_id', $user->id);
        } else {
            $expQuery->orderByDesc('id');
        }
        $exp = $expQuery->first();

        if (!$exp) {
            return response()->json(['ok'=>false, 'message'=>'No se encontró expediente del alumno.'], 422);
        }
        $epSedeId = (int) $exp->ep_sede_id;

        // 2) Periodo principal (por query param o por fechas vigentes; si no, el más reciente)
        $periodo = $request->filled('periodo_id')
            ? PeriodoAcademico::find((int)$request->get('periodo_id'))
            : null;

        if (!$periodo) {
            $periodo = PeriodoAcademico::whereDate('fecha_inicio','<=', now())
                ->whereDate('fecha_fin','>=', now())->first()
                ?: PeriodoAcademico::orderByDesc('fecha_inicio')->first();
        }
        if (!$periodo) {
            return response()->json(['ok'=>false, 'message'=>'No hay periodos académicos definidos.'], 422);
        }

        // 3) Participaciones del alumno en proyectos de su EP_SEDE (JOIN directo)
        $parts = VmParticipacion::query()
            ->join('vm_proyectos as pr', 'pr.id', '=', 'vm_participaciones.participable_id')
            ->where('vm_participaciones.expediente_id', $exp->id)
            ->where('pr.ep_sede_id', $epSedeId)
            ->select(
                'vm_participaciones.*',
                'pr.id as proyecto_id',
                'pr.nivel as proyecto_nivel',
                'pr.tipo as proyecto_tipo',
                'pr.estado as proyecto_estado',
                'pr.periodo_id as proyecto_periodo_id'
            )
            ->get();

        $nivelesCompletados = [];
        $pendientes = [];
        $actualProyecto = null; // VmProyecto|null

        foreach ($parts as $p) {
            $projId   = (int) $p->proyecto_id;
            $nivel    = $p->proyecto_nivel !== null ? (int) $p->proyecto_nivel : null;
            $tipo     = strtoupper((string) $p->proyecto_tipo);
            $estadoP  = (string) $p->proyecto_estado;

            // compatibilidad con datos viejos
            if ($tipo === 'PROYECTO') $tipo = 'VINCULADO';
            if ($tipo !== 'VINCULADO') continue; // ignorar LIBRE en lógica de niveles

            if (strtoupper($p->estado) === 'FINALIZADO') {
                if ($nivel !== null) $nivelesCompletados[] = $nivel;
                continue;
            }

            $proj = VmProyecto::find($projId);
            if (!$proj) continue;

            $req = $this->minutosRequeridosProyecto($proj);
            $acc = $this->minutosValidadosProyecto($projId, (int)$exp->id);

            if ($acc >= $req) {
                if ($nivel !== null) $nivelesCompletados[] = $nivel;
            } else {
                $pendientes[] = [
                    'proyecto'       => (new VmProyectoResource($proj->loadMissing('imagenes')))->toArray($request),
                    'periodo'        => optional($proj->periodo)->codigo ?? $proj->periodo_id,
                    'requerido_min'  => $req,
                    'acumulado_min'  => $acc,
                    'faltan_min'     => max(0, $req - $acc),
                    'cerrado'        => in_array($estadoP, ['CERRADO','CANCELADO']),
                ];

                // Candidato a "actual": EN_CURSO, del periodo seleccionado y no cerrado
                if (!$actualProyecto
                    && $estadoP === 'EN_CURSO'
                    && (int)$p->proyecto_periodo_id === (int)$periodo->id
                ) {
                    $actualProyecto = $proj;
                }
            }
        }

        $nivelesCompletados = array_values(array_unique($nivelesCompletados));

        // 4) Nivel objetivo = menor nivel VINCULADO no completado (1..10)
        $nivelObjetivo = 1;
        while (in_array($nivelObjetivo, $nivelesCompletados, true)) {
            $nivelObjetivo++;
        }
        if ($nivelObjetivo > 10) $nivelObjetivo = 10;

        $tienePendienteVinculado = count($pendientes) > 0;

        // 5) Bases para listas (cargamos imágenes para evitar null en resource)
        $base = VmProyecto::query()
            ->where('ep_sede_id', $epSedeId)
            ->where('periodo_id', $periodo->id)
            ->whereIn('estado', ['PLANIFICADO','EN_CURSO'])
            ->with(['imagenes' => fn ($q) => $q->latest()->limit(5)])
            ->withCount('imagenes as imagenes_total');

        // a) VINCULADOS del nivel objetivo (solo si NO hay pendientes)
        $inscribibles = collect();
        if (!$tienePendienteVinculado) {
            $inscribibles = (clone $base)
                ->whereIn('tipo', ['VINCULADO','PROYECTO']) // compat
                ->where('nivel', $nivelObjetivo)
                ->whereDoesntHave('participaciones', fn($q) => $q->where('expediente_id', $exp->id))
                ->orderByDesc('id')
                ->get();
        }

        // b) LIBRES (siempre visibles)
        $libres = (clone $base)
            ->where('tipo', 'LIBRE')
            ->orderByDesc('id')
            ->get();

        // c) Faltantes (niveles superiores informativos)
        $faltantes = (clone $base)
            ->whereIn('tipo', ['VINCULADO','PROYECTO'])
            ->where('nivel', '>', $nivelObjetivo)
            ->orderBy('nivel')
            ->get();

        // 6) Transformación (imagenes cargadas)
        $toRes = fn($col) => $col->map(fn($p) => (new VmProyectoResource($p))->toArray($request));

        $resp = [
            'contexto' => [
                'ep_sede_id'               => $epSedeId,
                'periodo_id'               => $periodo->id,
                'periodo_codigo'           => $periodo->codigo ?? $periodo->id,
                'nivel_objetivo'           => $nivelObjetivo,
                'tiene_pendiente_vinculado'=> $tienePendienteVinculado,
            ],
            // Para que el frontend pueda mostrar directamente el actual EN_CURSO
            'actual'                 => $actualProyecto
                ? (new VmProyectoResource($actualProyecto->loadMissing('imagenes')))->toArray($request)
                : null,

            'pendientes'             => $pendientes,
            'inscribibles_prioridad' => $toRes($inscribibles),
            'libres'                 => $toRes($libres),
            'faltantes_informativos' => $toRes($faltantes),
        ];

        return response()->json(['ok'=>true, 'data'=>$resp], 200);
    }

    /** POST /api/vm/proyectos */
    public function store(ProyectoStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if (!EpScopeService::userManagesEpSede($user->id, (int)$data['ep_sede_id'])) {
            return response()->json(['ok'=>false,'message'=>'No autorizado para esta EP_SEDE.'], 403);
        }

        $codigo = $data['codigo'] ?: sprintf('PRJ-%s-EP%s-%s',
            now()->format('YmdHis'), $data['ep_sede_id'], $user->id
        );

        $tipo = strtoupper($data['tipo']);

        $proyecto = VmProyecto::create([
            'ep_sede_id'                 => $data['ep_sede_id'],
            'periodo_id'                 => $data['periodo_id'],
            'codigo'                     => $codigo,
            'titulo'                     => $data['titulo'],
            'descripcion'                => $data['descripcion'] ?? null,
            'tipo'                       => $tipo,
            'modalidad'                  => $data['modalidad'],
            'estado'                     => 'PLANIFICADO',
            'nivel'                      => in_array($tipo, ['VINCULADO','PROYECTO'], true) ? $data['nivel'] : null,
            'horas_planificadas'         => $data['horas_planificadas'],
            'horas_minimas_participante' => $data['horas_minimas_participante'] ?? null,
        ]);

        return response()->json(['ok'=>true,'data'=>new VmProyectoResource($proyecto)], 201);
    }

    /** PUT /api/vm/proyectos/{proyecto}/publicar */
    public function publicar(VmProyecto $proyecto): JsonResponse
    {
        $user = request()->user();

        if (!EpScopeService::userManagesEpSede($user->id, (int)$proyecto->ep_sede_id)) {
            return response()->json(['ok'=>false,'message'=>'No autorizado para esta EP_SEDE.'], 403);
        }

        if ($proyecto->procesos()->count() < 1) {
            return response()->json([
                'ok'=>false,
                'message'=>'Debe definir al menos 1 proceso antes de publicar el proyecto.',
            ], 422);
        }

        $proyecto->update(['estado' => 'EN_CURSO']);

        return response()->json(['ok'=>true, 'data'=>$proyecto->fresh()], 200);
    }

    /** GET /api/vm/proyectos/niveles-disponibles */
    public function nivelesDisponibles(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'ep_sede_id' => ['required','integer','exists:ep_sede,id'],
            'periodo_id' => ['required','integer','exists:periodos_academicos,id'],
            'exclude_proyecto_id' => ['nullable','integer','exists:vm_proyectos,id'],
        ]);
        if ($v->fails()) {
            return response()->json(['ok'=>false,'errors'=>$v->errors()], 422);
        }

        $ep = (int) $request->get('ep_sede_id');
        $per = (int) $request->get('periodo_id');
        $exclude = $request->get('exclude_proyecto_id');

        $user = $request->user();
        if (!EpScopeService::userManagesEpSede($user->id, $ep)) {
            return response()->json(['ok'=>false, 'message'=>'No autorizado para esta EP_SEDE.'], 403);
        }

        $ocupados = VmProyecto::query()
            ->where('ep_sede_id', $ep)
            ->where('periodo_id', $per)
            ->whereIn('tipo', ['VINCULADO','PROYECTO']) // solo niveles reales
            ->when($exclude, fn($q) => $q->where('id', '!=', $exclude))
            ->pluck('nivel')
            ->filter(fn ($n) => !is_null($n)) // descarta NULL (LIBRE)
            ->map(fn ($n) => (int) $n)
            ->all();

        $todos = range(1, 10);
        $disponibles = array_values(array_diff($todos, $ocupados));
        sort($disponibles);

        return response()->json(['ok'=>true, 'data'=>$disponibles], 200);
    }

    // ───────────────────────── helpers ─────────────────────────

    protected function minutosRequeridosProyecto(VmProyecto $proyecto): int
    {
        $h = $proyecto->horas_minimas_participante ?? $proyecto->horas_planificadas;
        return ((int)$h) * 60;
    }

    /**
     * Suma minutos validados de asistencias en sesiones de PROCESOS del proyecto.
     * JOINs directos → no depende de relaciones ni morphMap.
     */
    protected function minutosValidadosProyecto(int $proyectoId, int $expedienteId): int
    {
        $total = DB::table('vm_asistencias as a')
            ->join('vm_sesiones as s', 's.id', '=', 'a.sesion_id')
            ->join('vm_procesos as p', 'p.id', '=', 's.sessionable_id')
            ->where('a.estado', 'VALIDADO')
            ->where('a.expediente_id', $expedienteId)
            ->where('p.proyecto_id', $proyectoId)
            ->sum('a.minutos_validados');

        return (int) $total;
    }
}
