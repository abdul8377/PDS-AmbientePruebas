<?php

namespace App\Http\Resources\Vm;

use Illuminate\Http\Resources\Json\JsonResource;

class VmEventoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'      => $this->id,
            'codigo'  => $this->codigo,
            'titulo'  => $this->titulo,
            'periodo_id' => $this->periodo_id,
            'targetable_type' => $this->getRawOriginal('targetable_type'), // alias morph
            'targetable_id'   => $this->targetable_id,
            'fecha'       => $this->fecha?->toDateString(),
            'hora_inicio' => $this->hora_inicio,
            'hora_fin'    => $this->hora_fin,
            'requiere_inscripcion' => (bool) $this->requiere_inscripcion,
            'cupo_maximo'          => $this->cupo_maximo ? (int) $this->cupo_maximo : null,
            'estado'      => $this->estado,
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
