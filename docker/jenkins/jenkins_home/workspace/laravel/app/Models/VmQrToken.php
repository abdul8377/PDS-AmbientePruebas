<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VmQrToken extends Model
{
    use HasFactory;

    protected $table = 'vm_qr_tokens';

    protected $fillable = [
        'sesion_id',
        'token',
        'tipo',
        'usable_from',
        'expires_at',
        'max_usos',
        'usos',
        'activo',
        'lat',
        'lng',
        'radio_m',
        'meta',
        'creado_por',
    ];

    protected $casts = [
        'usable_from' => 'datetime',
        'expires_at'  => 'datetime',
        'max_usos'    => 'integer',
        'usos'        => 'integer',
        'activo'      => 'boolean',
        'lat'         => 'decimal:7',
        'lng'         => 'decimal:7',
        'radio_m'     => 'integer',
        'meta'        => 'array',
    ];

    // Relaciones
    public function sesion()  { return $this->belongsTo(VmSesion::class, 'sesion_id'); }
    public function creador() { return $this->belongsTo(User::class, 'creado_por'); }
    public function asistencias() { return $this->hasMany(VmAsistencia::class, 'qr_token_id'); }

    // Scopes
    public function scopeActivos($q) { return $q->where('activo', true); }

    public function scopeVigentesAhora($q)
    {
        $ahora = Carbon::now();
        return $q->where(function ($w) use ($ahora) {
                $w->whereNull('usable_from')->orWhere('usable_from', '<=', $ahora);
            })
            ->where(function ($w) use ($ahora) {
                $w->whereNull('expires_at')->orWhere('expires_at', '>=', $ahora);
            })
            ->where('activo', true);
    }

    // Helpers
    public function getUsosRestantesAttribute(): ?int
    {
        if (is_null($this->max_usos)) return null;
        return max(0, $this->max_usos - (int) $this->usos);
    }

    public function getPuedeUsarseAttribute(): bool
    {
        $ahora = Carbon::now();
        $ventanaOk = (is_null($this->usable_from) || $this->usable_from->lte($ahora))
                  && (is_null($this->expires_at)  || $this->expires_at->gte($ahora));
        $limiteOk  = is_null($this->max_usos) || $this->usos < $this->max_usos;
        return $this->activo && $ventanaOk && $limiteOk;
    }
}
