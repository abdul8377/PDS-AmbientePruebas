<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VmSesion extends Model
{
    use HasFactory;

    protected $table = 'vm_sesiones';

    protected $fillable = [
        'sessionable_id',
        'sessionable_type',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
    ];

    protected static function booted()
    {
        static::saved(function (self $s) {
            app(\App\Services\Vm\EstadoService::class)->recalcOwner($s->sessionable);
        });
        static::deleted(function (self $s) {
            app(\App\Services\Vm\EstadoService::class)->recalcOwner($s->sessionable);
        });
    }


    protected $casts = [
        'fecha' => 'date',
        // 'hora_inicio' y 'hora_fin' quedan como string (TIME en DB)
    ];



    /* =====================
     | Relaciones
     |=====================*/
    public function sessionable()
    {
        // VmProceso o VmEvento
        return $this->morphTo();
    }

    public function asistencias()
    {
        return $this->hasMany(VmAsistencia::class, 'sesion_id');
    }

    public function qrTokens()
    {
        return $this->hasMany(VmQrToken::class, 'sesion_id');
    }

    public function registrosHoras()
    {
        return $this->hasMany(RegistroHora::class, 'sesion_id');
    }

    /* =====================
     | Scopes útiles
     |=====================*/
    public function scopeEnFecha($q, $fecha)
    {
        return $q->whereDate('fecha', $fecha);
    }

    public function scopeEntreHoras($q, string $desde, string $hasta)
    {
        return $q->whereTime('hora_inicio', '>=', $desde)
                 ->whereTime('hora_fin', '<=', $hasta);
    }

    public function scopeEnCurso($q)
    {
        return $q->where('estado', 'EN_CURSO');
    }

    /* =====================
     | Helpers
     |=====================*/
    public function getDuracionMinutosAttribute(): ?int
    {
        if (!$this->hora_inicio || !$this->hora_fin) return null;
        $ini = Carbon::createFromFormat('H:i:s', $this->hora_inicio);
        $fin = Carbon::createFromFormat('H:i:s', $this->hora_fin);
        return $ini->diffInMinutes($fin, false);
    }
}
