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
            || $user->isSuperviseurRegional()
            || ($user->isSuperviseur() && $labour->district === $user->district)
            || ($labour->user_id === $user->id);

        if (! $authorized) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $observations = Observation::where('labour_id', $labour->id)
            ->orderBy('observed_at')
            ->get(['observed_at', 'dilation']);

        if (! $labour->active_phase_start) {
            return response()->json(['data' => []]);
        }

        $start = Carbon::parse($labour->active_phase_start);

        $points = $observations->map(function ($obs) use ($start) {
            $observedAt = Carbon::parse($obs->observed_at);

            return [
                'hour' => round($start->diffInMinutes($observedAt) / 60, 2),
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
