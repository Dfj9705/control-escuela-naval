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
        Schema::create('meritos_disciplinarios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cadete_id')
                ->constrained('cadetes')
                ->restrictOnDelete();

            $table->date('fecha_merito');

            $table->unsignedSmallInteger('cantidad');

            $table->text('motivo');

            $table->foreignId('responsable_id')
                ->constrained('personas')
                ->restrictOnDelete();

            $table->foreignId('registrado_por')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('estado', 20)
                ->default('vigente');

            $table->foreignId('anulado_por')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('anulado_at')->nullable();

            $table->text('motivo_anulacion')->nullable();

            $table->timestamps();

            $table->index(['cadete_id', 'fecha_merito']);
            $table->index(['estado', 'fecha_merito']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meritos_disciplinarios');
    }

};
