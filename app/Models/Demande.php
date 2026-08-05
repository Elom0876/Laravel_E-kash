<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Demande extends Model
{
    protected $table = 'demandes';

    protected $fillable = [
        'user_id',
        'entreprise_id',
        'motif',po
        'montant_estime',
        'statut',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function depense(): HasOne
    {
        return $this->hasOne(Depense::class);
    }

    public function preuve(): HasOne
    {
        return $this->hasOne(Preuve_depense::class);
    }
}
