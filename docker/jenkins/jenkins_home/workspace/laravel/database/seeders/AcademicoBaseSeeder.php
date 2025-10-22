<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Universidad;
use App\Models\Sede;
use App\Models\Facultad;
use App\Models\EscuelaProfesional;
use App\Models\EpSede;
use App\Models\PeriodoAcademico;

class AcademicoBaseSeeder extends Seeder
{
    public function run(): void
    {
        $uni = Universidad::where('codigo', 'UPeU')->firstOrFail();

        // ===== SEDES =====
        $sedeLima = Sede::firstOrCreate(
            ['universidad_id' => $uni->id, 'nombre' => 'Sede Lima'],
            ['es_principal' => true, 'esta_suspendida' => false]
        );

        $sedeJuliaca = Sede::firstOrCreate(
            ['universidad_id' => $uni->id, 'nombre' => 'Sede Juliaca'],
            ['es_principal' => false, 'esta_suspendida' => false]
        );

        // ===== FACULTADES =====
        $facIng = Facultad::firstOrCreate(
            ['universidad_id' => $uni->id, 'codigo' => 'FIA'],
            ['nombre' => 'Facultad de Ingeniería y Arquitectura']
        );

        $facSalud = Facultad::firstOrCreate(
            ['universidad_id' => $uni->id, 'codigo' => 'FCS'],
            ['nombre' => 'Facultad de Ciencias de la Salud']
        );

        // ===== ESCUELAS =====
        $escSistemas = EscuelaProfesional::firstOrCreate(
            ['facultad_id' => $facIng->id, 'codigo' => 'SIS'],
            ['nombre' => 'Ingeniería de Sistemas']
        );

        $escArquitectura = EscuelaProfesional::firstOrCreate(
            ['facultad_id' => $facIng->id, 'codigo' => 'ARQ'],
            ['nombre' => 'Arquitectura']
        );

        $escEnfermeria = EscuelaProfesional::firstOrCreate(
            ['facultad_id' => $facSalud->id, 'codigo' => 'ENF'],
            ['nombre' => 'Enfermería']
        );

        // ===== EP_SEDE (vincular Escuela ↔ Sede) =====
        $epSede_SIS_Lima = EpSede::firstOrCreate([
            'escuela_profesional_id' => $escSistemas->id,
            'sede_id'                => $sedeLima->id,
        ]);

        $epSede_ARQ_Lima = EpSede::firstOrCreate([
            'escuela_profesional_id' => $escArquitectura->id,
            'sede_id'                => $sedeLima->id,
        ]);

        $epSede_ENF_Juliaca = EpSede::firstOrCreate([
            'escuela_profesional_id' => $escEnfermeria->id,
            'sede_id'                => $sedeJuliaca->id,
        ]);

        // ===== PERÍODOS ACADÉMICOS (2 antes, actual, 2 después) =====
        $periodos = [
            // 2 anteriores
            [
                'codigo' => '2024-2',
                'anio' => 2024, 'ciclo' => 2,
                'estado' => 'CERRADO', 'es_actual' => false,
                'fecha_inicio' => '2024-08-01', 'fecha_fin' => '2024-12-15',
            ],
            [
                'codigo' => '2025-1',
                'anio' => 2025, 'ciclo' => 1,
                'estado' => 'CERRADO', 'es_actual' => false,
                'fecha_inicio' => '2025-03-01', 'fecha_fin' => '2025-07-15',
            ],

            // actual
            [
                'codigo' => '2025-2',
                'anio' => 2025, 'ciclo' => 2,
                'estado' => 'EN_CURSO', 'es_actual' => true,
                'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-12-15',
            ],

            // 2 posteriores
            [
                'codigo' => '2026-1',
                'anio' => 2026, 'ciclo' => 1,
                'estado' => 'PLANIFICADO', 'es_actual' => false,
                'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-07-15',
            ],
            [
                'codigo' => '2026-2',
                'anio' => 2026, 'ciclo' => 2,
                'estado' => 'PLANIFICADO', 'es_actual' => false,
                'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-12-15',
            ],
        ];

        foreach ($periodos as $p) {
            PeriodoAcademico::updateOrCreate(
                ['anio' => $p['anio'], 'ciclo' => $p['ciclo']], // respeta unique(anio,ciclo)
                [
                    'codigo'        => $p['codigo'],
                    'estado'        => $p['estado'],
                    'es_actual'     => $p['es_actual'],
                    'fecha_inicio'  => $p['fecha_inicio'],
                    'fecha_fin'     => $p['fecha_fin'],
                ]
            );
        }

        // Asegura que solo 2025-2 quede marcado como actual
        PeriodoAcademico::where('codigo', '!=', '2025-2')->update(['es_actual' => false]);
    }
}
