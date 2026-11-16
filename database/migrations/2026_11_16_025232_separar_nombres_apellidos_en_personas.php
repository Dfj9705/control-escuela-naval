<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->string('primer_nombre', 100)->after('catalogo');
            $table->string('segundo_nombre', 100)->nullable()->after('primer_nombre');

            $table->string('primer_apellido', 100)->after('segundo_nombre');
            $table->string('segundo_apellido', 100)->nullable()->after('primer_apellido');
        });
    }

    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropColumn([
                'primer_nombre',
                'segundo_nombre',
                'primer_apellido',
                'segundo_apellido',
            ]);
        });
    }
};
