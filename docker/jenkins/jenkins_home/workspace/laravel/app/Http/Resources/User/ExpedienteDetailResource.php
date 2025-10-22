<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpedienteDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        // IMPORTANTE: usar relationLoaded() en vez de whenLoaded() para evitar MissingValue
        $ep        = $this->relationLoaded('epSede') ? $this->epSede : null;
        $escuela   = $ep?->relationLoaded('escuelaProfesional') ? $ep->escuelaProfesional : $ep?->escuelaProfesional;
        $facultad  = $escuela?->relationLoaded('facultad') ? $escuela->facultad : $escuela?->facultad;
        $universidad = $facultad?->relationLoaded('universidad') ? $facultad->universidad : $facultad?->universidad;
        $sede      = $ep?->relationLoaded('sede') ? $ep->sede : $ep?->sede;

        return [
            'id'                   => $this->id,
            'estado'               => $this->estado,
            'codigo_estudiante'    => $this->codigo_estudiante,
            'grupo'                => $this->grupo,
            'correo_institucional' => $this->correo_institucional,

            'ep_sede' => [
                'id'            => $ep?->id,
                'vigente_desde' => optional($ep?->vigente_desde)->toDateString(),
                'vigente_hasta' => optional($ep?->vigente_hasta)->toDateString(),
            ],

            'sede' => $sede ? [
                'id'              => $sede->id,
                'nombre'          => $sede->nombre,
                'es_principal'    => (bool) $sede->es_principal,
                'esta_suspendida' => (bool) $sede->esta_suspendida,
            ] : null,

            'escuela_profesional' => $escuela ? [
                'id'     => $escuela->id,
                'codigo' => $escuela->codigo,
                'nombre' => $escuela->nombre,
            ] : null,

            'facultad' => $facultad ? [
                'id'     => $facultad->id,
                'codigo' => $facultad->codigo,
                'nombre' => $facultad->nombre,
            ] : null,

            'universidad' => $universidad ? [
                'id'                    => $universidad->id,
                'codigo'                => $universidad->codigo,
                'nombre'                => $universidad->nombre,
                'tipo_gestion'          => $universidad->tipo_gestion,
                'estado_licenciamiento' => $universidad->estado_licenciamiento,
            ] : null,

            // Aquí sí conviene whenLoaded() porque va dentro del array y no lo dereferenciamos antes
            'matriculas' => $this->whenLoaded('matriculas', function () {
                return $this->matriculas->map(function ($m) {
                    return [
                        'id'                => $m->id,
                        'ciclo'             => $m->ciclo,
                        'grupo'             => $m->grupo,
                        'modalidad_estudio' => $m->modalidad_estudio,
                        'modo_contrato'     => $m->modo_contrato,
                        'fecha_matricula'   => optional($m->fecha_matricula)->toDateString(),
                        'periodo'           => $m->relationLoaded('periodo') && $m->periodo ? [
                            'id'           => $m->periodo->id,
                            'codigo'       => $m->periodo->codigo,
                            'anio'         => $m->periodo->anio,
                            'ciclo'        => $m->periodo->ciclo,
                            'estado'       => $m->periodo->estado,
                            'es_actual'    => (bool) $m->periodo->es_actual,
                            'fecha_inicio' => $m->periodo->fecha_inicio->toDateString(),
                            'fecha_fin'    => $m->periodo->fecha_fin->toDateString(),
                        ] : null,
                    ];
                });
            }),
        ];
    }
}
