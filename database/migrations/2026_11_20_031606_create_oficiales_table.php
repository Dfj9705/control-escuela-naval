<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('oficiales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('persona_id')
                ->unique()
                ->constrained('personas')
                ->restrictOnDelete();

            $table->foreignId('grado_id')
                ->constrained('grados')
                ->restrictOnDelete();

            $table->string('estado', 20)
                ->default('activo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oficiales');
    }
};
