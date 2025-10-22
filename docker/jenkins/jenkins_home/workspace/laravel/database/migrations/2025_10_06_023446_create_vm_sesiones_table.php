<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
         Schema::create('vm_sesiones', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Alcance polimórfico (VmProceso o VmEvento)
            $table->unsignedBigInteger('sessionable_id');
            $table->string('sessionable_type'); // usa MorphMap para alias estables

            // Programación
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');

            // Estado
            $table->enum('estado', ['PLANIFICADO', 'EN_CURSO', 'CERRADO', 'CANCELADO'])
                  ->default('PLANIFICADO');

            // ===== Índices =====
            // Evita duplicar exactamente el mismo “slot” de sesión
            $table->unique(
                ['sessionable_type', 'sessionable_id', 'fecha', 'hora_inicio', 'hora_fin'],
                'uniq_vm_sesion_slot'
            );

            // Apoyo para consultas
            $table->index(['sessionable_type', 'sessionable_id'], 'idx_vm_sesion_owner');
            $table->index('fecha', 'idx_vm_sesion_fecha');
            $table->index('estado', 'idx_vm_sesion_estado');

            $table->timestamps();
        });


        // (Opcional) CHECKs si tu motor lo soporta (MySQL 8+/PostgreSQL)
        // DB::statement("ALTER TABLE vm_sesiones ADD CONSTRAINT chk_horas CHECK (hora_inicio <= hora_fin)");
    }

    public function down(): void
    {
        Schema::dropIfExists('vm_sesiones');
    }
};
