<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vm_eventos', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FK → periodos_academicos
            $table->unsignedBigInteger('periodo_id');
            $table->foreign('periodo_id')
                ->references('id')->on('periodos_academicos')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Alcance polimórfico (targetable: Sede, Facultad, EpSede)
            $table->unsignedBigInteger('targetable_id');
            $table->string('targetable_type');

            // Datos principales
            $table->string('codigo')->unique(); // UK global del evento
            $table->string('titulo');

            // Programación
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');

            // Estado / reglas
            $table->enum('estado', ['PLANIFICADO', 'EN_CURSO', 'CERRADO', 'CANCELADO'])
                  ->default('PLANIFICADO');

            $table->boolean('requiere_inscripcion')->default(false);
            $table->integer('cupo_maximo')->nullable();

            // Índices de apoyo
            $table->index(['targetable_type', 'targetable_id']);
            $table->index('periodo_id');
            $table->index('fecha');
            $table->index('estado');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vm_eventos');
    }
};
