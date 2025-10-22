<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FKs
            $table->unsignedBigInteger('expediente_id');
            $table->foreign('expediente_id')
                ->references('id')->on('expedientes_academicos')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unsignedBigInteger('periodo_id');
            $table->foreign('periodo_id')
                ->references('id')->on('periodos_academicos')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Datos de matrícula
            $table->unsignedTinyInteger('ciclo')->nullable(); // p.ej. 1..12
            $table->string('grupo')->nullable();

            // Catálogos (ajusta valores si usas otros)
            $table->enum('modalidad_estudio', ['PRESENCIAL', 'SEMIPRESENCIAL', 'VIRTUAL'])->nullable();
            $table->enum('modo_contrato', ['REGULAR', 'CONVENIO', 'BECA', 'OTRO'])->nullable();

            $table->date('fecha_matricula')->nullable();

            // Reglas / índices
            $table->unique(['expediente_id', 'periodo_id']); // una matrícula por período y expediente
            $table->index('ciclo');
            $table->index('grupo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
