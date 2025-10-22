<?php

namespace App\Http\Resources\Academico;

use Illuminate\Http\Resources\Json\JsonResource;

class FacultadResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'codigo'         => $this->codigo,
            'nombre'         => $this->nombre,
            'universidad_id' => $this->universidad_id,
        ];
    }
}
