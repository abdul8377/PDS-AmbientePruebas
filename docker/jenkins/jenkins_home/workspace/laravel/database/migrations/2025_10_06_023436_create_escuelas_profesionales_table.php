<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escuelas_profesionales', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FK → facultades
            $table->unsignedBigInteger('facultad_id');
            $table->foreign('facultad_id')
                ->references('id')->on('facultades')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Atributos
            $table->string('codigo');
            $table->string('nombre');

            // Reglas de unicidad dentro de la facultad
            $table->unique(['facultad_id', 'codigo']);
            $table->unique(['facultad_id', 'nombre']);

            // Apoyo
            $table->index('codigo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escuelas_profesionales');
    }
};
