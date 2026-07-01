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
        } elseif (! $user->isAdmin() && ! $user->isSuperviseurRegional()) {
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

        if (! $user->isAdmin() && ! $user->isSuperviseurRegional()) {
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
    if ($user->isAnySuperviseur()) {
        return response()->json(['message' => 'Les superviseurs ne peuvent pas enregistrer un accouchement'], 403);
    }

    // ✅ Blocage explicite : admin ne peut jamais créer
    if ($user->isAdmin()) {
        return response()->json(['message' => 'Les administrateurs ne peuvent pas enregistrer un accouchement'], 403);
    }

    $validated = $request->validate([
        'local_id' => 'nullable|integer',
        'labour_id' => 'required|exists:labours,id',
        'voie' => 'required|string',
        'sexe' => 'required|string|in:M,F',
        'poids' => 'required|numeric',
        'heure_naissance' => 'required|date',
        'notes' => 'nullable|string',
        'complications' => 'nullable|string',
        'soins_administres' => 'required|string',
        'uterotonic_given' => 'nullable|string',
        'uterotonic_type' => 'nullable|string',
        'cord_clamping_time' => 'nullable|string',
        'controlled_cord_traction' => 'nullable|numeric',
        'uterine_massage' => 'nullable|numeric',
        'uterine_tone_checked' => 'nullable|numeric',
        'placenta_complete' => 'nullable|string',
        'estimated_blood_loss_ml' => 'nullable|numeric',
    ]);

    $labour = $this->visibleLabour($validated['labour_id']);
    if (! $labour) {
        return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 403);
    }

    $delivery = Delivery::create([
        'user_id' => $labour->user_id,
        'district' => $labour->district,
        'poste_de_sante' => $labour->poste_de_sante,
        'local_id' => $validated['local_id'] ?? null,
        'labour_id' => $labour->id,
        'voie' => $validated['voie'],
        'sexe' => $validated['sexe'],
        'poids' => $validated['poids'],
        'heure_naissance' => $validated['heure_naissance'],
        'notes' => $validated['notes'] ?? null,
        'complications' => $validated['complications'] ?? null,
        'soins_administres' => $validated['soins_administres'],
        'uterotonic_given' => $validated['uterotonic_given'] ?? null,
        'uterotonic_type' => $validated['uterotonic_type'] ?? null,
        'cord_clamping_time' => $validated['cord_clamping_time'] ?? null,
        'controlled_cord_traction' => $validated['controlled_cord_traction'] ?? null,
        'uterine_massage' => $validated['uterine_massage'] ?? null,
        'uterine_tone_checked' => $validated['uterine_tone_checked'] ?? null,
        'placenta_complete' => $validated['placenta_complete'] ?? null,
        'estimated_blood_loss_ml' => $validated['estimated_blood_loss_ml'] ?? null,
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
