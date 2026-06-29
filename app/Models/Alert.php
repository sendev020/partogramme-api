<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alert extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'labour_id',
        'user_id',
        'district',
        'poste_de_sante',
        'level',
        'message',
        'resolved',
        'synced',
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
