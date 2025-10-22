<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EpSede extends Model
{
    use HasFactory;

    protected $table = 'ep_sede';

    protected $fillable = [
        'escuela_profesional_id',
        'sede_id',
        'vigente_desde',
        'vigente_hasta',
    ];

protected $casts = [
    'vigente_desde' => 'date',
    'vigente_hasta' => 'date',
];

    /* =====================
     | Relaciones
     |=====================*/
    public function escuelaProfesional()
    {
        return $this->belongsTo(EscuelaProfesional::class, 'escuela_profesional_id');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    public function expedientesAcademicos()
    {
        return $this->hasMany(ExpedienteAcademico::class, 'ep_sede_id');
    }

    public function proyectos()
    {
        return $this->hasMany(VmProyecto::class, 'ep_sede_id');
    }

    public function registrosHoras()
    {
        return $this->hasMany(RegistroHora::class, 'ep_sede_id');
    }

    // Eventos con alcance polimórfico (targetable)
    public function eventos()
    {
        return $this->morphMany(VmEvento::class, 'targetable');
    }

    /* =====================
     | Scopes / helpers
     |=====================*/
    /**
     * @property Carbon|null $vigente_desde
     * @property Carbon|null $vigente_hasta
     */
    public function scopeVigentes($query, $enFecha = null)
    {
        $enFecha = $enFecha ? Carbon::parse($enFecha) : Carbon::today();

        return $query
            ->where(function ($q) use ($enFecha) {
                $q->whereNull('vigente_desde')
                ->orWhereDate('vigente_desde', '<=', $enFecha->toDateString());
            })
            ->where(function ($q) use ($enFecha) {
                $q->whereNull('vigente_hasta')
                ->orWhereDate('vigente_hasta', '>=', $enFecha->toDateString());
            });
    }


    public function getEsVigenteAttribute(): bool
    {
        $hoy = Carbon::today();

        $desdeOk = is_null($this->vigente_desde)
            ? true
            : Carbon::parse($this->vigente_desde)->lte($hoy);

        $hastaOk = is_null($this->vigente_hasta)
            ? true
            : Carbon::parse($this->vigente_hasta)->gte($hoy);

        return $desdeOk && $hastaOk;
    }
}
