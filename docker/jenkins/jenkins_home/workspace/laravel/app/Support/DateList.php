<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class DateList
{
    /** @return Collection<string> (fechas Y-m-d) */
    public static function fromBatchPayload(array $data): Collection
    {
        if ($data['mode'] === 'list') {
            return collect($data['fechas'])->map(fn($d) => (new Carbon($d))->toDateString())->unique()->values();
        }

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

        $out = collect();
        foreach (CarbonPeriod::create($fi, $ff) as $day) {
            if ($dias->isNotEmpty() && !$dias->contains($day->dayOfWeek)) continue;
            $out->push($day->toDateString());
        }
        return $out->unique()->values();
    }
}
