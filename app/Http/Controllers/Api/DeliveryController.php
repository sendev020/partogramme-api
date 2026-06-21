<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Labour;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
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

    private function visibilityScope($query)
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user->isSuperviseur()) {
            return $query->whereHas('labour', function ($q) use ($user) {
                $q->where('district', $user->district);
            });
        }

        if (! $user->isAdmin()) {
            return $query->whereHas('labour', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query;
    }

    public function index()
    {
        $query = Delivery::with('labour');
        $query = $this->visibilityScope($query);

        return response()->json(['data' => $query->get()]);
    }

    public function show($id)
    {
        $query = Delivery::with('labour')->where('id', $id);
        $query = $this->visibilityScope($query);

        $delivery = $query->first();

        if (! $delivery) {
            return response()->json(['message' => 'Non trouvé ou non autorisé'], 404);
        }

        return response()->json(['data' => $delivery]);
    }

    public function store(Request $request)
{
    /** @var User $user */
    $user = Auth::user();

    // ✅ Blocage explicite : superviseur ne peut jamais créer
    if ($user->isSuperviseur()) {
        return response()->json(['message' => 'Les superviseurs ne peuvent pas enregistrer un accouchement'], 403);
    }

    // ✅ Blocage explicite : admin ne peut jamais créer
    if ($user->isAdmin()) {
        return response()->json(['message' => 'Les administrateurs ne peuvent pas enregistrer un accouchement'], 403);
    }

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

    $labour = $this->visibleLabour($validated['labour_id']);
    if (! $labour) {
        return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 403);
    }

    $delivery = Delivery::create([
        ...$validated,
        'user_id' => $labour->user_id,
        'district' => $labour->district,
        'poste_de_sante' => $labour->poste_de_sante,
    ]);

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
}
