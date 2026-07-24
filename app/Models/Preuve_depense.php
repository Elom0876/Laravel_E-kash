<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preuve_depense extends Model
{
    protected $fillable = [
        'demande_id',
        'chemin_fichier',
        'montant_declare',
        'soumis_par',
        'soumis_at',
        'statut',
        'verifie_par',
        'verifie_at',
        'commentaire',
    ];

    public function demande()
    {
        return $this->belongsTo(Demande::class);
    }

    public function soumisPar()
    {
        return $this->belongsTo(Demandeur::class, 'soumis_par');
    }

    public function verifiePar()
    {
        return $this->belongsTo(Administrateur::class, 'verifie_par');
    }
}
