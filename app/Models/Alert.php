<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'labour_id', 'level', 'message', 'resolved',
    ];

    public function labour()
    {
        return $this->belongsTo(Labour::class);
    }
}
