<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approvisionnement extends Model
{
    protected $fillable = [
        'caisse_id',
        'montant',
        'motif',
        'enregistre_par',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function enregistrePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enregistre_par');
    }
}
