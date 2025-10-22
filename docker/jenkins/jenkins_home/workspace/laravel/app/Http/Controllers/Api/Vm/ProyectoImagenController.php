<?php

namespace App\Http\Controllers\Api\Vm;

use App\Http\Controllers\Controller;
use App\Http\Resources\Vm\ImagenResource;
use App\Models\Imagen;
use App\Models\VmProyecto;
use App\Services\Auth\EpScopeService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse; // 👈 tipo común

class ProyectoImagenController extends Controller
{
    /**
     * GET /api/vm/proyectos/{proyecto}/imagenes
     */
    public function index(VmProyecto $proyecto): JsonResponse
    {
        $user = request()->user();

        if (!EpScopeService::userManagesEpSede($user->id, (int) $proyecto->ep_sede_id)) {
            return response()->json(['ok' => false, 'message' => 'No autorizado para esta EP_SEDE.'], 403);
        }

        $imagenes = $proyecto->imagenes()->latest()->get();

        return response()->json([
            'ok'   => true,
            'data' => ImagenResource::collection($imagenes),
        ], 200);
    }

    /**
     * POST /api/vm/proyectos/{proyecto}/imagenes
     * Body (multipart/form-data): file: <imagen>
     */
    public function store(Request $request, VmProyecto $proyecto): JsonResponse
    {
        $user = $request->user();

        if (!EpScopeService::userManagesEpSede($user->id, (int) $proyecto->ep_sede_id)) {
            return response()->json(['ok' => false, 'message' => 'No autorizado para esta EP_SEDE.'], 403);
        }

        $request->validate([
            'file' => ['required', 'file', 'image', 'max:5120'], // 5MB
        ]);

        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            throw ValidationException::withMessages([
                'file' => ['El archivo no es válido.'],
            ]);
        }

        $disk = 'public';
        $path = $file->store("proyectos/{$proyecto->id}", $disk);

        /** @var FilesystemAdapter $fs */
        $fs = Storage::disk($disk);
        $absoluteUrl = $fs->url($path); // requiere storage:link y filesystems.php bien configurado

        $img = $proyecto->imagenes()->create([
            'disk'        => $disk,
            'path'        => $path,
            'url'         => $absoluteUrl, // guardamos la URL absoluta
            'titulo'      => null,
            'visibilidad' => 'PUBLICA',
            'subido_por'  => $user->id,
        ]);

        return response()->json([
            'ok'   => true,
            'data' => new ImagenResource($img->fresh()),
        ], 201);
    }

    /**
     * DELETE /api/vm/proyectos/{proyecto}/imagenes/{imagen}
     */
    public function destroy(VmProyecto $proyecto, Imagen $imagen): SymfonyResponse
    {
        $user = request()->user();

        if (!EpScopeService::userManagesEpSede($user->id, (int) $proyecto->ep_sede_id)) {
            return response()->json(['ok' => false, 'message' => 'No autorizado para esta EP_SEDE.'], 403);
        }

        if ($imagen->imageable_type !== VmProyecto::class || (int) $imagen->imageable_id !== (int) $proyecto->id) {
            return response()->json(['ok' => false, 'message' => 'Imagen no pertenece al proyecto.'], 404);
        }

        if ($imagen->disk && $imagen->path && Storage::disk($imagen->disk)->exists($imagen->path)) {
            Storage::disk($imagen->disk)->delete($imagen->path);
        }

        $imagen->delete();

        // 204 No Content
        return response()->noContent();

        // Alternativa si prefieres tipar como JsonResponse:
        // return response()->json(null, 204);
    }
}
