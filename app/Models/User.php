<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'poste_id',
        'entreprise_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // --- Relations à ajouter ---
    public function poste()
    {
        return $this->belongsTo(Poste::class);
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function getRoleAttribute()
    {
        return $this->poste?->role;
    }
}
