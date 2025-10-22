<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\EpSede;
use App\Models\ExpedienteAcademico;
use App\Models\PeriodoAcademico;

class DemoUsersSeeder extends Seeder
{
    private string $password = 'UPeU2025';

    public function run(): void
    {
        $periodo = PeriodoAcademico::where('codigo', '2025-2')->firstOrFail();

        // EP_SEDE creados en el seeder anterior:
        $epSede_SIS_Lima = EpSede::whereHas('escuelaProfesional', fn($q) => $q->where('codigo','SIS'))
                                ->whereHas('sede', fn($q) => $q->where('nombre','Sede Lima'))
                                ->firstOrFail();

        $epSede_ARQ_Lima = EpSede::whereHas('escuelaProfesional', fn($q) => $q->where('codigo','ARQ'))
                                ->whereHas('sede', fn($q) => $q->where('nombre','Sede Lima'))
                                ->firstOrFail();

        $epSede_ENF_Juliaca = EpSede::whereHas('escuelaProfesional', fn($q) => $q->where('codigo','ENF'))
                                   ->whereHas('sede', fn($q) => $q->where('nombre','Sede Juliaca'))
                                   ->firstOrFail();

        // ====== 1) ADMIN (sin expediente) ======
        $admin = $this->mkUser('admin', 'Admin', 'UPeU', 'admin@upeu.pe');
        $admin->assignRole('ADMINISTRADOR');

        // ====== 2) STAFF: 2 coordinadores + 2 encargados (con expediente) ======
        // Coordinadores
        $coord1 = $this->mkUser('carlos', 'Carlos', 'Quispe', 'carlos@upeu.pe');
        $coord1->assignRole('COORDINADOR');
        $this->vincularStaff($coord1, $epSede_SIS_Lima->id, 'COORDINADOR');

        $coord2 = $this->mkUser('maria', 'María', 'Huamán', 'maria@upeu.pe');
        $coord2->assignRole('COORDINADOR');
        $this->vincularStaff($coord2, $epSede_ARQ_Lima->id, 'COORDINADOR');

        // Encargados
        $enc1 = $this->mkUser('luis', 'Luis', 'Pérez', 'luis@upeu.pe');
        $enc1->assignRole('ENCARGADO');
        $this->vincularStaff($enc1, $epSede_SIS_Lima->id, 'ENCARGADO'); // mismo EP que coord1, permitido (único por rol)

        $enc2 = $this->mkUser('ana', 'Ana', 'Torres', 'ana@upeu.pe');
        $enc2->assignRole('ENCARGADO');
        $this->vincularStaff($enc2, $epSede_ENF_Juliaca->id, 'ENCARGADO');

        // ====== 3) ESTUDIANTES: 3 EP distintas ======
        $est1 = $this->mkUser('jorge', 'Jorge', 'Ramos', 'jorge@upeu.edu.pe');
        $est1->assignRole('ESTUDIANTE');
        $this->vincularEstudiante($est1, $epSede_SIS_Lima->id, $periodo, 'SIS2025-0001', 'jorge.ramos@upeu.edu.pe', 'A1');

        $est2 = $this->mkUser('sofia', 'Sofía', 'López', 'sofia@upeu.edu.pe');
        $est2->assignRole('ESTUDIANTE');
        $this->vincularEstudiante($est2, $epSede_ARQ_Lima->id, $periodo, 'ARQ2025-0001', 'sofia.lopez@upeu.edu.pe', 'B1');

        $est3 = $this->mkUser('pedro', 'Pedro', 'Flores', 'pedro@upeu.edu.pe');
        $est3->assignRole('ESTUDIANTE');
        $this->vincularEstudiante($est3, $epSede_ENF_Juliaca->id, $periodo, 'ENF2025-0001', 'pedro.flores@upeu.edu.pe', 'C1');
    }

    private function mkUser(string $nick, string $first, string $last, string $email): User
    {
        return User::firstOrCreate(
            ['username' => 'upeu.'.$nick],
            [
                'first_name' => $first,
                'last_name'  => $last,
                'email'      => $email,
                'password'   => Hash::make($this->password),
                'status'     => 'active',
            ]
        );
    }

    private function vincularStaff(User $user, int $epSedeId, string $rol): void
    {
        ExpedienteAcademico::updateOrCreate(
            ['user_id' => $user->id, 'ep_sede_id' => $epSedeId],
            [
                'codigo_estudiante'    => null,
                'grupo'                => null,
                'correo_institucional' => null,
                'estado'               => 'ACTIVO',
                'rol'                  => $rol, // COORDINADOR | ENCARGADO
                'vigente_desde'        => now()->toDateString(),
                'vigente_hasta'        => null,
            ]
        );
    }

    private function vincularEstudiante(User $user, int $epSedeId, $periodo, string $codigo, string $correoInst, ?string $grupo = null): void
    {
        ExpedienteAcademico::updateOrCreate(
            ['user_id' => $user->id, 'ep_sede_id' => $epSedeId],
            [
                'codigo_estudiante'    => $codigo,
                'grupo'                => $grupo,
                'correo_institucional' => $correoInst,
                'estado'               => 'ACTIVO',
                'rol'                  => 'ESTUDIANTE',
                'vigente_desde'        => $periodo->fecha_inicio->toDateString(),
                'vigente_hasta'        => null,
            ]
        );
    }
}
