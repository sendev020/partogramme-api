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
        'uterotonic_given',
        'uterotonic_type',
        'cord_clamping_time',
        'controlled_cord_traction',
        'uterine_massage',
        'uterine_tone_checked',
        'placenta_complete',
        'estimated_blood_loss_ml',
        'operation',
        'synced',
        'created_at',
        'updated_at',
        'deleted_at',
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
