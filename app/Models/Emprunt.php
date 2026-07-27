<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Emprunt extends Model
{
    use HasFactory;

    protected $fillable = [
        'caisse_preteuse_id',
        'caisse_emprunteuse_id',
        'montant',
        'motif',
        'statut',
        'date_emprunt',
        'date_remboursement',
        'created_by',
    ];

    protected $casts = [
        'montant' => 'index',
        'date_emprunt' => 'datetime',
        'date_remboursement' => 'datetime',
    ];

    // Deux relations vers la même table Caisse : chacune précise sa propre colonne
    public function caissePreteuse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class, 'caisse_preteuse_id');
    }

    public function caisseEmprunteuse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class, 'caisse_emprunteuse_id');
    }

    public function historique(): MorphMany
    {
        return $this->morphMany(Historique::class, 'mouvement');
    }
}
