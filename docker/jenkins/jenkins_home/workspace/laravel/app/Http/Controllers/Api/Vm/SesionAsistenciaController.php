<?php

namespace App\Http\Controllers\Api\Vm;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SesionAsistenciaController extends Controller
{
    // POST /api/vm/sesiones/{sesion}/qr
    public function abrirVentanaQr(Request $request, int $sesion): JsonResponse
    {
        $user = $request->user();

        // validar que la sesión exista y pertenezca a un proyecto de mis EP_SEDE
        $row = DB::table('vm_sesiones as s')
            ->join('vm_procesos as p', function ($j) {
                $j->on('p.id', '=', 's.sessionable_id')
                  ->where('s.sessionable_type', '=', \App\Models\VmProceso::class);
            })
            ->join('vm_proyectos as pr', 'pr.id', '=', 'p.proyecto_id')
            ->select('s.id','pr.ep_sede_id')
            ->where('s.id', $sesion)
            ->first();

        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Sesión no encontrada.'], 404);
        }

        // el usuario debe ser COORDINADOR/ENCARGADO en esa EP_SEDE
        $puede = DB::table('expedientes_academicos')
            ->where('user_id', $user->id)
            ->where('estado', 'ACTIVO')
            ->where('ep_sede_id', $row->ep_sede_id)
            ->whereIn('rol', ['COORDINADOR','ENCARGADO'])
            ->exists();

        if (!$puede) {
            return response()->json(['ok' => false, 'message' => 'No autorizado para abrir QR en esta sesión.'], 403);
        }

        $now = Carbon::now();
        $usable = $now;
        $expires = $now->copy()->addMinutes(30);

        $maxUsos = $request->integer('max_usos') ?: null;

        $token = Str::random(40);

        $id = DB::table('vm_qr_tokens')->insertGetId([
            'sesion_id'    => $sesion,
            'token'        => $token,
            'usable_from'  => $usable,
            'expires_at'   => $expires,
            'max_usos'     => $maxUsos,
            'usos'         => 0,
            'activo'       => 1,
            'creado_por'   => $user->id,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return response()->json([
            'ok' => true,
            'data' => [
                'token'       => $token,
                'usable_from' => $usable->format('Y-m-d H:i:s'),
                'expires_at'  => $expires->format('Y-m-d H:i:s'),
                'geo'         => null,
            ],
        ]);
    }
}
