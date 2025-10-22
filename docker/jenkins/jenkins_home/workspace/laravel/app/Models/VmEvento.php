<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VmEvento extends Model
{
    use HasFactory;

    protected $table = 'vm_eventos';

    protected $fillable = [
        'periodo_id',
        'targetable_id',
        'targetable_type',
        'codigo',
        'titulo',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
        'requiere_inscripcion',
        'cupo_maximo',
    ];

    protected $casts = [
        'fecha'                => 'date',
        'requiere_inscripcion' => 'boolean',
        'cupo_maximo'          => 'integer',
    ];

    /* =====================
     | Relaciones
     |=====================*/
    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
    }

    // Alcance polimórfico (Sede/Facultad/EpSede)
    public function targetable()
    {
        return $this->morphTo();
    }

    // Sesiones polimórficas (el evento "tiene" sesiones)
    public function sesiones()
    {
        return $this->morphMany(VmSesion::class, 'sessionable');
    }

    // Participaciones polimórficas
    public function participaciones()
    {
        return $this->morphMany(VmParticipacion::class, 'participable');
    }

    // Certificados polimórficos
    public function certificados()
    {
        return $this->morphMany(Certificado::class, 'certificable');
    }

    // Imágenes polimórficas
    public function imagenes()
    {
        return $this->morphMany(Imagen::class, 'imageable');
    }

    // Registro de horas (si lo vinculas como 'vinculable' a un evento)
    public function registrosHoras()
    {
        return $this->morphMany(RegistroHora::class, 'vinculable');
    }

    /* =====================
     | Scopes útiles
     |=====================*/
    public function scopeEnCurso($q)      { return $q->where('estado', 'EN_CURSO'); }
    public function scopePlanificados($q) { return $q->where('estado', 'PLANIFICADO'); }
    public function scopeDelPeriodo($q, int $periodoId) { return $q->where('periodo_id', $periodoId); }
    public function scopeEnFecha($q, $fecha) { return $q->whereDate('fecha', $fecha); }
}
