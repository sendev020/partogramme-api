<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Labour;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    // 👶 Ajouter un nouveau delivery
    public function store(Request $request)
    {
        $validated = $request->validate([
            'labour_id' => 'required|exists:labours,id',
            'voie' => 'required|string',
            'sexe' => 'required|string|in:M,F',
            'poids' => 'required|numeric',
            'heure_naissance' => 'required|date',
            'notes' => 'nullable|string',
            'complications' => 'nullable|string',
            'soins_administres' => 'required|string',
        ]);

        // 1️⃣ Création de la référence
        $delivery = Delivery::create($validated);

        // 2️⃣ Mise à jour du statut du labour
        $labour = Labour::findOrFail($validated['labour_id']);
        $labour->status = 'delivery';
        $labour->save();

        return response()->json([
            'success' => true,
            'message' => 'Accouchement enregistrée avec succès',
            'data' => [
                'delivery' => $delivery,
                'labour' => $labour,
            ],
        ], 201);
    }

    // 📦 Lister tous les deliveries
    public function index()
    {
        $deliveries = Delivery::with('labour')->get();

        return response()->json([
            'data' => $deliveries
        ]);
    }

    // 📦 Récupérer un delivery spécifique
    public function show($id)
    {
        $delivery = Delivery::with('labour')->findOrFail($id);

        return response()->json([
            'data' => $delivery
        ]);
    }
}
