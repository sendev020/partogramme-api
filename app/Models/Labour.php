<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Labour extends Model
{
    protected $fillable = [
        'patient_id', 'user_id', 'start_time', 'end_time', 'status',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function observations()
    {
        return $this->hasMany(Observation::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function referral()
    {
        return $this->hasOne(Referral::class);
    }

    public function death()
    {
        return $this->hasOne(Death::class);
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }

}
