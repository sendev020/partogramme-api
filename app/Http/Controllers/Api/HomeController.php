<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Labour;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class HomeController extends Controller
{
    public function homeData()
    {
         /** @var User|null $user */
        $user = Auth::user();

        $query = Labour::query();

        if ($user->isSuperviseur()) {
            $query->where('district', $user->district);
        } elseif (! $user->isAdmin() && ! $user->isSuperviseurRegional()) {
            $query->where('user_id', $user->id);
        }

        $ongoing_births = (clone $query)->where('status', 'en_cours')->count(); // ✅ corrige aussi "en cours" → "en_cours"
        $recent_births = (clone $query)->orderBy('created_at', 'desc')->take(5)->get();

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
