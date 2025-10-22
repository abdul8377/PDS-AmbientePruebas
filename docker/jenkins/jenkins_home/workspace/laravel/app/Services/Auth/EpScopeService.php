<?php

namespace App\Services\Auth;

use App\Models\ExpedienteAcademico;
use Illuminate\Support\Facades\Schema;


class EpScopeService
{
    public static function userManagesEpSede(int $userId, int $epSedeId): bool
    {
        return ExpedienteAcademico::query()
            ->where('user_id', $userId)
            ->where('ep_sede_id', $epSedeId)
            ->where('estado', 'ACTIVO')
            ->whereIn('rol', ['COORDINADOR','ENCARGADO'])
            ->exists();
    }

    public static function userManagesSede(int $userId, int $sedeId): bool
    {
        return ExpedienteAcademico::query()
            ->where('user_id', $userId)
            ->where('estado', 'ACTIVO')
            ->whereIn('rol', ['COORDINADOR','ENCARGADO'])
            ->whereHas('epSede', fn($q) => $q->where('sede_id', $sedeId))
            ->exists();
    }

    public static function userManagesFacultad(int $userId, int $facultadId): bool
    {
        return ExpedienteAcademico::query()
            ->where('user_id', $userId)
            ->where('estado', 'ACTIVO')
            ->whereIn('rol', ['COORDINADOR','ENCARGADO'])
            ->whereHas('epSede.escuelaProfesional', fn($q) => $q->where('facultad_id', $facultadId))
            ->exists();
    }

    /** 👇 NUEVO: devuelve los ep_sede_id que administra el usuario */
    public static function epSedesIdsManagedBy(int $userId): array
    {
        return ExpedienteAcademico::query()
            ->where('user_id', $userId)
            ->where('estado', 'ACTIVO')
            ->whereIn('rol', ['COORDINADOR','ENCARGADO'])
            ->pluck('ep_sede_id')
            ->unique()
            ->values()
            ->all();
    }

    public static function expedienteId(int $userId): ?int
    {
        $q = ExpedienteAcademico::query()
            ->select('id')
            ->where('estado', 'ACTIVO');

        if (Schema::hasColumn('expedientes_academicos', 'user_id')) {
            $q->where('user_id', $userId);
        } else {
            $q->where('usuario_id', $userId);
        }

        return optional($q->latest('id')->first())->id;
    }

    public static function userBelongsToEpSede(int $userId, int $epSedeId): bool
    {
        $q = ExpedienteAcademico::query()
            ->where('ep_sede_id', $epSedeId)
            ->where('estado', 'ACTIVO');

        // tolera user_id vs usuario_id
        if (Schema::hasColumn('expedientes_academicos', 'user_id')) {
            $q->where('user_id', $userId);
        } else {
            $q->where('usuario_id', $userId);
        }

        return $q->exists();
    }
}
