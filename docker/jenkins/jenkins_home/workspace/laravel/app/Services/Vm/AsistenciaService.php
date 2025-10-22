<?php

namespace App\Services\Vm;

use App\Models\User;
use App\Models\VmSesion;
use App\Models\VmProceso;
use App\Models\VmEvento;
use App\Models\VmProyecto;
use App\Models\VmQrToken;
use App\Models\VmAsistencia;
use App\Models\VmParticipacion;
use App\Models\RegistroHora;
use App\Models\ExpedienteAcademico;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AsistenciaService
{
    public const WINDOW_MINUTES = 30;

    // =========================
    // TOKENS
    // =========================
    public function generarToken(
        VmSesion $sesion,
        string $tipo = 'QR',
        ?array $geo = null,
        ?int $maxUsos = null,
        ?int $creadoPor = null
    ): VmQrToken {
        $now = now();

        return DB::transaction(function () use ($sesion, $tipo, $geo, $maxUsos, $creadoPor, $now) {
            $t = new VmQrToken();
            $t->forceFill([
                'sesion_id'   => $sesion->id,
                'token'       => bin2hex(random_bytes(16)), // 32 chars hex
                'tipo'        => $tipo,                     // QR | MANUAL
                'usable_from' => $now,
                'expires_at'  => $now->copy()->addMinutes(self::WINDOW_MINUTES),
                'max_usos'    => $maxUsos,
                'usos'        => 0,
                'activo'      => true,
                'creado_por'  => $creadoPor,
                'lat'         => $geo['lat']    ?? null,
                'lng'         => $geo['lng']    ?? null,
                'radio_m'     => $geo['radio_m']?? null,
                'meta'        => null,
            ])->save();

            return $t->refresh();
        });
    }

    /** Token MANUAL alineado a la sesión (±1h respecto al horario de la sesión). */
    public function generarTokenManualAlineado(VmSesion $sesion, ?int $creadoPor = null): VmQrToken
    {
        [$start, $end] = $this->timeWindowForSesion($sesion);

        return DB::transaction(function () use ($sesion, $creadoPor, $start, $end) {
            $t = new VmQrToken();
            $t->forceFill([
                'sesion_id'   => $sesion->id,
                'token'       => bin2hex(random_bytes(16)),
                'tipo'        => 'MANUAL',
                'usable_from' => $start,
                'expires_at'  => $end,
                'max_usos'    => null,
                'usos'        => 0,
                'activo'      => true,
                'creado_por'  => $creadoPor,
            ])->save();
            return $t->refresh();
        });
    }

    public function checkVentana(VmQrToken $t): void
    {
        $now = now();
        if (!$t->activo || ($t->usable_from && $now->lt($t->usable_from)) || ($t->expires_at && $now->gt($t->expires_at))) {
            throw ValidationException::withMessages(['token' => 'VENTANA_INVALIDA']);
        }
        if (!is_null($t->max_usos) && $t->usos >= $t->max_usos) {
            throw ValidationException::withMessages(['token' => 'VENTANA_SIN_CUPO']);
        }
    }

    public function checkGeofence(?VmQrToken $t, ?float $lat, ?float $lng): void
    {
        if (!$t || !$t->lat || !$t->lng || !$t->radio_m) return;
        if ($lat === null || $lng === null) {
            throw ValidationException::withMessages(['geo' => 'GEO_REQUERIDA']);
        }
        $dist = $this->haversine((float)$t->lat, (float)$t->lng, (float)$lat, (float)$lng);
        if ($dist > (int)$t->radio_m) {
            throw ValidationException::withMessages(['geo' => 'FUERA_DE_RANGO']);
        }
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $R = 6371000; // m
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)**2;
        return (int) round(2 * $R * atan2(sqrt($a), sqrt(1-$a)));
    }

    // =========================
    // UTILIDADES FECHAS
    // =========================
    public function minutosSesion(VmSesion $sesion): int
    {
        if ($sesion->hora_inicio && $sesion->hora_fin) {
            $ini = Carbon::createFromFormat('H:i:s', $sesion->hora_inicio);
            $fin = Carbon::createFromFormat('H:i:s', $sesion->hora_fin);
            return max(0, $ini->diffInMinutes($fin, false));
        }
        return 0;
    }

    /** Devuelve [inicio-1h, fin+1h] (si hay horas); si no, todo el día. */
    public function timeWindowForSesion(VmSesion $sesion): array
    {
        $fecha = $sesion->fecha ? Carbon::parse($sesion->fecha)->toDateString() : now()->toDateString();

        $norm = static function (?string $t): ?string {
            if (!$t) return null;
            if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t.':00';
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) return $t;
            try { return Carbon::parse($t)->format('H:i:s'); } catch (\Throwable) { return null; }
        };

        $hi = $norm($sesion->hora_inicio);
        $hf = $norm($sesion->hora_fin);

        if ($hi && $hf) {
            return [Carbon::parse("$fecha $hi")->subHour(), Carbon::parse("$fecha $hf")->addHour()];
        }
        if ($hi) {
            $b = Carbon::parse("$fecha $hi");
            return [$b->copy()->subHour(), $b->copy()->addHours(2)];
        }
        if ($hf) {
            $b = Carbon::parse("$fecha $hf");
            return [$b->copy()->subHours(2), $b->copy()->addHour()];
        }
        return [Carbon::parse("$fecha 00:00:00"), Carbon::parse("$fecha 23:59:59")];
    }

    // =========================
    // RESOLUCIONES ESTRUCTURALES ROBUSTAS
    // =========================
    /** [ptype, pid, ep_sede_id, periodo_id] detectados a partir de la sesión. */
    protected function datosDesdeSesion(VmSesion $sesion): array
    {
        // 1) Intento normal vía morphTo
        $owner = $sesion->sessionable; // VmProceso | VmEvento | null

        if ($owner instanceof VmProceso) {
            $owner->loadMissing('proyecto');
            $p = $owner->proyecto;
            if ($p) return [VmProyecto::class, (int)$p->id, (int)$p->ep_sede_id, (int)$p->periodo_id];
        }

        if ($owner instanceof VmEvento) {
            return [VmEvento::class, (int)$owner->id, (int)$owner->ep_sede_id, (int)$owner->periodo_id];
        }

        // 2) Fallback robusto si el morph no resolvió (aliases viejos en DB)
        $type = strtolower((string) $sesion->sessionable_type);
        $sid  = (int) $sesion->sessionable_id;

        if (str_contains($type, 'proceso')) {
            $row = DB::table('vm_procesos as pr')
                ->join('vm_proyectos as p', 'p.id', '=', 'pr.proyecto_id')
                ->select('p.id as pid','p.ep_sede_id','p.periodo_id')
                ->where('pr.id', $sid)->first();
            if ($row) return [VmProyecto::class, (int)$row->pid, (int)$row->ep_sede_id, (int)$row->periodo_id];
        }

        if (str_contains($type, 'evento')) {
            $row = DB::table('vm_eventos')->select('id','ep_sede_id','periodo_id')->where('id', $sid)->first();
            if ($row) return [VmEvento::class, (int)$row->id, (int)$row->ep_sede_id, (int)$row->periodo_id];
        }

        return [null, null, null, null];
    }

    /** Sólo el ep_sede_id (o null si no se puede resolver). */
    public function epSedeIdDesdeSesion(VmSesion $sesion): ?int
    {
        [, , $ep] = $this->datosDesdeSesion($sesion);
        return $ep;
    }

    /** sessionable → participable (Proyecto para sesiones de Proceso; Evento para sesiones de Evento). */
    public function participableDesdeSesion(VmSesion $sesion): array
    {
        [$ptype, $pid] = $this->datosDesdeSesion($sesion);
        if ($ptype === VmProyecto::class) return [VmProyecto::class, (int)$pid];
        if ($ptype === VmEvento::class)   return [VmEvento::class,   (int)$pid];
        return [null, null];
    }

    // =========================
    // EXPEDIENTES
    // =========================
    public function resolverExpedientePorUser(User $user, ?int $epSedeId): ?ExpedienteAcademico
    {
        if (!$epSedeId) return null;
        return ExpedienteAcademico::where('user_id', $user->id)->where('ep_sede_id', $epSedeId)->first();
    }

    public function resolverExpedientePorIdentificador(string $dniOCodigo, ?int $epSedeId): ?ExpedienteAcademico
    {
        if (!$epSedeId) return null;

        return ExpedienteAcademico::query()
            ->where('ep_sede_id', $epSedeId)
            ->where(function ($q) use ($dniOCodigo) {
                $q->where('codigo_estudiante', $dniOCodigo)
                  ->orWhereHas('user', fn($qq) => $qq->where('doc_numero', $dniOCodigo));
            })
            ->first();
    }

    // =========================
    // ASISTENCIAS
    // =========================
    public function upsertAsistencia(
        VmSesion $sesion,
        ExpedienteAcademico $exp,
        string $metodo,
        ?VmQrToken $token = null,
        ?array $meta = null
    ): VmAsistencia {
        return DB::transaction(function () use ($sesion, $exp, $metodo, $token, $meta) {
            /** @var VmAsistencia $a */
            $a = VmAsistencia::firstOrNew([
                'sesion_id'     => $sesion->id,
                'expediente_id' => $exp->id,
            ]);

            if (!$a->exists || !$a->check_in_at) {
                $a->check_in_at = now();
            }

            // Participación a nivel Proyecto/Evento
            [$ptype, $pid] = $this->participableDesdeSesion($sesion);
            if ($ptype && $pid) {
                $a->participacion_id = VmParticipacion::where([
                    'participable_type' => $ptype,
                    'participable_id'   => $pid,
                    'expediente_id'     => $exp->id,
                ])->value('id');
            }

            $a->metodo            = $metodo; // 'QR' | 'MANUAL' | ...
            $a->qr_token_id       = $token?->id;
            $a->estado            = $a->estado ?: 'PENDIENTE';
            $a->minutos_validados = $a->minutos_validados ?? 0;

            $prev = is_array($a->meta) ? $a->meta : [];
            $a->meta = array_merge($prev, $meta ?? []);
            $a->save();

            if ($token) {
                $token->increment('usos');
            }

            return $a->refresh();
        });
    }

    public function validarAsistencia(VmAsistencia $a, int $minutos, bool $crearRegistroHoras = true): VmAsistencia
    {
        $a->estado = 'VALIDADO';
        $a->minutos_validados = $minutos;
        $a->check_out_at = $a->check_out_at ?? now();
        $a->save();

        if ($crearRegistroHoras && $minutos > 0) {
            $this->crearOActualizarRegistroHora($a, $minutos);
        }
        return $a;
    }

    protected function crearOActualizarRegistroHora(VmAsistencia $a, int $minutos): void
    {
        $sesion = $a->sesion;
        [$ptype, $pid, $epSedeId, $periodoId] = $this->datosDesdeSesion($sesion);
        if (!$ptype || !$pid || !$epSedeId || !$periodoId) return;

        $reg = RegistroHora::firstOrNew(['asistencia_id' => $a->id]);

        $reg->fill([
            'expediente_id'  => $a->expediente_id,
            'ep_sede_id'     => $epSedeId,
            'periodo_id'     => $periodoId,
            'fecha'          => $sesion->fecha,
            'minutos'        => $minutos,
            'actividad'      => 'Asistencia sesión '.$sesion->id,
            'estado'         => 'APROBADO',
            'vinculable_type'=> $ptype,
            'vinculable_id'  => $pid,
            'sesion_id'      => $sesion->id,
        ]);

        if ($reg->exists) {
            $reg->minutos = $minutos;
            $reg->estado  = 'APROBADO';
        }
        $reg->save();
    }
}
