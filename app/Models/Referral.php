<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    protected $fillable = [
        'labour_id','hospital', 'reason', 'referral_time','transport_mode',
    ];

     // 🔗 Relations
    public function labour()
    {
        return $this->belongsTo(Labour::class);
    }
}
