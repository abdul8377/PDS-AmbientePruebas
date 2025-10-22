<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EscuelaProfesional extends Model
{
    use HasFactory;

    protected $table = 'escuelas_profesionales';

    protected $fillable = [
        'facultad_id',
        'codigo',
        'nombre',
    ];

    /* =====================
     | Relaciones
     |=====================*/
    public function facultad()
    {
        return $this->belongsTo(Facultad::class, 'facultad_id');
    }

    public function epSedes()
    {
        return $this->hasMany(EpSede::class, 'escuela_profesional_id');
    }

    // Conveniencia: proyectos de la escuela (vía EP_SEDE)
    public function proyectos()
    {
        return $this->hasManyThrough(
            VmProyecto::class,    // Modelo destino
            EpSede::class,        // Modelo intermedio
            'escuela_profesional_id', // FK en EpSede que apunta a esta escuela
            'ep_sede_id',         // FK en VmProyecto que apunta a EpSede
            'id',                 // Local key en EscuelaProfesional
            'id'                  // Local key en EpSede
        );
    }
}
