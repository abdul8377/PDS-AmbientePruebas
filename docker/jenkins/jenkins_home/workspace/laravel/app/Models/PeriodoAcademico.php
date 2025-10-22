<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodoAcademico extends Model
{
    use HasFactory;

    protected $table = 'periodos_academicos';

    protected $fillable = [
        'codigo',
        'anio',
        'ciclo',
        'estado',
        'es_actual',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'es_actual'     => 'boolean',
        'fecha_inicio'  => 'date',
        'fecha_fin'     => 'date',
    ];

    public const ESTADOS = ['PLANIFICADO', 'EN_CURSO', 'CERRADO'];

    /* =====================
     | Relaciones
     |=====================*/
    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'periodo_id');
    }

    public function proyectos()
    {
        return $this->hasMany(VmProyecto::class, 'periodo_id');
    }

    public function eventos()
    {
        return $this->hasMany(VmEvento::class, 'periodo_id');
    }

    public function registrosHoras()
    {
        return $this->hasMany(RegistroHora::class, 'periodo_id');
    }

    /* =====================
     | Scopes útiles
     |=====================*/
    public function scopeActual($query)
    {
        return $query->where('es_actual', true);
    }

    public function scopePorAnioYCiclo($query, int $anio, int $ciclo)
    {
        return $query->where(compact('anio', 'ciclo'));
    }

    public function scopeEnRangoFechas($query, $desde, $hasta)
    {
        return $query->whereDate('fecha_inicio', '<=', $hasta)
                     ->whereDate('fecha_fin', '>=', $desde);
    }
}
