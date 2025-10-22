<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
    use HasFactory;

    protected $table = 'matriculas';

    protected $fillable = [
        'expediente_id',
        'periodo_id',
        'ciclo',
        'grupo',
        'modalidad_estudio',
        'modo_contrato',
        'fecha_matricula',
    ];

    protected $casts = [
        'fecha_matricula' => 'date',
    ];

    /* =====================
     | Relaciones
     |=====================*/
    public function expediente()
    {
        return $this->belongsTo(ExpedienteAcademico::class, 'expediente_id');
    }

    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
    }

    /* =====================
     | Scopes útiles
     |=====================*/
    public function scopeDeExpediente($q, int $expedienteId)
    {
        return $q->where('expediente_id', $expedienteId);
    }

    public function scopeDelPeriodo($q, int $periodoId)
    {
        return $q->where('periodo_id', $periodoId);
    }

    public function scopeDelCiclo($q, int $ciclo)
    {
        return $q->where('ciclo', $ciclo);
    }
}
