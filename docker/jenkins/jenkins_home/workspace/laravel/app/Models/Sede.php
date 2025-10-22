<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    use HasFactory;

    protected $table = 'sedes';

    protected $fillable = [
        'universidad_id',
        'nombre',
        'es_principal',
        'esta_suspendida',
    ];

    protected $casts = [
        'es_principal'   => 'boolean',
        'esta_suspendida'=> 'boolean',
    ];

    /* =====================
     |  Relaciones
     |=====================*/
    public function universidad()
    {
        return $this->belongsTo(Universidad::class, 'universidad_id');
    }

    // EP_SEDE (relación intermedia entre Sede y EscuelaProfesional)
    public function epSedes()
    {
        return $this->hasMany(EpSede::class, 'sede_id');
    }

    // Eventos (polimórfica: targetable)
    public function eventos()
    {
        return $this->morphMany(VmEvento::class, 'targetable');
    }

    // Registro de horas (según ERD)
    public function registrosHoras()
    {
        return $this->hasMany(RegistroHora::class, 'ep_sede_id'); // Nota: se asocia por EP_SEDE en la práctica
    }

    /* =====================
     |  Scopes útiles
     |=====================*/
    public function scopePrincipales($query)
    {
        return $query->where('es_principal', true);
    }

    public function scopeSuspendidas($query)
    {
        return $query->where('esta_suspendida', true);
    }
}
