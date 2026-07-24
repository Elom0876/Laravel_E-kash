<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Approvisionnement extends Model
{
    use HasFactory;

    protected $fillable = [
        'caisse_id',
        'montant',
        'motif',
        'enregistre_par',
        'date_approvisionnement',
    ];

    protected $casts = [
        'montant' => 'index',
        'date_approvisionnement' => 'datetime',
    ];

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    // "enregistre_par" ne suit pas la convention ("administrateur_id") : colonne précisée
    public function enregistrePar(): BelongsTo
    {
        return $this->belongsTo(Administrateur::class, 'enregistre_par');
    }

    // Ligne(s) correspondante(s) dans le journal général
    public function historique(): MorphMany
    {
        return $this->morphMany(Historique::class, 'mouvement');
    }
}
