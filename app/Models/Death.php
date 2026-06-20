<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Death extends Model
{
    protected $fillable = [
        'labour_id', 'user_id','district','poste_de_sante', 'concerner', 'cause_deces', 'heure_deces','notes',
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
