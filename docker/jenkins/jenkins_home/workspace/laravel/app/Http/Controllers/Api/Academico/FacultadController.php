<?php

namespace App\Http\Controllers\Api\Academico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\FacultadStoreRequest;
use App\Http\Resources\Academico\FacultadResource;
use App\Models\Facultad;
use App\Models\Universidad;
use Illuminate\Http\JsonResponse;

class FacultadController extends Controller
{
    /** POST /api/academico/facultades (Crea Facultad en la única universidad) */
    public function store(FacultadStoreRequest $request): JsonResponse
    {
        $uni  = Universidad::unica();
        $data = $request->validated();

        // Unicidad por universidad
        $dupCodigo = Facultad::where('universidad_id', $uni->id)
            ->where('codigo', $data['codigo'])
            ->exists();
        if ($dupCodigo) {
            return response()->json(['ok'=>false,'message'=>'Código ya existe en la universidad.'], 422);
        }

        $dupNombre = Facultad::where('universidad_id', $uni->id)
            ->where('nombre', $data['nombre'])
            ->exists();
        if ($dupNombre) {
            return response()->json(['ok'=>false,'message'=>'Nombre ya existe en la universidad.'], 422);
        }

        $facu = Facultad::create([
            'universidad_id' => $uni->id,
            'codigo'         => $data['codigo'],
            'nombre'         => $data['nombre'],
        ]);

        return response()->json([
            'ok'   => true,
            'data' => new FacultadResource($facu),
        ], 201);
    }
}
