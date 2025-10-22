<?php

namespace App\Http\Controllers\Api\Vm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vm\SesionBatchRequest;
use App\Http\Resources\Vm\VmSesionResource;
use App\Models\VmProceso;
use App\Services\Auth\EpScopeService;
use App\Services\Vm\SesionBatchService;
use App\Support\DateList;
use Illuminate\Http\JsonResponse;

class ProcesoSesionController extends Controller
{
    /** POST /api/vm/procesos/{proceso}/sesiones/batch */
    public function storeBatch(VmProceso $proceso, SesionBatchRequest $request): JsonResponse
    {
        $user = $request->user();

        $proyecto = $proceso->proyecto()->with('periodo')->firstOrFail();
        if (!EpScopeService::userManagesEpSede($user->id, (int)$proyecto->ep_sede_id)) {
            return response()->json(['ok'=>false,'message'=>'No autorizado para la EP_SEDE del proceso.'], 403);
        }

        // Fechas dentro del período del proyecto
        $fechas = DateList::fromBatchPayload($request->validated());
        $ini = $proyecto->periodo->fecha_inicio->toDateString();
        $fin = $proyecto->periodo->fecha_fin->toDateString();

        $fuera = $fechas->filter(fn($f) => !($ini <= $f && $f <= $fin))->values();
        if ($fuera->isNotEmpty()) {
            return response()->json([
                'ok'=>false,
                'message'=>'Hay fechas fuera del período del proyecto.',
                'rango'=>[$ini,$fin],
                'fechas_fuera'=>$fuera,
            ], 422);
        }

        $created = SesionBatchService::createFor($proceso, $request->validated());

        return response()->json(['ok'=>true,'data'=>VmSesionResource::collection($created)], 201);
    }
}
