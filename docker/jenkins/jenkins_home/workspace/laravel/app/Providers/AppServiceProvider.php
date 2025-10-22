<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Models\EpSede;
use App\Models\Facultad;
use App\Models\Sede;
use App\Models\VmEvento;
use App\Models\VmProceso;
use App\Models\VmProyecto;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ✅ Aliases CANÓNICOS (lo que se guardará de ahora en adelante)
        $canonical = [
            'user'        => User::class,        // Spatie model_has_* agradece un alias estable
            'vm_proyecto' => VmProyecto::class,
            'vm_proceso'  => VmProceso::class,
            'vm_evento'   => VmEvento::class,
            'ep_sede'     => EpSede::class,
            'sede'        => Sede::class,
            'facultad'    => Facultad::class,
        ];

        // ♻️ Compatibilidad hacia atrás (tipos que ya podrían existir en la BD)
        $backwardCompatibility = [
            // FQCN que pudieron guardarse antes
            'App\\Models\\User'       => User::class,
            'App\\Models\\VmProyecto' => VmProyecto::class,
            'App\\Models\\VmProceso'  => VmProceso::class,
            'App\\Models\\VmEvento'   => VmEvento::class,
            'App\\Models\\EpSede'     => EpSede::class,
            'App\\Models\\Sede'       => Sede::class,
            'App\\Models\\Facultad'   => Facultad::class,

            // Aliases antiguos en PascalCase
            'VmProceso'               => VmProceso::class,
            'VmEvento'                => VmEvento::class,


        ];

        // Importante: los canónicos primero ⇒ Eloquent usará esos al guardar.
        $map = $canonical + $backwardCompatibility;

        Relation::enforceMorphMap($map);
    }
}
