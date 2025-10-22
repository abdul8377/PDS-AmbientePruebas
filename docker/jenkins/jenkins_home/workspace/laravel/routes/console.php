<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes (Laravel 12)
|--------------------------------------------------------------------------
| Asegúrate del cron del sistema:
| * * * * * php /ruta/a/tu/app/artisan schedule:run >> /dev/null 2>&1
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})
->purpose('Display an inspiring quote')
->hourly();

Schedule::command('vm:tick')
    ->everyMinute()
    ->withoutOverlapping()
    // en local y production (agrega 'staging' si aplica)
    ->environments(['local','production'])
    // en local quita onOneServer si no usas Redis/Memcached
    // ->onOneServer()
    ->description('Actualiza estados de sesiones/procesos/proyectos/eventos');
