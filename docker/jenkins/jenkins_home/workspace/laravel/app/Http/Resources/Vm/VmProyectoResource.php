<?php

namespace App\Http\Resources\Vm;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Vm\ImagenResource as VmImagenResource;

class VmProyectoResource extends JsonResource
{
    public function toArray($request): array
    {
        $firstImg = $this->relationLoaded('imagenes') ? $this->imagenes->first() : null;

        return [
            'id'         => $this->id,
            'codigo'     => $this->codigo,
            'titulo'     => $this->titulo,
            'tipo'       => $this->tipo,
            'modalidad'  => $this->modalidad,
            'estado'     => $this->estado,
            'descripcion'=> $this->descripcion, // 👈 AQUI

            // 👇 si es NULL en DB (LIBRE), retorna null; si no, int
            'nivel'      => $this->nivel !== null ? (int) $this->nivel : null,

            'ep_sede_id' => $this->ep_sede_id,
            'periodo_id' => $this->periodo_id,

            'horas_planificadas' => (int) $this->horas_planificadas,
            'horas_minimas_participante' =>
                $this->horas_minimas_participante !== null
                    ? (int) $this->horas_minimas_participante
                    : null,

            'created_at' => $this->created_at?->toDateTimeString(),

            // Portada e imágenes (seguros si no está cargada la relación)
            'cover_url'      => $firstImg ? $firstImg->url_publica : null,
            'imagenes'       => $this->when(
                $this->relationLoaded('imagenes'),
                fn () => VmImagenResource::collection($this->imagenes),
                [] // si no está cargada la relación, devolvemos []
            ),
            'imagenes_total' => $this->imagenes_total
                ?? ($this->relationLoaded('imagenes') ? $this->imagenes->count() : 0),
        ];
    }
}
