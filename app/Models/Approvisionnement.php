<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Approvisionnement extends Model
{
    use HasFactory;

    protected $fillable = [
        'caisse_id',
        'montant',
        'motif',
        'enregistre_par',
        'date_approvisionnement',
        'source_type',
        'compte_bancaire',
        'mode_reglement',
        'numero_cheque',
        'depose_par',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_approvisionnement' => 'datetime',
    ];

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    // "enregistre_par" ne suit pas la convention ("administrateur_id") : colonne précisée
    public function enregistrePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enregistre_par');
    }

    // Ligne(s) correspondante(s) dans le journal général
}
