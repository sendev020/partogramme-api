<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    /// ⚠️ Route TEMPORAIRE de diagnostic — à supprimer après usage.
    /// Aucune authentification requise pour permettre une vérification
    /// rapide sans outil externe (Postman/psql). Ne retourne aucune
    /// donnée sensible comme les mots de passe.
    public function checkData(Request $request)
    {
        $users = User::select('id', 'email', 'role', 'district', 'poste_de_sante')
            ->get();

        $patients = Patient::select('id', 'name', 'user_id', 'district', 'poste_de_sante', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'total_users' => $users->count(),
            'users' => $users,
            'total_patients' => $patients->count(),
            'patients' => $patients,
        ], 200, [], JSON_PRETTY_PRINT);
    }
}
