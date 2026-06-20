<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observation extends Model
{
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
        'notes',
        'observed_at',
        'synced',
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
