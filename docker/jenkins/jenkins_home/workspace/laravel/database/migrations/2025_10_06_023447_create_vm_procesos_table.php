<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vm_procesos', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FK → vm_proyectos
            $table->unsignedBigInteger('proyecto_id');
            $table->foreign('proyecto_id')
                ->references('id')->on('vm_proyectos')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Atributos
            $table->string('nombre');
            $table->text('descripcion')->nullable();

            // Catálogos (ajústalos si usas otros valores)
            $table->enum('tipo_registro', ['HORAS', 'ASISTENCIA', 'EVALUACION', 'MIXTO']);
            $table->unsignedSmallInteger('horas_asignadas')->nullable(); // minutos/horas planificadas del proceso
            $table->unsignedTinyInteger('nota_minima')->nullable();      // p.ej. 0..20 ó 0..100
            $table->boolean('requiere_asistencia')->default(false);
            $table->unsignedSmallInteger('orden')->nullable();

            $table->enum('estado', ['PLANIFICADO', 'EN_CURSO', 'CERRADO', 'CANCELADO'])
                  ->default('PLANIFICADO');

            // Reglas/índices
            $table->unique(['proyecto_id', 'nombre']); // evita duplicados dentro del proyecto
            $table->index(['proyecto_id', 'orden']);
            $table->index('estado');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vm_procesos');
    }
};
