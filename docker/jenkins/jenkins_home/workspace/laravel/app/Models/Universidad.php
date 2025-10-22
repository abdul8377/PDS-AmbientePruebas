<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Universidad extends Model
{
    use HasFactory;

    protected $table = 'universidades';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo_gestion',
        'estado_licenciamiento',
    ];

    // Catálogos para validar/usar en selects
    public const TIPO_GESTION = ['PUBLICO', 'PRIVADO'];
    public const ESTADO_LICENCIAMIENTO = ['LICENCIA_OTORGADA', 'LICENCIA_DENEGADA', 'EN_PROCESO', 'NINGUNO'];

    /* =====================
     |  Relaciones
     |=====================*/
    public function sedes()
    {
        return $this->hasMany(Sede::class, 'universidad_id');
    }

    public function facultades()
    {
        return $this->hasMany(Facultad::class, 'universidad_id');
    }

    /* =====================
     |  Scopes útiles
     |=====================*/
    public function scopePublicas($query)
    {
        return $query->where('tipo_gestion', 'PUBLICO');
    }

    public function scopePrivadas($query)
    {
        return $query->where('tipo_gestion', 'PRIVADO');
    }

    public function scopeConLicenciaOtorgada($query)
    {
        return $query->where('estado_licenciamiento', 'LICENCIA_OTORGADA');
    }

        /* === Imágenes polimórficas === */
    public function imagenes()
    {
        return $this->morphMany(Imagen::class, 'imageable');
    }

    // Último LOGO (titulo = 'LOGO')
    public function logo()
    {
        return $this->morphOne(Imagen::class, 'imageable')->where('titulo', 'LOGO')->latestOfMany();
    }

    // Última PORTADA (titulo = 'PORTADA')
    public function portada()
    {
        return $this->morphOne(Imagen::class, 'imageable')->where('titulo', 'PORTADA')->latestOfMany();
    }
}
