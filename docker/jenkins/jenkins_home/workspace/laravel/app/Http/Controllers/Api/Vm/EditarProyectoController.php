<?php

namespace App\Http\Controllers\Api\Vm;

use App\Http\Controllers\Controller;
use App\Http\Resources\Vm\VmProyectoResource;
use App\Http\Resources\Vm\VmProcesoResource;
use App\Models\VmProyecto;
use App\Services\Auth\EpScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class EditarProyectoController extends Controller
{
    /** GET /api/vm/proyectos/{proyecto}/edit */
    public function show(VmProyecto $proyecto): JsonResponse
    {
        $user = request()->user();
        if (!EpScopeService::userManagesEpSede($user->id, (int)$proyecto->ep_sede_id)) {
            return response()->json(['ok'=>false,'message'=>'No autorizado para esta EP_SEDE.'], 403);
        }

        // Cargamos procesos con sesiones (si necesitas imágenes agrega ->load('imagenes'))
        $proyecto->load(['procesos.sesiones']);

        return response()->json([
            'ok'   => true,
            'data' => [
                'proyecto' => new VmProyectoResource($proyecto),
                'procesos' => VmProcesoResource::collection($proyecto->procesos),
            ],
        ], 200);
    }

    /** PUT /api/vm/proyectos/{proyecto}
     *  Edita SOLO campos del proyecto.
     *  Permitido si: estado PLANIFICADO y no hay sesiones iniciadas.
     */
    public function update(Request $request, VmProyecto $proyecto): JsonResponse
    {
        $user = $request->user();
        if (!EpScopeService::userManagesEpSede($user->id, (int)$proyecto->ep_sede_id)) {
            return response()->json(['ok'=>false,'message'=>'No autorizado para esta EP_SEDE.'], 403);
        }

        if (!$this->editable($proyecto)) {
            return response()->json([
                'ok'=>false,
                'message'=>'El proyecto ya inició (o no está en PLANIFICADO). No se puede editar.',
            ], 409);
        }

        // Validación ligera para update (no cambiamos ep_sede_id ni periodo_id aquí)
        $data = $request->validate([
            'titulo'                     => ['sometimes','string','max:255'],
            'descripcion'                => ['sometimes','nullable','string'],
            'tipo'                       => ['sometimes','in:VINCULADO,LIBRE'],
            'modalidad'                  => ['sometimes','in:PRESENCIAL,VIRTUAL,MIXTA'],
            'horas_planificadas'         => ['sometimes','integer','min:1','max:32767'],
            'horas_minimas_participante' => ['sometimes','nullable','integer','min:0','max:32767'],

            // 👇 Permitir cambiar nivel mientras siga planificado y se respete la unicidad
            'nivel'                      => [
                'sometimes',
                'integer',
                'between:1,10',
                Rule::unique('vm_proyectos', 'nivel')
                    ->where(fn ($q) => $q
                        ->where('ep_sede_id', $proyecto->ep_sede_id)
                        ->where('periodo_id', $proyecto->periodo_id)
                    )
                    ->ignore($proyecto->id),
            ],
        ]);

        $proyecto->update($data);

        return response()->json(['ok'=>true,'data'=>new VmProyectoResource($proyecto->fresh())], 200);
    }

    /** DELETE /api/vm/proyectos/{proyecto} */
    public function destroy(VmProyecto $proyecto): JsonResponse
    {
        $user = request()->user();
        if (!EpScopeService::userManagesEpSede($user->id, (int)$proyecto->ep_sede_id)) {
            return response()->json(['ok'=>false,'message'=>'No autorizado para esta EP_SEDE.'], 403);
        }

        if (!$this->editable($proyecto)) {
            return response()->json([
                'ok'=>false,
                'message'=>'El proyecto ya inició (o no está en PLANIFICADO). No se puede eliminar.',
            ], 409);
        }

        $proyecto->delete();
        // 204 = No Content (conviene no enviar body)
        return response()->json(null, 204);
    }

    /** editable/eliminable si:
     *  - estado === PLANIFICADO
     *  - no hay sesiones pasadas o ya iniciadas hoy
     */
    private function editable(VmProyecto $proyecto): bool
    {
        if ($proyecto->estado !== 'PLANIFICADO') {
            return false;
        }

        $today = Carbon::today()->toDateString();
        $now   = Carbon::now()->format('H:i:s');

        $yaInicio = $proyecto->procesos()
            ->whereHas('sesiones', function ($q) use ($today, $now) {
                $q->whereDate('fecha', '<', $today)
                  ->orWhere(function ($qq) use ($today, $now) {
                      $qq->whereDate('fecha', $today)
                         ->where('hora_inicio', '<=', $now);
                  });
            })
            ->exists();

        return !$yaInicio;
    }
}
