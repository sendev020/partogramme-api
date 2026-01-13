<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Death;
use App\Models\Labour;
use Illuminate\Http\Request;

class DeathController extends Controller
{
    /**
     * Enregistrer un décès (mère ou nouveau-né)
     * et terminer automatiquement le labour
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'labour_id' => 'required|exists:labours,id',
            'concerner' => 'required|string|in:mere,nouveau_ne',
            'cause_deces' => 'required|string',
            'heure_deces' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // 1️⃣ Création du décès
        $death = Death::create($validated);

        // 2️⃣ Mise à jour automatique du labour
        $labour = Labour::findOrFail($validated['labour_id']);
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

