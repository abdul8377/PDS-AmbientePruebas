<?php

namespace App\Http\Controllers\Api\Academico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\SedeStoreRequest;
use App\Http\Resources\Academico\SedeResource;
use App\Models\Sede;
use App\Models\Universidad;
use Illuminate\Http\JsonResponse;

class SedeController extends Controller
{
    /** POST /api/academico/sedes  (Crea Sede para la única universidad) */
    public function store(SedeStoreRequest $request): JsonResponse
    {
        $uni = Universidad::unica();
        $data = $request->validated();

        // Enforce unicidad por (universidad_id, nombre)
        $exists = Sede::query()
            ->where('universidad_id', $uni->id)
            ->where('nombre', $data['nombre'])
            ->exists();

        if ($exists) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya existe una sede con ese nombre en la universidad.',
            ], 422);
        }

        $sede = Sede::create([
            'universidad_id' => $uni->id,
            'nombre'         => $data['nombre'],
            'es_principal'   => (bool) ($data['es_principal'] ?? false),
            'esta_suspendida'=> (bool) ($data['esta_suspendida'] ?? false),
        ]);

        // Si es_principal = true ⇒ desmarcar otras
        if ($sede->es_principal) {
            Sede::where('universidad_id', $uni->id)
                ->where('id', '!=', $sede->id)
                ->update(['es_principal' => false]);
        }

        return response()->json([
            'ok'   => true,
            'data' => new SedeResource($sede),
        ], 201);
    }
}
