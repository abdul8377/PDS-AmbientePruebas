<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VmParticipacion extends Model
{
    use HasFactory;

    protected $table = 'vm_participaciones';

    protected $fillable = [
        'participable_id',
        'participable_type',
        'expediente_id',
        'externo_nombre',
        'externo_documento',
        'rol',
        'estado',
    ];

    /* =====================
     | Relaciones
     |=====================*/
    // Proyecto o Evento
    public function participable()
    {
        return $this->morphTo();
    }

    // Alumno (si aplica)
    public function expediente()
    {
        return $this->belongsTo(ExpedienteAcademico::class, 'expediente_id');
    }

    // Asistencias registradas para esta participación
    public function asistencias()
    {
        return $this->hasMany(VmAsistencia::class, 'participacion_id');
    }

    // Certificados emitidos para este participante (vía certificable = proyecto/evento)
    // Se consultan normalmente desde Proyecto/Evento; aquí no hay relación directa.

    /* =====================
     | Scopes útiles
     |=====================*/
    public function scopeDeParticipable($q, string $type, int $id)
    {
        return $q->where('participable_type', $type)->where('participable_id', $id);
    }

    public function scopeDeExpediente($q, int $expedienteId)
    {
        return $q->where('expediente_id', $expedienteId);
    }

    public function scopePorRol($q, string $rol)
    {
        return $q->where('rol', $rol);
    }

    public function scopeActivas($q)
    {
        return $q->whereIn('estado', ['INSCRITO', 'CONFIRMADO']);
    }
}
