<?php

namespace App\Http\Controllers\Api\Login;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LookupRequest;
use App\Http\Resources\Auth\UserSummaryResource;
use App\Http\Resources\Auth\AcademicoSummaryResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Paso 1: LOOKUP por username
     * GET/POST /api/auth/lookup
     */
    public function lookup(LookupRequest $request)
    {
        $username = $request->validated()['username'];

        $user = User::query()
            ->where('username', $username)
            ->first();

        if (!$user) {
            return response()->json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        $expediente = $user->expedientesAcademicos()
            ->with(['epSede.escuelaProfesional:id,nombre', 'epSede.sede:id,nombre'])
            ->where('estado', 'ACTIVO')
            ->latest('id')
            ->first();

        return response()->json([
            'ok'        => true,
            'user'      => new UserSummaryResource($user),
            'academico' => $expediente ? new AcademicoSummaryResource($expediente) : null,
        ]);
    }

    /**
     * Paso 2: LOGIN con password
     * POST /api/auth/login
     */
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::query()->where('username', $data['username'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'credentials' => ['Credenciales inválidas.'],
            ]);
        }

        // Token (Sanctum)
        $token = $user->createToken('api')->plainTextToken;

        $expediente = $user->expedientesAcademicos()
            ->with(['epSede.escuelaProfesional:id,nombre', 'epSede.sede:id,nombre'])
            ->where('estado', 'ACTIVO')
            ->latest('id')
            ->first();

        return response()->json([
            'ok'    => true,
            'token' => $token,
            'user'  => new UserSummaryResource($user),
            'academico' => $expediente ? new AcademicoSummaryResource($expediente) : null,
        ]);
    }

    /**
     * LOGOUT: revoca el token actual
     * POST /api/auth/logout (Authorization: Bearer <token>)
     */
    public function logout()
    {
        request()->user()?->currentAccessToken()?->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Sesión cerrada.',
        ]);
    }
}
