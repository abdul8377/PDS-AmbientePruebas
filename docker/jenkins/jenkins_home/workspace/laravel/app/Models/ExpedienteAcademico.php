<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpedienteAcademico extends Model
{
    use HasFactory;

    protected $table = 'expedientes_academicos';

    protected $fillable = [
        'user_id',
        'ep_sede_id',
        'codigo_estudiante',
        'grupo',
        'correo_institucional',
        'estado',
        'rol',
        'vigente_desde',
        'vigente_hasta',
    ];

    /* =====================
     | Relaciones
     |=====================*/
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function epSede()
    {
        return $this->belongsTo(EpSede::class, 'ep_sede_id');
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'expediente_id');
    }

    public function participaciones()
    {
        return $this->hasMany(VmParticipacion::class, 'expediente_id');
    }

    public function asistencias()
    {
        return $this->hasMany(VmAsistencia::class, 'expediente_id');
    }

    public function certificados()
    {
        return $this->hasMany(Certificado::class, 'expediente_id');
    }

    public function registrosHoras()
    {
        return $this->hasMany(RegistroHora::class, 'expediente_id');
    }

    /* =====================
     | Scopes útiles
     |=====================*/
    public function scopeActivos($q)     { return $q->where('estado', 'ACTIVO'); }
    public function scopeDeUsuario($q, int $userId)  { return $q->where('user_id', $userId); }
    public function scopeDeEpSede($q, int $epSedeId) { return $q->where('ep_sede_id', $epSedeId); }
    public function scopePorCodigo($q, string $codigo) { return $q->where('codigo_estudiante', $codigo); }
}
