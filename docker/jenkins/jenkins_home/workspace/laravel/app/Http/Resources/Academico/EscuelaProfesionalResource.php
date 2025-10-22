<?php

namespace App\Http\Resources\Academico;

use Illuminate\Http\Resources\Json\JsonResource;

class EscuelaProfesionalResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'codigo'       => $this->codigo,
            'nombre'       => $this->nombre,
            'facultad_id'  => $this->facultad_id,
        ];
    }
}
