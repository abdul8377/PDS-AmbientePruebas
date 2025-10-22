<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes_academicos', function (Blueprint $table) {
            $table->bigIncrements('id');

            // ===== FKs =====
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('ep_sede_id');

            $table->foreign('user_id', 'fk_exp_user')
                  ->references('id')->on('users')
                  ->onUpdate('cascade')->onDelete('restrict');

            $table->foreign('ep_sede_id', 'fk_exp_epsede')
                  ->references('id')->on('ep_sede')
                  ->onUpdate('cascade')->onDelete('restrict');

            // ===== Datos de alumno (para staff pueden ser NULL) =====
            $table->string('codigo_estudiante')->nullable();
            $table->string('grupo')->nullable();
            $table->string('correo_institucional')->nullable();

            // ===== Estado y Rol con alcance EP_SEDE =====
            $table->enum('estado', ['ACTIVO', 'SUSPENDIDO', 'EGRESADO', 'CESADO'])
                  ->default('ACTIVO');

            $table->enum('rol', ['ESTUDIANTE','COORDINADOR','ENCARGADO'])
                  ->default('ESTUDIANTE');

            // Vigencias del vínculo
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();

            // ===== Reglas / Índices =====
            // Un solo expediente por (usuario, EP_SEDE)
            $table->unique(['user_id', 'ep_sede_id'], 'uq_exp_user_epsede');

            // Código y correo únicos (aceptan múltiples NULL en MySQL/InnoDB)
            $table->unique('codigo_estudiante', 'uq_exp_codigo');
            $table->unique('correo_institucional', 'uq_exp_correo');

            // Consultas típicas
            $table->index('estado', 'idx_exp_estado');
            $table->index(['ep_sede_id','rol','estado'], 'idx_exp_ep_rol_estado');
            $table->index('user_id', 'idx_exp_user');

            // Garantizar a nivel BD: 1 coordinador ACTIVO y 1 encargado ACTIVO por EP_SEDE
            // (para estudiantes, esta columna queda null y no aplica la unique)
            $table->string('active_staff_role', 20)
                  ->nullable()
                  ->storedAs("case when `estado` = 'ACTIVO' and (`rol` in ('COORDINADOR','ENCARGADO')) then `rol` else null end");

            $table->unique(['ep_sede_id', 'active_staff_role'], 'uq_exp_ep_staff_activo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expedientes_academicos');
    }
};
