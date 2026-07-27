<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportCare extends Model
{
    protected $table = 'support_care';

    protected $fillable = [
        'labour_id',
        'user_id',
        'patient_id',
        'companion_present',
        'oral_fluids',
        'position',
        'pain_relief',
        'notes',
        'recorded_at',
        'synced',
    ];

    // protected $casts = [
    //     'companion_present' => 'nullable|in:oui,non,refuser',
    //     'oral_fluids' => 'nullable|in:oui,non,refuser',
    //     'pain_relief' => 'nullable|in:oui,non,refuser',
    //     'recorded_at' => 'datetime',
    // ];

    public function labour()
    {
        return $this->belongsTo(Labour::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
