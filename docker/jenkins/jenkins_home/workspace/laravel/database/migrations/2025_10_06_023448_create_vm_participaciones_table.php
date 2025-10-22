<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vm_participaciones', function (Blueprint $table) {
        $table->bigIncrements('id');

        // Alcance polimórfico (VmProyecto o VmEvento)
        $table->unsignedBigInteger('participable_id');
        $table->string('participable_type', 191); // 191 para seguridad con índices (utf8mb4)

        // Vínculo (opcional) con expediente académico
        $table->unsignedBigInteger('expediente_id')->nullable();
        $table->foreign('expediente_id')
            ->references('id')->on('expedientes_academicos')
            ->onUpdate('cascade')
            ->onDelete('restrict');

        // Datos para participantes externos (cuando no hay expediente)
        $table->string('externo_nombre')->nullable();
        $table->string('externo_documento', 191)->nullable(); // 191 para índices seguros

        // Catálogos
        $table->enum('rol', ['ALUMNO', 'ORGANIZADOR', 'PONENTE', 'INVITADO', 'VOLUNTARIO', 'OTRO'])->default('ALUMNO');
        $table->enum('estado', ['INSCRITO', 'CONFIRMADO', 'RETIRADO', 'CANCELADO', 'FINALIZADO'])->default('INSCRITO');

        // Índices
        $table->index(['participable_type', 'participable_id'], 'idx_vm_particip_participable');
        $table->index('rol', 'idx_vm_particip_rol');
        $table->index('estado', 'idx_vm_particip_estado');

        // Unicidades con NOMBRE CORTO (evita 1059)
        $table->unique(['participable_type', 'participable_id', 'expediente_id'], 'uniq_vm_particip_exp');
        $table->unique(['participable_type', 'participable_id', 'externo_documento'], 'uniq_vm_particip_extdoc');

        $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vm_participaciones');
    }
};
