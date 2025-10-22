<?php

namespace App\Http\Resources\Universidad;

use Illuminate\Http\Resources\Json\JsonResource;

class UniversidadResource extends JsonResource
{
    public function toArray($request): array
    {
        $logo    = $this->relationLoaded('logo') ? $this->logo : null;
        $portada = $this->relationLoaded('portada') ? $this->portada : null;

        return [
            'id'                    => $this->id,
            'codigo'                => $this->codigo,
            'nombre'                => $this->nombre,
            'tipo_gestion'          => $this->tipo_gestion,
            'estado_licenciamiento' => $this->estado_licenciamiento,

            'logo' => $logo ? [
                'id'    => $logo->id,
                'titulo'=> $logo->titulo,
                'url'   => $logo->url_publica, // usa el accessor de tu modelo Imagen
                'disk'  => $logo->disk,
                'path'  => $logo->path,
            ] : null,

            'portada' => $portada ? [
                'id'    => $portada->id,
                'titulo'=> $portada->titulo,
                'url'   => $portada->url_publica,
                'disk'  => $portada->disk,
                'path'  => $portada->path,
            ] : null,
        ];
    }
}
