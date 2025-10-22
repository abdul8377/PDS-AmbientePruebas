<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VmProceso extends Model
{
    use HasFactory;

    protected $table = 'vm_procesos';

    protected $fillable = [
        'proyecto_id',
        'nombre',
        'descripcion',
        'tipo_registro',
        'horas_asignadas',
        'nota_minima',
        'requiere_asistencia',
        'orden',
        'estado',
    ];

    protected $casts = [
        'requiere_asistencia' => 'boolean',
        'horas_asignadas'     => 'integer',
        'nota_minima'         => 'integer',
        'orden'               => 'integer',
    ];

    /* =====================
     | Relaciones
     |=====================*/
    public function proyecto()
    {
        return $this->belongsTo(VmProyecto::class, 'proyecto_id');
    }

    // Sesiones polimórficas (este proceso "tiene" sesiones)
    public function sesiones()
    {
        return $this->morphMany(VmSesion::class, 'sessionable');
    }

    /* =====================
     | Scopes útiles
     |=====================*/
    public function scopeDeProyecto($q, int $proyectoId)
    {
        return $q->where('proyecto_id', $proyectoId);
    }

    public function scopeEnCurso($q)
    {
        return $q->where('estado', 'EN_CURSO');
    }

    protected static function booted()
    {
        static::deleting(function ($proceso) {
            $proceso->sesiones()->delete();
        });
    }

    public function scopeOrdenados($q)
    {
        return $q->orderBy('orden');
    }
}
