<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Death;
use App\Models\Labour;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeathController extends Controller
{
    public function index(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $query = Death::query();

        if ($user->isSuperviseur()) {
            $query->where('district', $user->district);
        } elseif (! $user->isAdmin() && ! $user->isSuperviseurRegional()) {
            $query->where('user_id', $user->id);
        }

        $deaths = $query
            ->orderByDesc('heure_deces')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deaths,
        ]);
    }

    private function visibleLabour($labourId)
    {
        /** @var User|null $user */
        $user = Auth::user();
        $query = Labour::where('id', $labourId);

        if ($user->isSuperviseur()) {
            $query->where('district', $user->district);
        } elseif (! $user->isAdmin() && ! $user->isSuperviseurRegional()) {
            $query->where('user_id', $user->id);
        }

        return $query->first();
    }

    public function store(Request $request)
{
    /** @var User $user */
    $user = Auth::user();

    // ✅ Blocage explicite : superviseur ne peut jamais créer
    if ($user->isAnySuperviseur()) {
        return response()->json(['message' => 'Les superviseurs ne peuvent pas enregistrer un décès'], 403);
    }

    // ✅ Blocage explicite : admin ne peut jamais créer
    if ($user->isAdmin()) {
        return response()->json(['message' => 'Les administrateurs ne peuvent pas enregistrer un décès'], 403);
    }

    $validated = $request->validate([
        'labour_id' => 'required|exists:labours,id',
        'concerner' => 'required|string|in:mere,nouveau_ne',
        'cause_deces' => 'required|string',
        'heure_deces' => 'required|date',
        'notes' => 'nullable|string',
    ]);

    $labour = $this->visibleLabour($validated['labour_id']);
    if (! $labour) {
        return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 403);
    }

    $death = Death::create([
        ...$validated,
        'user_id' => $labour->user_id,
        'district' => $labour->district,
        'poste_de_sante' => $labour->poste_de_sante,
    ]);

    $labour->status = 'death';
    $labour->end_time = $validated['heure_deces'] ?? now();
    $labour->save();

    return response()->json([
        'success' => true,
        'message' => 'Décès enregistré avec succès',
        'data' => [
            'death' => $death,
            'labour' => $labour,
        ],
    ], 201);
}
}
