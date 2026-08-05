<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Historique extends Model
{
    use HasFactory;

    protected $table = 'historique'; 

    protected $fillable = [
        'caisse_id',
        'type',
        'sens',
        'montant',
        'motif',
        'created_by',
    ];

    protected $casts = [
        'montant' => 'index',
    ];

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function mouvement(): MorphTo
    {
        return $this->morphTo();
    }
}