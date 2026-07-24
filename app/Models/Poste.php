<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Poste extends Model
{
    protected $fillable = ['nom', 'slug', 'role'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
