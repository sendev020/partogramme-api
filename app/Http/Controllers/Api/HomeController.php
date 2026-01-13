<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Labour;

class HomeController extends Controller
{
    public function homeData()
    {
        $ongoing_births = Labour::where('status', 'en cours')->count();
        $recent_births = Labour::orderBy('created_at', 'desc')->take(5)->get();
        $protocols = [
            'Protocole OMS 1',
            'Protocole OMS 2',
            'Protocole OMS 3',
        ];

        return response()->json([
            'ongoing_births' => $ongoing_births,
            'recent_births' => $recent_births,
            'protocols' => $protocols,
        ]);
    }
}
