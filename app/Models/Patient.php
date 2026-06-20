<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table = 'patients';

    protected $fillable = [
        'user_id',
        'district',
        'poste_de_sante',
        'age',
        'name',
        'parity',
        'gestational_age',
        'risk_factors',
        'synced',
        'server_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function labours()
    {
        return $this->hasMany(Labour::class);
    }
}
