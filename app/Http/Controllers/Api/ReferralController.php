<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Labour;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

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
        $validated = $request->validate([
            'labour_id' => 'required|exists:labours,id',
            'hospital' => 'required|string|max:255',
            'reason' => 'required|string',
            'referral_time' => 'required|date',
            'transport_mode' => 'required|string|max:50',
        ]);

        // ✅ Vérifier que l'utilisateur a le droit d'agir sur ce labour
        $labour = $this->visibleLabour($validated['labour_id']);
        if (! $labour) {
            return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 403);
        }

        $referral = Referral::create($validated);

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
