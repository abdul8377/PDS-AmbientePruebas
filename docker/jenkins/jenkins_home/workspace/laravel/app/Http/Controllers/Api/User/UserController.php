<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserDetailResource;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * GET /api/users/me
     * Perfil completo del autenticado (roles, permisos, expedientes, sede, escuela, facultad, universidad, etc.)
     */
    public function me(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'No autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $this->cargarRelaciones($user);

        return response()->json([
            'ok'   => true,
            'user' => new UserDetailResource($user),
        ]);
    }

    /**
     * GET /api/users/by-username/{username}
     * Busca por username y devuelve toda la información vinculada.
     */
    public function showByUsername(string $username)
    {
        /** @var \App\Models\User $user */
        $user = User::query()
            ->where('username', $username)
            ->firstOrFail();

        $this->cargarRelaciones($user);

        return response()->json([
            'ok'   => true,
            'user' => new UserDetailResource($user),
        ]);
    }

    /**
     * Eager load de todo lo necesario para evitar N+1.
     */
    private function cargarRelaciones(User $user): void
    {
        $user->load([
            'expedientesAcademicos' => function ($q) {
                $q->with([
                    'epSede' => function ($qq) {
                        $qq->with([
                            'sede:id,nombre,es_principal,esta_suspendida',
                            'escuelaProfesional' => function ($qp) {
                                $qp->select('id','facultad_id','codigo','nombre')
                                   ->with(['facultad' => function ($qf) {
                                       $qf->select('id','universidad_id','codigo','nombre')
                                          ->with(['universidad:id,codigo,nombre,tipo_gestion,estado_licenciamiento']);
                                   }]);
                            },
                        ]);
                    },
                    'matriculas' => function ($qm) {
                        $qm->with([
                            'periodo:id,codigo,anio,ciclo,estado,es_actual,fecha_inicio,fecha_fin'
                        ])->orderByDesc('id');
                    },
                ])->orderByDesc('id');
            },
        ]);
    }
}
