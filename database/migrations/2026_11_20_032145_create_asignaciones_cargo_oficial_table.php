<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asignaciones_cargo_oficial', function (Blueprint $table) {
            $table->id();

            $table->foreignId('oficial_id')
                ->constrained('oficiales')
                ->restrictOnDelete();

            $table->foreignId('cargo_id')
                ->constrained('cargos')
                ->restrictOnDelete();

            $table->string('tipo_asignacion', 20);

            $table->date('fecha_inicio');

            $table->date('fecha_fin')->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index(
                ['oficial_id', 'tipo_asignacion', 'fecha_inicio'],
                'asignaciones_cargo_oficial_busqueda_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_cargo_oficial');
    }
};
