<?php

namespace App\Http\Controllers\Api\Academico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\AsignarResponsableRequest;
use App\Models\ExpedienteAcademico;
use App\Models\EpSede;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ResponsableEpController extends Controller
{
    /** POST /api/academico/ep-sede/{epSede}/coordinador */
    public function setCoordinador(EpSede $epSede, AsignarResponsableRequest $request): JsonResponse
    {
        return $this->assign($epSede, $request->validated(), 'COORDINADOR', 'coordinador');
    }

    /** POST /api/academico/ep-sede/{epSede}/encargado */
    public function setEncargado(EpSede $epSede, AsignarResponsableRequest $request): JsonResponse
    {
        return $this->assign($epSede, $request->validated(), 'ENCARGADO', 'encargado');
    }

    /* ===================== Helper ===================== */

    private function assign(EpSede $epSede, array $data, string $rol, string $spatieRole): JsonResponse
    {
        $user = User::findOrFail($data['user_id']);

        $record = DB::transaction(function () use ($epSede, $data, $rol, $user, $spatieRole) {
            // 1) "Dar de baja" al responsable actual de ese ROL en esa EP
            ExpedienteAcademico::where('ep_sede_id', $epSede->id)
                ->where('rol', $rol)
                ->where('estado', 'ACTIVO')
                ->update([
                    'estado'        => 'CESADO',
                    'vigente_hasta' => now()->toDateString(),
                ]);

            // 2) Upsert del nuevo responsable (una fila por (user, ep))
            $exp = ExpedienteAcademico::firstOrNew([
                'user_id'    => $user->id,
                'ep_sede_id' => $epSede->id,
            ]);

            $exp->fill([
                'rol'           => $rol,
                'estado'        => 'ACTIVO',
                'vigente_desde' => $data['vigente_desde'] ?? now()->toDateString(),
                'vigente_hasta' => $data['vigente_hasta'] ?? null,
            ]);

            // Campos de alumno pueden quedar NULL para staff
            if (!$exp->exists) {
                $exp->codigo_estudiante    = null;
                $exp->grupo                = null;
                $exp->correo_institucional = null;
            }

            $exp->save();

            // 3) Rol Spatie global de apoyo a policies/middlewares
            if (!$user->hasRole($spatieRole)) {
                $user->assignRole($spatieRole);
            }

            return $exp;
        });

        return response()->json([
            'ok'   => true,
            'data' => $record->fresh(),
        ]);
    }
}
