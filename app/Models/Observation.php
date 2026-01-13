<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observation extends Model
{
    protected $fillable = [
        'labour_id',
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
}
