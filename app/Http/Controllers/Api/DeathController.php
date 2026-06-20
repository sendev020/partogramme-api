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
            'concerner' => 'required|string|in:mere,nouveau_ne',
            'cause_deces' => 'required|string',
            'heure_deces' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $labour = $this->visibleLabour($validated['labour_id']);
        if (! $labour) {
            return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 403);
        }

        // ✅ Hériter user_id/district/poste_de_sante depuis le labour parent
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
