<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Labour;
use App\Models\Observation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class PartographController extends Controller
{
    public function show(Labour $labour)
    {
         /** @var User|null $user */
        $user = Auth::user();

        // ✅ Vérifier la visibilité avant de renvoyer les données
        $authorized = $user->isAdmin()
            || ($user->isSuperviseur() && $labour->district === $user->district)
            || ($labour->user_id === $user->id);

        if (! $authorized) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $observations = Observation::where('labour_id', $labour->id)
            ->orderBy('created_at')
            ->get(['created_at', 'dilation']);

        if (! $labour->active_phase_start) {
            return response()->json(['data' => []]);
        }

        $start = Carbon::parse($labour->active_phase_start);

        $points = $observations->map(function ($obs) use ($start) {
            return [
                'hour' => round($start->diffInMinutes($obs->created_at) / 60, 2),
                'dilation' => $obs->dilation,
            ];
        });

        return response()->json([
            'labour_id' => $labour->id,
            'active_phase_start' => $labour->active_phase_start,
            'points' => $points,
            'reference_rate' => 1,
        ]);
    }
}
