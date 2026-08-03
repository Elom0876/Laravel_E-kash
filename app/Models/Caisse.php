<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caisse extends Model
{
    use HasFactory;

    protected $fillable = [
        'entreprise_id',
        'nom',
        'solde',
    ];
    protected $casts = [
        'solde' => 'decimal:2',
    ];

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class);
    }

    public function approvisionnements(): HasMany
    {
        return $this->hasMany(Approvisionnement::class);
    }

    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class);
    }

    public function historique(): HasMany
    {
        return $this->hasMany(Historique::class);
    }

    // Une caisse peut prêter à d'autres caisses...
    public function empruntsEnTantquePreteuse(): HasMany
    {
        return $this->hasMany(Emprunt::class, 'caisse_preteuse_id');
    }

    // ...ou emprunter à d'autres caisses.
    public function empruntsEnTantquEmprunteuse(): HasMany
    {
        return $this->hasMany(Emprunt::class, 'caisse_emprunteuse_id');
    }
}
