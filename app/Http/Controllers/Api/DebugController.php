<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function checkData(Request $request)
    {
        $users = User::select('id', 'email', 'role', 'district', 'poste_de_sante')
            ->get();

        // ✅ withTrashed() inclut les lignes soft-deletées (deleted_at non null)
        // si le modèle Patient utilise le trait SoftDeletes. Si ce trait
        // n'est pas utilisé, withTrashed() lèvera une erreur — dans ce cas
        // le champ deleted_at n'existe probablement pas comme soft delete
        // Eloquent, et la piste est à écarter.
        $patientsQuery = Patient::query();
        $usesSoftDeletes = method_exists(new Patient(), 'trashed');

        if ($usesSoftDeletes) {
            $patientsQuery->withTrashed();
        }

        $patients = $patientsQuery
            ->select('id', 'name', 'user_id', 'district', 'poste_de_sante', 'created_at', 'deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'uses_soft_deletes' => $usesSoftDeletes,
            'total_users' => $users->count(),
            'users' => $users,
            'total_patients_including_trashed' => $patients->count(),
            'patients' => $patients,
        ], 200, [], JSON_PRETTY_PRINT);
    }
}
