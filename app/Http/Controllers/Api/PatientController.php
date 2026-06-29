<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{

    private function applyVisibilityScope($query, Request $request)
{
    /** @var User $user */
    $user = Auth::user();

    if ($user->isAdmin() || $user->isSuperviseurRegional()) {
        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }
        if ($request->filled('poste_de_sante')) {
            $query->where('poste_de_sante', $request->poste_de_sante);
        }
        return $query;
    }

    if ($user->isSuperviseur()) {
        $query->where('district', $user->district);
        if ($request->filled('poste_de_sante')) {
            $query->where('poste_de_sante', $request->poste_de_sante);
        }
        return $query;
    }

    return $query->where('user_id', $user->id);
}

public function index(Request $request)
{
    $query = Patient::query();
    $query = $this->applyVisibilityScope($query, $request);

    return $query->orderBy('created_at', 'desc')->get();
}


    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // ✅ Blocage explicite : superviseur ne peut jamais créer
        if ($user->isAnySuperviseur()) {
            return response()->json(['message' => 'Les superviseurs ne peuvent pas créer de patiente'], 403);
        }

        // ✅ Blocage explicite : admin ne peut jamais créer
        if ($user->isAdmin()) {
            return response()->json(['message' => 'Les administrateurs ne peuvent pas créer de patiente'], 403);
        }

        if (! $user->isSageFemme()) {
            return response()->json(['message' => 'Action réservée aux sages-femmes'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'age' => 'required|integer|min:10|max:60',
            'parity' => 'required|integer|min:0',
            'gestational_age' => 'required|numeric|min:1',
            'risk_factors' => 'nullable|string',
        ]);

        $validated['user_id'] = $user->id;
        $validated['district'] = $user->district;
        $validated['poste_de_sante'] = $user->poste_de_sante;

        $patient = Patient::create($validated);

        return response()->json($patient, 201);
    }

    public function update(Request $request, $id)
    {
        /** @var User $user */
        $user = Auth::user();

        // ✅ Blocage explicite : superviseur ne peut jamais modifier
        if ($user->isAnySuperviseur()) {
            return response()->json(['message' => 'Les superviseurs ne peuvent pas modifier une patiente'], 403);
        }

        // ✅ Blocage explicite : admin ne peut jamais modifier
        if ($user->isAdmin()) {
            return response()->json(['message' => 'Les administrateurs ne peuvent pas modifier une patiente'], 403);
        }

        if (! $user->isSageFemme()) {
            return response()->json(['message' => 'Action réservée aux sages-femmes'], 403);
        }

        $patient = Patient::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $patient) {
            return response()->json(['message' => 'Patiente non trouvée ou non autorisée'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'age' => 'sometimes|integer|min:10|max:60',
            'parity' => 'sometimes|integer|min:0',
            'gestational_age' => 'sometimes|numeric|min:1',
            'risk_factors' => 'nullable|string',
        ]);

        $patient->update($validated);

        return response()->json($patient);
    }

    public function destroy($id)
    {
        /** @var User $user */
        $user = Auth::user();

        // ✅ Blocage explicite : superviseur ne peut jamais supprimer
        if ($user->isAnySuperviseur()) {
            return response()->json(['message' => 'Les superviseurs ne peuvent pas supprimer une patiente'], 403);
        }

        // ✅ Au-delà de ce point, seuls sage_femme et admin sont admis
        if (! $user->isSageFemme() && ! $user->isAdmin()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $query = Patient::where('id', $id);

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $patient = $query->first();

        if (! $patient) {
            return response()->json(['message' => 'Patiente non trouvée ou non autorisée'], 404);
        }

        foreach ($patient->labours as $labour) {
            $labour->observations()->delete();
            $labour->delete();
        }

        $patient->delete();

        return response()->json(['message' => 'Patiente supprimée']);
    }
}
