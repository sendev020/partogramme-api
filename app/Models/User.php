<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'district',
        'poste_de_sante',
        'phone',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // ✅ Relations
    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    public function labours()
    {
        return $this->hasMany(Labour::class);
    }

    // ✅ Helpers de rôle
    public function isSageFemme(): bool
    {
        return $this->role === 'sage_femme';
    }

    public function isSuperviseur(): bool
    {
        return $this->role === 'superviseur';
    }

    public function isSuperviseurRegional(): bool
    {
        return $this->role === 'superviseur_regional';
    }

    public function isAnySuperviseur(): bool
    {
        return $this->isSuperviseur() || $this->isSuperviseurRegional();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
