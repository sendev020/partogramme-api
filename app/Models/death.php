<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class death extends Model
{
    protected $fillable = [
        'labour_id', 'concerner','cause_deces','heure_deces','notes',
    ];

    public function labour()
    {
        return $this->belongsTo(Labour::class);
    }
}
