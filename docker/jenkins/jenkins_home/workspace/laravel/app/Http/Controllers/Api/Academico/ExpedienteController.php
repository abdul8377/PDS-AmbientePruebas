<?php

namespace App\Http\Controllers\Api\Academico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\ExpedienteStoreRequest;
use App\Models\ExpedienteAcademico;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ExpedienteController extends Controller
{
    /** POST /api/academico/expedientes  */
    public function store(ExpedienteStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['rol'] = $data['rol'] ?? 'ESTUDIANTE';
        $data['vigente_desde'] = $data['vigente_desde'] ?? now()->toDateString();

        // Evita duplicado por (user, ep_sede)
        $exists = ExpedienteAcademico::where('user_id', $data['user_id'])
            ->where('ep_sede_id', $data['ep_sede_id'])
            ->first();

        if ($exists) {
            // Si ya existe, actualiza datos y rol si envían explícito (tu regla define si permites cambiar rol)
            $exists->update([
                'codigo_estudiante'    => $data['codigo_estudiante'] ?? $exists->codigo_estudiante,
                'grupo'                => $data['grupo'] ?? $exists->grupo,
                'correo_institucional' => $data['correo_institucional'] ?? $exists->correo_institucional,
                'rol'                  => $data['rol'] ?? $exists->rol,
                'estado'               => 'ACTIVO',
                'vigente_desde'        => $exists->vigente_desde ?? $data['vigente_desde'],
                'vigente_hasta'        => null,
            ]);

            return response()->json(['ok'=>true, 'data'=>$exists->fresh()], 200);
        }

        $exp = ExpedienteAcademico::create([
            'user_id'              => $data['user_id'],
            'ep_sede_id'           => $data['ep_sede_id'],
            'codigo_estudiante'    => $data['codigo_estudiante'] ?? null,
            'grupo'                => $data['grupo'] ?? null,
            'correo_institucional' => $data['correo_institucional'] ?? null,
            'estado'               => 'ACTIVO',
            'rol'                  => $data['rol'],
            'vigente_desde'        => $data['vigente_desde'],
            'vigente_hasta'        => null,
        ]);

        // (opcional) asigna rol Spatie global
        /** @var User $u */
        $u = User::find($data['user_id']);
        if ($u && !$u->hasRole('estudiante')) $u->assignRole('estudiante');

        return response()->json(['ok'=>true, 'data'=>$exp], 201);
    }
}
