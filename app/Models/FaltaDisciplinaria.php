<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaltaDisciplinaria extends Model
{
    protected $table = 'faltas_disciplinarias';

    protected $fillable = [
        'tipo',
        'numeral',
        'categoria',
        'descripcion',
        'tipo_sancion',
        'cantidad_base',
        'fundamento_legal',
        'version_reglamento',
        'activo',
    ];

    protected $casts = [
        'numeral' => 'integer',
        'cantidad_base' => 'integer',
        'activo' => 'boolean',
    ];
}
