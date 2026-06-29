<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Labour;
use App\Models\Medicament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicamentController extends Controller
{
    private function visibleLabour(Labour $labour)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isSuperviseur() && $labour->district !== $user->district) {
            return null;
        }

        if (! $user->isAdmin() && ! $user->isSuperviseurRegional() && $labour->user_id !== $user->id) {
            return null;
        }

        return $labour;
    }

    private function visibleMedicament(int $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = Medicament::where('id', $id)->whereHas('labour', function ($query) use ($user) {
            if ($user->isSuperviseur()) {
                $query->where('district', $user->district);
            } elseif (! $user->isAdmin() && ! $user->isSuperviseurRegional()) {
                $query->where('user_id', $user->id);
            }
        });

        return $query->first();
    }

    public function index(Labour $labour)
    {
        if (! $this->visibleLabour($labour)) {
            return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 403);
        }

        return response()->json($labour->medicaments()->orderByDesc('created_at')->get());
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isAnySuperviseur()) {
            return response()->json(['message' => 'Les superviseurs ne peuvent pas enregistrer de médicaments'], 403);
        }

        if (! $user->isAdmin() && ! $user->isSageFemme()) {
            return response()->json(['message' => 'Action réservée aux sages-femmes ou administrateurs'], 403);
        }

        $validated = $request->validate([
            'labour_id' => 'required|exists:labours,id',
            'name' => 'required|string',
            'dose' => 'nullable|string',
            'route' => 'nullable|string',
            'administered_at' => 'nullable|date',
            'indication' => 'nullable|string',
            'notes' => 'nullable|string',
            
        ]);

        $labour = Labour::find($validated['labour_id']);
        if (! $labour || ! $this->visibleLabour($labour)) {
            return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 403);
        }

        $medicament = Medicament::create([
            'labour_id' => $labour->id,
            'patient_id' => $labour->patient_id,
            'user_id' => $user->id,
            'name' => $validated['name'],
            'dose' => $validated['dose'] ?? null,
            'route' => $validated['route'] ?? null,
            'administered_at' => $validated['administered_at'] ?? now(),
            'indication' => $validated['indication'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json($medicament, 201);
    }

    public function show($id)
    {
        $medicament = $this->visibleMedicament($id);
        if (! $medicament) {
            return response()->json(['message' => 'Médicament non trouvé ou non autorisé'], 403);
        }

        return response()->json($medicament);
    }

    public function update(Request $request, $id)
    {
        $medicament = $this->visibleMedicament($id);
        if (! $medicament) {
            return response()->json(['message' => 'Médicament non trouvé ou non autorisé'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'dose' => 'nullable|string',
            'route' => 'nullable|string',
            'administered_at' => 'nullable|date',
            'indication' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $medicament->update($validated);

        return response()->json($medicament);
    }

    public function destroy($id)
    {
        $medicament = $this->visibleMedicament($id);
        if (! $medicament) {
            return response()->json(['message' => 'Médicament non trouvé ou non autorisé'], 403);
        }

        $medicament->delete();

        return response()->json(['message' => 'Médicament supprimé']);
    }
}
