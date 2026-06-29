<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Death extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'labour_id', 'user_id','district','poste_de_sante', 'concerner', 'cause_deces', 'heure_deces','notes','synced','created_at','updated_at','deleted_at',
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
