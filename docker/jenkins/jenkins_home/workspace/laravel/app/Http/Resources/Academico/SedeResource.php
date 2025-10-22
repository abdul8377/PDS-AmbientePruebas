<?php

namespace App\Http\Resources\Academico;

use Illuminate\Http\Resources\Json\JsonResource;

class SedeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'nombre'          => $this->nombre,
            'es_principal'    => (bool) $this->es_principal,
            'esta_suspendida' => (bool) $this->esta_suspendida,
            'universidad_id'  => $this->universidad_id,
        ];
    }
}
