<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Matricula;
use App\Models\PeriodoAcademico;
use App\Models\ExpedienteAcademico;

class MatriculasSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Tomamos el período actual (o el último por si acaso)
        $periodo = PeriodoAcademico::where('es_actual', true)->first()
                 ?? PeriodoAcademico::orderByDesc('id')->firstOrFail();

        // 2) Estudiantes de demo (creados por DemoUsersSeeder)
        $alumnos = [
            // username        ciclo grupo
            ['upeu.jorge',     1,    'A1'],
            ['upeu.sofia',     2,    'B1'],
            ['upeu.pedro',     1,    'C1'],
        ];

        foreach ($alumnos as [$username, $ciclo, $grupo]) {
            $user = User::where('username', $username)->first();

            if (!$user) {
                $this->command->warn("Usuario {$username} no existe. Saltando.");
                continue;
            }

            // 3) Expediente ACTIVO del estudiante (por diseño 1 por EP_SEDE)
            $expediente = ExpedienteAcademico::query()
                ->where('user_id', $user->id)
                ->where('rol', 'ESTUDIANTE')
                ->where('estado', 'ACTIVO')
                ->latest('id')
                ->first();

            if (!$expediente) {
                $this->command->warn("{$username} no tiene expediente ESTUDIANTE ACTIVO. Saltando.");
                continue;
            }

            // 4) Una matrícula por (expediente, período)
            Matricula::updateOrCreate(
                [
                    'expediente_id' => $expediente->id,
                    'periodo_id'    => $periodo->id,
                ],
                [
                    'ciclo'              => $ciclo,
                    'grupo'              => $grupo,
                    'modalidad_estudio'  => null, // <- ajusta si tienes ENUM definidos
                    'modo_contrato'      => null, // <- ajusta si tienes ENUM definidos
                    'fecha_matricula'    => $periodo->fecha_inicio ?? now()->toDateString(),
                ]
            );

            $this->command->info("Matrícula creada/actualizada: {$username} → periodo {$periodo->codigo}");
        }
    }
}
