<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table = 'patients'; // Nom exact de la table

    // Permet à Laravel de remplir ces colonnes via create() ou fill()
    protected $fillable = [
        'age',
        'name',
        'parity',
        'gestational_age',
        'risk_factors',
    ];

    
}
