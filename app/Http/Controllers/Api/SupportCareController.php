<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Labour;
use App\Models\SupportCare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportCareController extends Controller
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

    private function visibleSupportCare(int $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = SupportCare::where('id', $id)->whereHas('labour', function ($query) use ($user) {
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

        return response()->json($labour->supportCares()->orderByDesc('created_at')->get());
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isAnySuperviseur()) {
            return response()->json(['message' => 'Les superviseurs ne peuvent pas enregistrer les soins de soutien'], 403);
        }

        if (! $user->isAdmin() && ! $user->isSageFemme()) {
            return response()->json(['message' => 'Action réservée aux sages-femmes ou administrateurs'], 403);
        }

        $validated = $request->validate([
            'labour_id' => 'required|exists:labours,id',
            'companion_present' => 'boolean',
            'oral_fluids' => 'boolean',
            'position' => 'nullable|string',
            'pain_relief' => 'boolean',
            'notes' => 'nullable|string',
            'recorded_at' => 'nullable|date',
        ]);

        $labour = Labour::find($validated['labour_id']);
        if (! $labour || ! $this->visibleLabour($labour)) {
            return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 403);
        }

        $supportCare = SupportCare::create([
            'labour_id' => $labour->id,
            'patient_id' => $labour->patient_id,
            'user_id' => $user->id,
            'companion_present' => $validated['companion_present'] ?? false,
            'oral_fluids' => $validated['oral_fluids'] ?? false,
            'position' => $validated['position'] ?? null,
            'pain_relief' => $validated['pain_relief'] ?? false,
            'notes' => $validated['notes'] ?? null,
            'recorded_at' => $validated['recorded_at'] ?? now(),
        ]);

        return response()->json($supportCare, 201);
    }

    public function show($id)
    {
        $supportCare = $this->visibleSupportCare($id);
        if (! $supportCare) {
            return response()->json(['message' => 'Soins de soutien non trouvés ou non autorisés'], 403);
        }

        return response()->json($supportCare);
    }

    public function update(Request $request, $id)
    {
        $supportCare = $this->visibleSupportCare($id);
        if (! $supportCare) {
            return response()->json(['message' => 'Soins de soutien non trouvés ou non autorisés'], 403);
        }

        $validated = $request->validate([
            'companion_present' => 'boolean',
            'oral_fluids' => 'boolean',
            'position' => 'nullable|string',
            'pain_relief' => 'boolean',
            'notes' => 'nullable|string',
            'recorded_at' => 'nullable|date',
        ]);

        $supportCare->update($validated);

        return response()->json($supportCare);
    }

    public function destroy($id)
    {
        $supportCare = $this->visibleSupportCare($id);
        if (! $supportCare) {
            return response()->json(['message' => 'Soins de soutien non trouvés ou non autorisés'], 403);
        }

        $supportCare->delete();

        return response()->json(['message' => 'Soins de soutien supprimés']);
    }
}
