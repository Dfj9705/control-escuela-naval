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
        Schema::create('personas', function (Blueprint $table) {
            $table->id();

            $table->string('catalogo', 30)->nullable()->unique();
            $table->string('nombres', 150);
            $table->string('apellidos', 150);

            $table->string('etnia', 100)->nullable();
            $table->string('grupo_sanguineo', 5)->nullable();

            $table->string('tipo_documento', 30)->nullable();
            $table->string('numero_documento', 50)->nullable();

            $table->string('nit', 30)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->text('direccion')->nullable();
            $table->string('correo_electronico')->nullable();

            $table->date('fecha_nacimiento')->nullable();

            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
