<?php

namespace App\Http\Resources\Vm;

use Illuminate\Http\Resources\Json\JsonResource;

class VmSesionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sessionable_type' => $this->getRawOriginal('sessionable_type'),
            'sessionable_id'   => $this->sessionable_id,
            'fecha'       => $this->fecha?->toDateString(),
            'hora_inicio' => $this->hora_inicio,
            'hora_fin'    => $this->hora_fin,
            'estado'      => $this->estado,
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
