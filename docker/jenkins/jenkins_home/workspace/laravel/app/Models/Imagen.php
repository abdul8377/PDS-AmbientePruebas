<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;


class Imagen extends Model
{
    use HasFactory;

    protected $table = 'imagenes';

    protected $fillable = [
        'imageable_id',
        'imageable_type',
        'disk',
        'path',
        'url',
        'titulo',
        'visibilidad',
        'subido_por',
    ];

    /* =====================
     | Relaciones
     |=====================*/
    public function imageable()
    {
        return $this->morphTo();
    }

    public function subidor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    /* =====================
     | Scopes útiles
     |=====================*/
    public function scopePublicas($q)
    {
        return $q->where('visibilidad', 'PUBLICA');
    }

    public function scopeDeModel($q, string $type, int $id)
    {
        return $q->where('imageable_type', $type)->where('imageable_id', $id);
    }

    public function scopeDeUsuario($q, int $userId)
    {
        return $q->where('subido_por', $userId);
    }



    /* =====================
     | Helpers
     |=====================*/
    public function getUrlPublicaAttribute(): ?string
    {
        if ($this->url) {
            return $this->url;
        }

        try {
            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk($this->disk);

            // Si el adaptador expone url(), úsala
            if (method_exists($disk, 'url')) {
                return $disk->url($this->path);
            }

            // Fallback: si es el disco por defecto o 'public', usa Storage::url()
            if ($this->disk === config('filesystems.default') || $this->disk === 'public') {
                return Storage::url($this->path);
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
