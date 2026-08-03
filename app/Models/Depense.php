<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Depense extends Model
{
    use HasFactory;

    protected $fillable = [
        'demande_id',
        'caisse_id',
        'montant_reel',
        'enregistre_par',
        'date_depense',
    ];

    protected $casts = [
        'montant_reel' => 'decimal:2',
        'date_depense' => 'datetime',
    ];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class);
    }

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function historique(): MorphMany
    {
        return $this->morphMany(Historique::class, 'mouvement');
    }
}
