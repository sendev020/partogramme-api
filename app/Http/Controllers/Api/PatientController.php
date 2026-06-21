<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{
    private function applyVisibilityScope($query)
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isSuperviseur()) {
            return $query->where('district', $user->district);
        }

        return $query->where('user_id', $user->id);
    }

    public function index()
    {
        $query = Patient::query();
        $query = $this->applyVisibilityScope($query);

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // ✅ Seule la sage-femme peut créer
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

        if (! $user->isSageFemme()) {
            return response()->json(['message' => 'Action réservée aux sages-femmes'], 403);
        }

        $query = Patient::where('id', $id);
        $query = $this->applyVisibilityScope($query);
        $patient = $query->first();

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

        // ✅ Sage-femme (ses propres patientes) OU admin (toutes)
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

        // ✅ Cascade : soft-delete des labours + observations liés
        foreach ($patient->labours as $labour) {
            $labour->observations()->delete();
            $labour->delete();
        }

        $patient->delete();

        return response()->json(['message' => 'Patiente supprimée']);
    }
}
