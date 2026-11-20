<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asignaciones_semestre', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cadete_id')
                ->constrained('cadetes')
                ->restrictOnDelete();

            $table->foreignId('periodo_academico_id')
                ->constrained('periodos_academicos')
                ->restrictOnDelete();

            $table->foreignId('semestre_id')
                ->constrained('semestres')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(
                ['cadete_id', 'periodo_academico_id'],
                'asignacion_cadete_periodo_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignaciones_semestre');
    }
};
