<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universidades', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('codigo')->unique(); // UK
            $table->string('nombre');

            // Enums según tu ERD anterior
            $table->enum('tipo_gestion', ['PUBLICO', 'PRIVADO']);
            $table->enum('estado_licenciamiento', ['LICENCIA_OTORGADA', 'LICENCIA_DENEGADA', 'EN_PROCESO', 'NINGUNO'])
                  ->default('NINGUNO');

            // Timestamps (útiles para auditoría; si no los quieres, avísame y los retiro)
            $table->timestamps();

            // Índices de apoyo
            $table->index('nombre');
            $table->index('tipo_gestion');
            $table->index('estado_licenciamiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universidades');
    }
};
