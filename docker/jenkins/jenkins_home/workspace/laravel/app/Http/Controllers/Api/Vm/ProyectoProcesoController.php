<?php

namespace App\Http\Controllers\Api\Vm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vm\ProcesoStoreRequest;
use App\Http\Resources\Vm\VmProcesoResource;
use App\Models\VmProyecto;
use App\Services\Auth\EpScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProyectoProcesoController extends Controller
{
    /** POST /api/vm/proyectos/{proyecto}/procesos */
    public function store(VmProyecto $proyecto, ProcesoStoreRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!EpScopeService::userManagesEpSede($user->id, (int)$proyecto->ep_sede_id)) {
            return response()->json(['ok'=>false,'message'=>'No autorizado para la EP_SEDE del proyecto.'], 403);
        }

        $data = $request->validated();

        if (empty($data['orden'])) {
            $max = (int) ($proyecto->procesos()->max('orden') ?? 0);
            $data['orden'] = $max + 1;
        }

        $proc = DB::transaction(fn () => $proyecto->procesos()->create($data));

        return response()->json(['ok'=>true, 'data'=>new VmProcesoResource($proc)], 201);
    }
}
