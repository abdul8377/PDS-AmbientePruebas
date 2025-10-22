<?php

namespace App\Http\Controllers\Api\Academico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\EscuelaProfesionalStoreRequest;
use App\Http\Resources\Academico\EscuelaProfesionalResource;
use App\Models\EscuelaProfesional;
use App\Models\Facultad;
use App\Models\Universidad;
use Illuminate\Http\JsonResponse;

class EscuelaProfesionalController extends Controller
{
    /** POST /api/academico/escuelas  (Crea Escuela Profesional) */
    public function store(EscuelaProfesionalStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $facu = Facultad::findOrFail($data['facultad_id']);

        // (Sanidad) La facultad debe pertenecer a la única universidad
        $uni = Universidad::unica();
        if ($facu->universidad_id !== $uni->id) {
            return response()->json(['ok'=>false,'message'=>'La facultad no pertenece a la universidad del sistema.'], 422);
        }

        // Unicidad por facultad
        $dupCodigo = EscuelaProfesional::where('facultad_id', $facu->id)
            ->where('codigo', $data['codigo'])
            ->exists();
        if ($dupCodigo) {
            return response()->json(['ok'=>false,'message'=>'Código ya existe en la facultad.'], 422);
        }

        $dupNombre = EscuelaProfesional::where('facultad_id', $facu->id)
            ->where('nombre', $data['nombre'])
            ->exists();
        if ($dupNombre) {
            return response()->json(['ok'=>false,'message'=>'Nombre ya existe en la facultad.'], 422);
        }

        $ep = EscuelaProfesional::create([
            'facultad_id' => $facu->id,
            'codigo'      => $data['codigo'],
            'nombre'      => $data['nombre'],
        ]);

        return response()->json([
            'ok'   => true,
            'data' => new EscuelaProfesionalResource($ep),
        ], 201);
    }
}
