<?php

namespace App\Models;

use App\Models\Labour;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Medicament extends Model
{
    protected $fillable = [
        'labour_id',
        'patient_id',
        'user_id',
        'name',
        'dose',
        'route',
        'administered_at',
        'indication',
        'notes',
        'synced',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

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
