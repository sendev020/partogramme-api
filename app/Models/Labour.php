<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Labour extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'patient_id', 'user_id', 'district', 'poste_de_sante', 'hospital_referred_to', 'start_time', 'end_time', 'active_phase_start', 'status', 'synced', 'server_id',
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

    public function supportCares()
    {
        return $this->hasMany(SupportCare::class);
    }

    protected $casts = [
        'active_phase_start' => 'datetime',
    ];

    public function medicaments()
    {
        return $this->hasMany(Medicament::class);
    }

    public function death()
    {
        return $this->hasOne(Death::class);
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
