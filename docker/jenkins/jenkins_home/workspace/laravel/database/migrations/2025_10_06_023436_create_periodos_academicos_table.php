<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_academicos', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('codigo')->unique();     // UK
            $table->smallInteger('anio');           // p.ej. 2025
            $table->unsignedTinyInteger('ciclo');   // 1 | 2

            $table->enum('estado', ['PLANIFICADO', 'EN_CURSO', 'CERRADO'])
                  ->default('PLANIFICADO');

            $table->boolean('es_actual')->default(false);

            $table->date('fecha_inicio');
            $table->date('fecha_fin');

            // Reglas útiles
            $table->unique(['anio', 'ciclo']); // evita duplicados por año/ciclo

            // Índices de apoyo
            $table->index('estado');
            $table->index('es_actual');
            $table->index(['fecha_inicio', 'fecha_fin']);

            $table->timestamps();
        });

        // (Opcional) CHECK de ciclo ∈ {1,2} si tu motor lo soporta.
        // Descomenta si usas MySQL 8+ o PostgreSQL.
        // DB::statement("ALTER TABLE periodos_academicos ADD CONSTRAINT chk_ciclo CHECK (ciclo IN (1,2))");
        // DB::statement("ALTER TABLE periodos_academicos ADD CONSTRAINT chk_fechas CHECK (fecha_inicio <= fecha_fin)");
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_academicos');
    }
};
