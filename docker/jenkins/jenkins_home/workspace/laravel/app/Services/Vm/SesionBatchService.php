<?php

namespace App\Services\Vm;

use App\Models\VmSesion;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class SesionBatchService
{
    /**
     * Crea sesiones (evita duplicados exactos) para un "sessionable" (Proceso o Evento).
     *
     * @param  \Illuminate\Database\Eloquent\Model  $sessionable
     * @param  array $data  // validated payload de SesionBatchRequest
     * @return \Illuminate\Support\Collection<VmSesion>
     */
    public static function createFor($sessionable, array $data): Collection
    {
        $horaInicio = $data['hora_inicio'] . ':00';
        $horaFin    = $data['hora_fin'] . ':00';

        $make = function (string $fecha) use ($sessionable, $horaInicio, $horaFin) {
            return $sessionable->sesiones()->firstOrCreate([
                'fecha'       => $fecha,
                'hora_inicio' => $horaInicio,
                'hora_fin'    => $horaFin,
            ], [
                'estado' => 'PLANIFICADO',
            ]);
        };

        $rows = collect();

        if ($data['mode'] === 'range') {
            $fi = new Carbon($data['fecha_inicio']);
            $ff = new Carbon($data['fecha_fin']);

            $map = [
                'DO'=>0,'DOM'=>0,'SUN'=>0, 0=>0,
                'LU'=>1,'LUN'=>1,'MON'=>1, 1=>1,
                'MA'=>2,'MAR'=>2,'TUE'=>2, 2=>2,
                'MI'=>3,'MIE'=>3,'WED'=>3, 3=>3,
                'JU'=>4,'JUE'=>4,'THU'=>4, 4=>4,
                'VI'=>5,'VIE'=>5,'FRI'=>5, 5=>5,
                'SA'=>6,'SAB'=>6,'SAT'=>6, 6=>6,
            ];
            $dias = collect($data['dias_semana'] ?? [])->map(function ($v) use ($map) {
                $k = is_int($v) ? $v : strtoupper($v);
                return $map[$k] ?? null;
            })->filter()->unique()->values();

            foreach (CarbonPeriod::create($fi, $ff) as $day) {
                if ($dias->isNotEmpty() && !$dias->contains($day->dayOfWeek)) continue;
                $rows->push($make($day->toDateString()));
            }
        } else {
            foreach ($data['fechas'] as $fecha) {
                $rows->push($make((new Carbon($fecha))->toDateString()));
            }
        }

        return $rows;
    }
}
