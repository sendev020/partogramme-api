<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Delivery extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'labour_id',
        'user_id',
        'district',
        'poste_de_sante',
        'voie',
        'sexe',
        'poids',
        'heure_naissance',
        'notes',
        'complications',
        'soins_administres',
    ];

    // Relation avec le modèle Labour
    public function labour()
    {
        return $this->belongsTo(Labour::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
