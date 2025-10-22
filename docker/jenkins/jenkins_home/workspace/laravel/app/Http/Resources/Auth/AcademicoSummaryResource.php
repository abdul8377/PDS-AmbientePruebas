<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class AcademicoSummaryResource extends JsonResource
{
    public function toArray($request): array
    {
        $ep = $this->epSede;
        return [
            'escuela_profesional' => $ep?->escuelaProfesional?->nombre,
            'sede'                => $ep?->sede?->nombre,
            'expediente_id'       => $this->id,
        ];
    }
}
