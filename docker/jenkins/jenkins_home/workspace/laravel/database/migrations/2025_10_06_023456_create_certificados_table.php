<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificados', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Polimórfica: proyecto o evento
            $table->unsignedBigInteger('certificable_id');
            $table->string('certificable_type');

            // Opcional: vinculado a un alumno (expediente)
            $table->unsignedBigInteger('expediente_id')->nullable();
            $table->foreign('expediente_id')
                ->references('id')->on('expedientes_academicos')
                ->onUpdate('cascade')
                ->onDelete('set null');

            // Datos
            $table->enum('rol', ['ALUMNO', 'ORGANIZADOR', 'PONENTE', 'INVITADO', 'VOLUNTARIO', 'OTRO'])->default('ALUMNO');
            $table->unsignedSmallInteger('minutos');

            $table->string('codigo_unico')->unique(); // UK global (para verificación pública)
            $table->enum('estado', ['PENDIENTE', 'EMITIDO', 'ANULADO'])->default('PENDIENTE');
            $table->timestamp('emitido_at')->nullable();

            // Índices de apoyo
            $table->index(['certificable_type', 'certificable_id']);
            $table->index('expediente_id');
            $table->index('rol');
            $table->index('estado');
            $table->index('emitido_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados');
    }
};
