<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imagenes', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Alcance polimórfico (Proyecto/Evento/u otro)
            $table->unsignedBigInteger('imageable_id');
            $table->string('imageable_type', 150);

            // Ubicación del archivo
            $table->string('disk', 100);             // p.ej. public, s3, local
            $table->string('path', 191);             // limitar para evitar error de índice
            $table->string('url', 2048)->nullable(); // URL absoluta opcional (CDN, S3, etc.)

            // Metadatos
            $table->string('titulo', 255)->nullable();
            $table->enum('visibilidad', ['PUBLICA', 'PRIVADA', 'RESTRINGIDA'])->default('PRIVADA');

            // Autor (quien sube)
            $table->unsignedBigInteger('subido_por');
            $table->foreign('subido_por')
                ->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Índices
            $table->index(['imageable_type', 'imageable_id']);
            $table->index('disk');
            $table->index('visibilidad');

            // ⚙️ Clave única segura para MySQL utf8mb4
            $table->unique(['disk', 'path']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagenes');
    }
};