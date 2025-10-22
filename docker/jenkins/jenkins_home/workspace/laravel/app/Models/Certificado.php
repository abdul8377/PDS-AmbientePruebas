<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificado extends Model
{
    use HasFactory;

    protected $table = 'certificados';

    protected $fillable = [
        'certificable_id',
        'certificable_type',
        'expediente_id',
        'rol',
        'minutos',
        'codigo_unico',
        'estado',
        'emitido_at',
    ];

    protected $casts = [
        'minutos'    => 'integer',
        'emitido_at' => 'datetime',
    ];

    /* =====================
     | Relaciones
     |=====================*/
    // Proyecto o Evento
    public function certificable()
    {
        return $this->morphTo();
    }

    // Alumno (si aplica)
    public function expediente()
    {
        return $this->belongsTo(ExpedienteAcademico::class, 'expediente_id');
    }

    /* =====================
     | Scopes útiles
     |=====================*/
    public function scopeEmitidos($q)
    {
        return $q->where('estado', 'EMITIDO');
    }

    public function scopeDeExpediente($q, int $expedienteId)
    {
        return $q->where('expediente_id', $expedienteId);
    }

    public function scopePorRol($q, string $rol)
    {
        return $q->where('rol', $rol);
    }

    /* =====================
     | Helpers
     |=====================*/
    public function getEstaEmitidoAttribute(): bool
    {
        return $this->estado === 'EMITIDO';
    }
}
