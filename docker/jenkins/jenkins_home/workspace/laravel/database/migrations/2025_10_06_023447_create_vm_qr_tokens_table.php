<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vm_qr_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FKs
            $table->unsignedBigInteger('sesion_id');
            $table->foreign('sesion_id')
                ->references('id')->on('vm_sesiones')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->string('token')->unique(); // UK global

            // Tipo de ventana: QR (alumno escanea) o MANUAL (staff llama lista)
            $table->enum('tipo', ['QR','MANUAL'])->default('QR');

            // Ventanas de uso
            $table->timestamp('usable_from')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Límites
            $table->integer('max_usos')->nullable(); // NULL = ilimitado
            $table->integer('usos')->default(0);
            $table->boolean('activo')->default(true);

            // Geocerca (opcional)
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('radio_m')->nullable();

            // Extras
            $table->json('meta')->nullable();

            // Quién lo creó (opcional)
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->foreign('creado_por')
                ->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('set null');

            // Índices de apoyo
            $table->index(['sesion_id','tipo']);
            $table->index('activo');
            $table->index('expires_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vm_qr_tokens');
    }
};
