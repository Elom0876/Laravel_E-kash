<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Demande extends Model
{
    protected $table = 'demandes' ;

    protected $fillale = [
      'demandeur_id',
      'administrateur_id',
      'motif',
      'montant',
      'motif_refus',
      ''
    ];

    public function demandeur(): BelongsTo
    {
        return $this->belongTo(Demandeur::class, 'demandeur_id');
    }
    public function administrateur(): BelongsTo
    {
        return $this->belongsTo(Administrateur::class, 'administrateur_id');
    }
    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
    }
    public function notification(): HasMany
    {
        return $this->hasMany(Notification::class, 'demande_id');
    }
}
