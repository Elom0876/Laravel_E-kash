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
        'montant',
        'enregistre_par',
        'date_depense',
    ];

    protected $casts = [
        'montant' => 'index',
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
    
    public function preuve_depense():BelongsTo
    {
        return $this->belongsTo(Preuve_depense::class);
    }

    public function historique(): MorphMany
    {
        return $this->morphMany(Historique::class, 'mouvement');
    }
}
