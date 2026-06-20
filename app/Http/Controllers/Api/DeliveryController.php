<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Labour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

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

        $labour = $this->visibleLabour($validated['labour_id']);
        if (! $labour) {
            return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 403);
        }

        $delivery = Delivery::create($validated);

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
}
