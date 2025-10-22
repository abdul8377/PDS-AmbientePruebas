<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ep_sede', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FKs
            $table->unsignedBigInteger('escuela_profesional_id');
            $table->foreign('escuela_profesional_id')
                ->references('id')->on('escuelas_profesionales')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unsignedBigInteger('sede_id');
            $table->foreign('sede_id')
                ->references('id')->on('sedes')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Vigencias
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();

            // Unicidad EP x Sede (una relación por par)
            $table->unique(['escuela_profesional_id', 'sede_id']);

            // Apoyo para consultas por rango
            $table->index(['vigente_desde', 'vigente_hasta']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ep_sede');
    }
};
