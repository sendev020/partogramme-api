<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class PatientController extends Controller
{
    private function applyVisibilityScope($query)
    {
        //$user = auth()->user();
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
        $validated = $request->validate([
            'name' => 'required|string',
            'age' => 'required|integer|min:10|max:60',
            'parity' => 'required|integer|min:0',
            'gestational_age' => 'required|numeric|min:1',
            'risk_factors' => 'nullable|string',
        ]);

        $user = Auth::user();

        // ✅ Lier automatiquement à l'utilisateur connecté et son district
        $validated['user_id'] = $user->id;
        $validated['district'] = $user->district;
        $validated['poste_de_sante'] = $user->poste_de_sante;

        $patient = Patient::create($validated);

        return response()->json($patient, 201);
    }
}
