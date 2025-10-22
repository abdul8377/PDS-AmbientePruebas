<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facultad extends Model
{
    use HasFactory;

    protected $table = 'facultades';

    protected $fillable = [
        'universidad_id',
        'codigo',
        'nombre',
    ];

    /* =====================
     | Relaciones
     |=====================*/
    public function universidad()
    {
        return $this->belongsTo(Universidad::class, 'universidad_id');
    }

    public function escuelasProfesionales()
    {
        return $this->hasMany(EscuelaProfesional::class, 'facultad_id');
    }

    // Polimórfica: targetable de eventos
    public function eventos()
    {
        return $this->morphMany(VmEvento::class, 'targetable');
    }

    /* =====================
     | Scopes útiles
     |=====================*/
    public function scopeDeUniversidad($query, int $universidadId)
    {
        return $query->where('universidad_id', $universidadId);
    }

    public function scopeConCodigo($query, string $codigo)
    {
        return $query->where('codigo', $codigo);
    }
}
