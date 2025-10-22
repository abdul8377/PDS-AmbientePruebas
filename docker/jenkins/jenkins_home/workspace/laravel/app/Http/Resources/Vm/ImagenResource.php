<?php

namespace App\Http\Resources\Vm;

use Illuminate\Http\Resources\Json\JsonResource;

class ImagenResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'url'         => $this->url_publica,
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
