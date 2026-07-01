<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Labour;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
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

        $query = Referral::query();

        if ($user->isSuperviseur()) {
            $query->where('district', $user->district);
        } elseif (! $user->isAdmin() && ! $user->isSuperviseurRegional()) {
            $query->where('user_id', $user->id);
        }

        $referrals = $query
            ->orderByDesc('referral_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $referrals,
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
    /** @var User|null $user */
    $user = Auth::user();

    if (! $user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated',
        ], 401);
    }

    // ✅ Blocage explicite : superviseur ne peut jamais créer
    if ($user->isAnySuperviseur()) {
        return response()->json(['message' => 'Les superviseurs ne peuvent pas enregistrer une référence'], 403);
    }

    // ✅ Blocage explicite : admin ne peut jamais créer
    if ($user->isAdmin()) {
        return response()->json(['message' => 'Les administrateurs ne peuvent pas enregistrer une référence'], 403);
    }

    $validated = $request->validate([
        'labour_id' => 'required|exists:labours,id',
        'hospital' => 'required|string|max:255',
        'reason' => 'required|string',
        'referral_time' => 'required|date',
        'transport_mode' => 'required|string|max:50',
    ]);

    $labour = $this->visibleLabour($validated['labour_id']);
    if (! $labour) {
        return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 403);
    }

    $referral = Referral::create([
        ...$validated,
        'user_id' => $labour->user_id,
        'district' => $labour->district,
        'poste_de_sante' => $labour->poste_de_sante,
    ]);

    $labour->status = 'refere';
    $labour->hospital_referred_to = $validated['hospital'];
    $labour->save();

    return response()->json([
        'success' => true,
        'message' => 'Référence enregistrée avec succès',
        'data' => [
            'referral' => $referral,
            'labour' => $labour,
        ],
    ], 201);
}
}
