<?php

namespace App\Http\Resources\Academico;

use Illuminate\Http\Resources\Json\JsonResource;

class EpSedeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                       => $this->id,
            'escuela_profesional_id'   => $this->escuela_profesional_id,
            'sede_id'                  => $this->sede_id,
            'vigente_desde'            => optional($this->vigente_desde)->toDateString(),
            'vigente_hasta'            => optional($this->vigente_hasta)->toDateString(),
        ];
    }
}
