<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Observation extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'labour_id',
        'user_id',
        'district',
        'poste_de_sante',
        'dilation',
        'contractions',
        'fcf',
        'station',
        'systolic_bp',
        'diastolic_bp',
        'temperature',
        'pulse',
        'amniotic_fluid',
        'fetal_heart_deceleration',
        'fetal_position',
        'caput',
        'moulding',
        'urines',
        'maternal_position',
        'oral_fluids',
        'iv_fluids',
        'oxytocin_rate',
        'analgesia',
        'drugs',
        'evalution',
        'care_plan',
        'operation',
        'notes',
        'observed_at',
        'synced',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'observed_at' => 'datetime',
        'synced' => 'boolean',
    ];

    public function labour()
    {
        return $this->belongsTo(Labour::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
