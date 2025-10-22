<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vm_proyectos', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FKs
            $table->unsignedBigInteger('ep_sede_id');
            $table->foreign('ep_sede_id')
                ->references('id')->on('ep_sede')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unsignedBigInteger('periodo_id');
            $table->foreign('periodo_id')
                ->references('id')->on('periodos_academicos')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Datos principales
            $table->string('codigo')->unique();     // UK global
            $table->string('titulo');
            $table->text('descripcion');

            // Catálogos
            $table->enum('tipo', ['VINCULADO','LIBRE'])->default('VINCULADO');
            $table->enum('modalidad', ['PRESENCIAL', 'VIRTUAL', 'MIXTA'])->default('PRESENCIAL');
            $table->enum('estado', ['PLANIFICADO', 'EN_CURSO', 'CERRADO', 'CANCELADO'])->default('PLANIFICADO');

            // Nivel (1..10) — en LIBRE debe ser NULL, en VINCULADO es requerido (1..10)
            $table->unsignedTinyInteger('nivel')->nullable(); // 👈 ahora nullable

            // Horas
            $table->unsignedSmallInteger('horas_planificadas');
            $table->unsignedSmallInteger('horas_minimas_participante')->nullable();

            // Índices
            $table->index('ep_sede_id');
            $table->index('periodo_id');
            $table->index('estado');
            $table->index(['periodo_id', 'nivel']); // apoyo para consultas por periodo/nivel

            // Único: 1 proyecto por (ep_sede_id, periodo_id, nivel)
            // Con nivel NULL (LIBRE) se permiten múltiples filas (MySQL/Postgres)
            $table->unique(['ep_sede_id', 'periodo_id', 'nivel'], 'uk_vm_proy_ep_periodo_nivel');

            $table->timestamps();
        });

        // CHECK: LIBRE => nivel IS NULL; VINCULADO => nivel entre 1..10
        // (MySQL 8.0.16+ / PostgreSQL; en MySQL <8.0.16 se ignora sin error)
        try {
            DB::statement("
                ALTER TABLE vm_proyectos
                ADD CONSTRAINT chk_vm_proyectos_tipo_nivel
                CHECK (
                    (tipo = 'LIBRE' AND nivel IS NULL)
                    OR
                    (tipo = 'VINCULADO' AND nivel BETWEEN 1 AND 10)
                )
            ");
        } catch (\Throwable $e) {
            // Ignorar si el motor no soporta CHECK
        }
    }

    public function down(): void
    {
        // Intentar quitar el CHECK si existe (Postgres / MySQL 8+)
        try {
            DB::statement('ALTER TABLE vm_proyectos DROP CONSTRAINT chk_vm_proyectos_tipo_nivel');
        } catch (\Throwable $e) {
            // MySQL puede requerir DROP CHECK ...; si no existe, ignorar
            try {
                DB::statement('ALTER TABLE vm_proyectos DROP CHECK chk_vm_proyectos_tipo_nivel');
            } catch (\Throwable $e2) {}
        }

        Schema::dropIfExists('vm_proyectos');
    }
};
