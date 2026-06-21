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
    private function visibleLabour($labourId)
    {
        /** @var User|null $user */
        $user = Auth::user();
        $query = Labour::where('id', $labourId);

        if ($user->isSuperviseur()) {
            $query->where('district', $user->district);
        } elseif (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query->first();
    }

    public function store(Request $request)
{
    /** @var User $user */
    $user = Auth::user();

    // ✅ Blocage explicite : superviseur ne peut jamais créer
    if ($user->isSuperviseur()) {
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
