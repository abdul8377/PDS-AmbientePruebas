<?php

namespace App\Http\Resources\Vm;

use Illuminate\Http\Resources\Json\JsonResource;

class VmProcesoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'proyecto_id' => $this->proyecto_id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'tipo_registro' => $this->tipo_registro,
            'horas_asignadas' => $this->horas_asignadas ? (int) $this->horas_asignadas : null,
            'nota_minima' => $this->nota_minima ? (int) $this->nota_minima : null,
            'requiere_asistencia' => (bool) $this->requiere_asistencia,
            'orden' => $this->orden ? (int) $this->orden : null,
            'estado' => $this->estado,
            'created_at' => $this->created_at?->toDateTimeString(),

            'sesiones' => VmSesionResource::collection($this->whenLoaded('sesiones')),
        ];
    }
}
