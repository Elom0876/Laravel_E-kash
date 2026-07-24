<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'destinataire_type',
        'destinataire_id',
        'canal',
        'type',
        'contenu',
        'statut_envoi',
        'envoyee_at',
    ];

    public function destinataire()
    {
        return $this->morphTo();
    }
}