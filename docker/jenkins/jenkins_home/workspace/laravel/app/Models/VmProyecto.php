<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class VmProyecto extends Model
{
    use HasFactory;

    protected $table = 'vm_proyectos';

    protected $fillable = [
        'ep_sede_id',
        'periodo_id',
        'codigo',
        'titulo',
        'descripcion',
        'tipo',
        'modalidad',
        'estado',
        'nivel', // 👈 nuevo (1..10)
        'horas_planificadas',
        'horas_minimas_participante',
    ];

    protected $casts = [
        'nivel'                      => 'integer',
        'horas_planificadas'         => 'integer',
        'horas_minimas_participante' => 'integer',
    ];

    /* =====================
     | Relaciones
     |=====================*/

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo */
    public function epSede()
    {
        return $this->belongsTo(EpSede::class, 'ep_sede_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo */
    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany */
    public function procesos()
    {
        return $this->hasMany(VmProceso::class, 'proyecto_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\MorphMany */
    public function participaciones()
    {
        return $this->morphMany(VmParticipacion::class, 'participable');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\MorphMany */
    public function certificados()
    {
        return $this->morphMany(Certificado::class, 'certificable');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\MorphMany */
    public function imagenes()
    {
        return $this->morphMany(Imagen::class, 'imageable');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\MorphMany */
    public function registrosHoras()
    {
        return $this->morphMany(RegistroHora::class, 'vinculable');
    }

    /* =====================
     | Hooks
     |=====================*/

    protected static function booted()
    {
        static::deleting(function (self $proyecto) {
            // Si tu FK vm_procesos.proyecto_id tiene onDelete('cascade'),
            // puedes quitar este bloque y dejar al motor borrar en cascada.
            $proyecto->loadMissing('procesos');
            foreach ($proyecto->procesos as $proceso) {
                $proceso->delete(); // permitirá disparar eventos en los hijos
            }
        });
    }

    /* =====================
     | Scopes útiles
     |=====================*/

    /** @return \Illuminate\Database\Eloquent\Builder */
    public function scopeEnCurso($q)
    {
        return $q->where('estado', 'EN_CURSO');
    }

    /** @return \Illuminate\Database\Eloquent\Builder */
    public function scopePlanificados($q)
    {
        return $q->where('estado', 'PLANIFICADO');
    }

    /** @return \Illuminate\Database\Eloquent\Builder */
    public function scopeDelPeriodo($q, int $periodoId)
    {
        return $q->where('periodo_id', $periodoId);
    }

    /** @return \Illuminate\Database\Eloquent\Builder */
    public function scopeDeEpSede($q, int $epSedeId)
    {
        return $q->where('ep_sede_id', $epSedeId);
    }

    /** @return \Illuminate\Database\Eloquent\Builder */
    public function scopeDelNivel($q, int $nivel)
    {
        return $q->where('nivel', $nivel);
    }

    /* =====================
     | Reglas de negocio
     |=====================*/

    /**
     * Indica si el proyecto puede ser editado/eliminado.
     * Reglas:
     *  - estado === PLANIFICADO
     *  - no existe ninguna sesión pasada o ya iniciada hoy (hora_inicio <= ahora).
     */
    public function isEditable(): bool
    {
        if ($this->estado !== 'PLANIFICADO') {
            return false;
        }

        $today = Carbon::today()->toDateString();
        $now   = Carbon::now()->format('H:i:s');

        $yaInicio = $this->procesos()
            ->whereHas('sesiones', function ($q) use ($today, $now) {
                $q->whereDate('fecha', '<', $today)
                  ->orWhere(function ($qq) use ($today, $now) {
                      $qq->whereDate('fecha', $today)
                         ->where('hora_inicio', '<=', $now);
                  });
            })
            ->exists();

        return !$yaInicio;
    }
}
