<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sanciones_disciplinarias', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('cadete_id')
                ->constrained('cadetes')
                ->restrictOnDelete();

            $table->foreignId('falta_disciplinaria_id')
                ->constrained('faltas_disciplinarias')
                ->restrictOnDelete();

            $table->foreignId('responsable_id')
                ->constrained('personas')
                ->restrictOnDelete();

            $table->foreignId('registrado_por')
                ->constrained('users')
                ->restrictOnDelete();

            // Información de la falta
            $table->date('fecha_falta');
            $table->text('descripcion_hechos')->nullable();

            // Sanción aplicada
            $table->unsignedSmallInteger('cantidad_aplicada')->nullable();
            $table->string('tipo_sancion', 20);

            // Procedimiento
            $table->string('estado', 30)->default('registrada');

            // Documento firmado
            $table->string('boleta_firmada')->nullable();

            // Confirmación
            $table->foreignId('confirmado_por')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('confirmado_at')->nullable();

            // Anulación
            $table->foreignId('anulado_por')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('anulado_at')->nullable();
            $table->text('motivo_anulacion')->nullable();

            $table->timestamps();

            // Índices de consulta
            $table->index(['cadete_id', 'fecha_falta']);
            $table->index(['estado', 'fecha_falta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanciones_disciplinarias');
    }
};
