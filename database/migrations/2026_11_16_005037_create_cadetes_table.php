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
        Schema::create('cadetes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('persona_id')
                ->unique()
                ->constrained('personas')
                ->restrictOnDelete();

            $table->foreignId('grado_id')
                ->nullable()
                ->constrained('grados')
                ->restrictOnDelete();

            $table->date('fecha_ingreso')->nullable();

            $table->string('estado', 30)->default('activo');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cadetes');
    }
};
