<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VmAsistencia extends Model
{
    use HasFactory;

    protected $table = 'vm_asistencias';

    protected $fillable = [
        'sesion_id',
        'expediente_id',
        'participacion_id',
        'qr_token_id',
        'metodo',
        'check_in_at',
        'check_out_at',
        'estado',
        'minutos_validados',
        'meta',
    ];

    protected $casts = [
        'check_in_at'       => 'datetime',
        'check_out_at'      => 'datetime',
        'minutos_validados' => 'integer',
        'meta'              => 'array',
    ];

    // Relaciones
    public function sesion()        { return $this->belongsTo(VmSesion::class, 'sesion_id'); }
    public function expediente()    { return $this->belongsTo(ExpedienteAcademico::class, 'expediente_id'); }
    public function participacion() { return $this->belongsTo(VmParticipacion::class, 'participacion_id'); }
    public function qrToken()       { return $this->belongsTo(VmQrToken::class, 'qr_token_id'); }

    // Scopes
    public function scopeDeSesion($q, int $sesionId) { return $q->where('sesion_id', $sesionId); }
    public function scopeDeExpediente($q, int $expedienteId) { return $q->where('expediente_id', $expedienteId); }
    public function scopePendientes($q) { return $q->where('estado', 'PENDIENTE'); }
    public function scopeValidadas($q)  { return $q->where('estado', 'VALIDADO'); }
    public function scopeEntreFechas($q, $desde, $hasta) { return $q->whereBetween('check_in_at', [$desde, $hasta]); }
}
