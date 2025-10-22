<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_horas', function (Blueprint $table) {
            $table->bigIncrements('id');

            // ===== FKs obligatorias =====
            $table->unsignedBigInteger('expediente_id');
            $table->foreign('expediente_id')
                ->references('id')->on('expedientes_academicos')
                ->onUpdate('cascade')
                ->onDelete('restrict');

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

            // ===== Datos principales =====
            $table->date('fecha');                   // día trabajado
            $table->unsignedSmallInteger('minutos'); // minutos a validar
            $table->string('actividad');             // breve descripción

            // Estado
            $table->enum('estado', ['PENDIENTE', 'APROBADO', 'RECHAZADO', 'ANULADO'])
                  ->default('APROBADO'); // al validar asistencia, lo marcamos APROBADO

            // Polimórfica: vínculo a Proyecto/Evento u otros
            $table->unsignedBigInteger('vinculable_id');
            $table->string('vinculable_type');

            // Referencias opcionales a sesión/asistencia
            $table->unsignedBigInteger('sesion_id')->nullable();
            $table->foreign('sesion_id')
                ->references('id')->on('vm_sesiones')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->unsignedBigInteger('asistencia_id')->nullable();
            $table->foreign('asistencia_id')
                ->references('id')->on('vm_asistencias')
                ->onUpdate('cascade')
                ->onDelete('set null');

            // ===== Índices =====
            $table->unique('asistencia_id', 'uq_reg_horas_asistencia'); // evita duplicados por asistencia
            $table->index('expediente_id');
            $table->index('ep_sede_id');
            $table->index('periodo_id');
            $table->index('fecha');
            $table->index('estado');
            $table->index(['vinculable_type', 'vinculable_id']);
            $table->index('sesion_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_horas');
    }
};
