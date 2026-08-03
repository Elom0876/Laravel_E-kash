<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Demande extends Model
{
    protected $table = 'demandes';

    protected $fillable = [
        'user_id',
        'entreprise_id',
        'motif',
        'montant_estime',
        'statut',

    ];

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function administrateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
