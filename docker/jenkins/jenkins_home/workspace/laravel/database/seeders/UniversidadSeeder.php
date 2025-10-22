<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Universidad;

class UniversidadSeeder extends Seeder
{
    public function run(): void
    {
        // Única universidad del sistema
        Universidad::firstOrCreate(
            ['codigo' => 'UPeU'],
            [
                'nombre'                => 'Universidad Peruana Unión',
                'tipo_gestion'          => 'PRIVADO',
                'estado_licenciamiento' => 'LICENCIA_OTORGADA',
            ]
        );
    }
}
