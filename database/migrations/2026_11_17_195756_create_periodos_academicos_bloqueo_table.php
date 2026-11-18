<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('periodos_academicos_bloqueo', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
        });

        DB::table('periodos_academicos_bloqueo')
            ->insert(['id' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_academicos_bloqueo');
    }
};
