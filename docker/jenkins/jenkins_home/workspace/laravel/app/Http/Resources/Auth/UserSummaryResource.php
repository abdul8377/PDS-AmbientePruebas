<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class UserSummaryResource extends JsonResource
{
    public function toArray($request): array
    {
        // Requiere Spatie HasRoles en el modelo User
        $roles = $this->getRoleNames()->values();
        $perms = $this->getAllPermissions()->pluck('name')->values();

        return [
            'id'            => $this->id,
            'username'      => $this->username,
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'full_name'     => trim($this->first_name.' '.$this->last_name),
            'profile_photo' => $this->profile_photo,
            'roles'         => $roles,
            'rol_principal' => $roles->first(), // null si no tiene roles aún
            'permissions'   => $perms,
        ];
    }
}
