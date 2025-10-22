<?php

namespace App\Http\Controllers\Api\Academico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academico\EpSedeStoreRequest;
use App\Http\Resources\Academico\EpSedeResource;
use App\Models\EpSede;
use App\Models\EscuelaProfesional;
use App\Models\Sede;
use Illuminate\Http\JsonResponse;

class EpSedeController extends Controller
{
    /** POST /api/academico/ep-sede  (vincula Escuela Profesional ↔ Sede) */
    public function store(EpSedeStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $escuela = EscuelaProfesional::with('facultad:id,universidad_id')
            ->findOrFail($data['escuela_profesional_id']);

        $sede = Sede::select('id','universidad_id','nombre')->findOrFail($data['sede_id']);

        // Regla de negocio clave:
        // La sede y la escuela (vía facultad) deben pertenecer a la MISMA universidad
        if ($sede->universidad_id !== $escuela->facultad->universidad_id) {
            return response()->json([
                'ok' => false,
                'message' => 'La sede y la escuela pertenecen a universidades distintas.',
            ], 422);
        }

        // Evitar duplicados (UK a nivel BD ya existe; esto es preventivo)
        $exists = EpSede::where('escuela_profesional_id', $escuela->id)
            ->where('sede_id', $sede->id)
            ->first();

        if ($exists) {
            // 409 para indicar recurso ya vinculado
            return response()->json([
                'ok'   => true,
                'data' => new EpSedeResource($exists),
                'message' => 'La relación ya existía.',
            ], 409);
        }

        $rel = EpSede::create([
            'escuela_profesional_id' => $escuela->id,
            'sede_id'                => $sede->id,
            'vigente_desde'          => $data['vigente_desde'] ?? null,
            'vigente_hasta'          => $data['vigente_hasta'] ?? null,
        ]);

        return response()->json([
            'ok'   => true,
            'data' => new EpSedeResource($rel),
        ], 201);
    }

    /** DELETE /api/academico/ep-sede/{id}  (desvincular) */
    public function destroy(int $id): JsonResponse
    {
        $rel = EpSede::findOrFail($id);
        $rel->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Vinculación eliminada.',
        ]);
    }
}
