<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Entreprise extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'slug',
    ];

    public function caisse(): HasOne
    {
        return $this->hasOne(Caisse::class);
    }

    public function demandeurs(): HasMany
    {
        return $this->hasMany(Demandeur::class);
    }
}
