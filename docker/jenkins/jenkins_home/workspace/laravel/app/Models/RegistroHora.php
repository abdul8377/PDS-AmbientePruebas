<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroHora extends Model
{
    use HasFactory;

    protected $table = 'registro_horas';

    protected $fillable = [
        'expediente_id',
        'ep_sede_id',
        'periodo_id',
        'fecha',
        'minutos',
        'actividad',
        'estado',
        'vinculable_id',
        'vinculable_type',
        'sesion_id',
        'asistencia_id',
    ];

    protected $casts = [
        'fecha'   => 'date',
        'minutos' => 'integer',
    ];

    // Relaciones
    public function expediente()  { return $this->belongsTo(ExpedienteAcademico::class, 'expediente_id'); }
    public function sesion()      { return $this->belongsTo(VmSesion::class, 'sesion_id'); }
    public function asistencia()  { return $this->belongsTo(VmAsistencia::class, 'asistencia_id'); }
    public function vinculable()  { return $this->morphTo(); }

    // Scopes útiles
    public function scopeDeExpediente($q, int $expedienteId) { return $q->where('expediente_id', $expedienteId); }
    public function scopeDePeriodo($q, int $periodoId) { return $q->where('periodo_id', $periodoId); }
}
