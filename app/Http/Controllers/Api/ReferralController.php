<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\Labour;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    /**
     * Enregistrer une référence et mettre le labour à "refere"
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'labour_id' => 'required|exists:labours,id',
            'hospital' => 'required|string|max:255',
            'reason' => 'required|string',
            'referral_time' => 'required|date',
            'transport_mode' => 'required|string|max:50',
        ]);

        // 1️⃣ Création de la référence
        $referral = Referral::create($validated);

        // 2️⃣ Mise à jour du statut du labour
        $labour = Labour::findOrFail($validated['labour_id']);
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
