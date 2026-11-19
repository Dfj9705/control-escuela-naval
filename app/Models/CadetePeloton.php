<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CadetePeloton extends Model
{
    protected $table = 'cadete_pelotones';

    protected $fillable = [
        'cadete_id',
        'peloton_id',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function cadete(): BelongsTo
    {
        return $this->belongsTo(Cadete::class);
    }

    public function peloton(): BelongsTo
    {
        return $this->belongsTo(Peloton::class);
    }
}
