<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Referral extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'labour_id','user_id','district','poste_de_sante','hospital', 'reason', 'referral_time','transport_mode',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

     // 🔗 Relations
    public function labour()
    {
        return $this->belongsTo(Labour::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
