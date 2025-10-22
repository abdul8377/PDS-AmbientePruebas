<?php

namespace App\Http\Controllers\Api\Universidad;

use App\Http\Controllers\Controller;
use App\Http\Resources\Universidad\UniversidadResource;
use App\Models\Universidad;
use App\Models\Imagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UniversidadController extends Controller
{
    /**
     * GET /api/universidad
     * Retorna la única universidad con logo y portada.
     */
    public function show()
    {
        $uni = $this->getSingleton()
            ->load(['logo', 'portada']); // relaciones morphOne con filtro por 'titulo'

        return response()->json([
            'ok' => true,
            'data' => new UniversidadResource($uni),
        ]);
    }

    /**
     * PUT /api/universidad
     * Actualiza datos básicos de la única universidad.
     */
    public function update(Request $request)
    {
        $uni = $this->getSingleton();

        $data = $request->validate([
            'codigo'               => ['required','string','max:255', Rule::unique('universidades','codigo')->ignore($uni->id)],
            'nombre'               => ['required','string','max:255'],
            'tipo_gestion'         => ['required', Rule::in(\App\Models\Universidad::TIPO_GESTION)],
            'estado_licenciamiento'=> ['required', Rule::in(\App\Models\Universidad::ESTADO_LICENCIAMIENTO)],
        ]);

        $uni->update($data);

        return response()->json([
            'ok'   => true,
            'data' => new UniversidadResource($uni->fresh(['logo','portada'])),
        ]);
    }

    /**
     * POST /api/universidad/logo
     * Sube/reemplaza el LOGO. Mantiene el anterior, pero lo vuelve "RESTRINGIDA".
     * Body: file (multipart/form-data)
     */
    public function setLogo(Request $request)
    {
        $request->validate([
            'file' => ['required','image','max:4096'], // ~4MB
        ]);

        return $this->storeImagenCategoria($request, 'LOGO');
    }

    /**
     * POST /api/universidad/portada
     * Sube/reemplaza la PORTADA. Mantiene el anterior como RESTRINGIDA.
     * Body: file (multipart/form-data)
     */
    public function setPortada(Request $request)
    {
        $request->validate([
            'file' => ['required','image','max:8192'], // portada puede ser más pesada
        ]);

        return $this->storeImagenCategoria($request, 'PORTADA');
    }

    /* ===========================
     | Helpers internos
     |===========================*/

    private function getSingleton(): Universidad
    {
        // Si no existe, crea un registro base.
        return Universidad::query()->firstOrCreate([], [
            'codigo' => 'UNI-001',
            'nombre' => 'Universidad',
            'tipo_gestion' => 'PUBLICO',
            'estado_licenciamiento' => 'NINGUNO',
        ]);
    }

    private function storeImagenCategoria(Request $request, string $categoria)
    {
        $uni  = $this->getSingleton();
        $user = $request->user(); // para subido_por

        $file = $request->file('file');
        $disk = config('filesystems.default', 'public');
        $ext  = strtolower($file->getClientOriginalExtension() ?: 'jpg');

        $path = $file->storeAs(
            "universidad/{$categoria}",
            "{$categoria}_" . now()->format('Ymd_His') . ".{$ext}",
            $disk
        );

        // “Archivar” imágenes previas de esa categoría (no se borran)
        $uni->imagenes()
            ->where('titulo', $categoria)
            ->update(['visibilidad' => 'RESTRINGIDA']);

        // Crear nueva imagen
        /** @var Imagen $img */
        $img = $uni->imagenes()->create([
            'disk'        => $disk,
            'path'        => $path,
            'url'         => null, // si usas S3 público puedes setear la URL completa aquí
            'titulo'      => $categoria, // ← usamos 'LOGO' / 'PORTADA'
            'visibilidad' => 'PUBLICA',
            'subido_por'  => $user?->id,
        ]);

        // Respuesta
        $uni->load(['logo','portada']);

        return response()->json([
            'ok'   => true,
            'data' => new UniversidadResource($uni),
        ]);
    }
}
