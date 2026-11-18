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
        Schema::create('faltas_disciplinarias', function (Blueprint $table) {
            $table->id();

            $table->string('tipo', 20);
            $table->unsignedSmallInteger('numeral');
            $table->string('categoria', 150)->nullable();
            $table->text('descripcion');

            $table->string('tipo_sancion', 20);
            $table->unsignedSmallInteger('cantidad_base')->nullable();

            $table->text('fundamento_legal')->nullable();

            $table->string('version_reglamento', 30)
                ->default('2026');

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->unique(
                ['version_reglamento', 'tipo', 'numeral'],
                'faltas_reglamento_tipo_numeral_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('falta_disciplinarias');
    }
};
