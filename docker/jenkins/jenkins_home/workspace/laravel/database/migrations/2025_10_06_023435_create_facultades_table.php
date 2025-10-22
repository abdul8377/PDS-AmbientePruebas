<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facultades', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FK → universidades
            $table->unsignedBigInteger('universidad_id');
            $table->foreign('universidad_id')
                ->references('id')->on('universidades')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Atributos
            $table->string('codigo');   // código interno de la facultad
            $table->string('nombre');

            // Reglas de unicidad dentro de una universidad
            $table->unique(['universidad_id', 'codigo']);
            $table->unique(['universidad_id', 'nombre']);

            // Apoyo
            $table->index('codigo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facultades');
    }
};
