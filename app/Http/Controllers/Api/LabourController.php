<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Labour;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LabourController extends Controller
{

    private function applyVisibilityScope($query, Request $request)
{
    /** @var User $user */
    $user = Auth::user();

    if ($user->isAdmin() || $user->isSuperviseurRegional()) {
        // ✅ Admin : libre, filtre optionnel par district ET/OU poste_de_sante
        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }
        if ($request->filled('poste_de_sante')) {
            $query->where('poste_de_sante', $request->poste_de_sante);
        }
        return $query;
    }

    if ($user->isSuperviseur()) {
        // ✅ Superviseur : toujours limité à son district, filtre optionnel par poste_de_sante
        $query->where('district', $user->district);
        if ($request->filled('poste_de_sante')) {
            $query->where('poste_de_sante', $request->poste_de_sante);
        }
        return $query;
    }

    // sage_femme : ses propres données uniquement, pas de filtre pertinent
    return $query->where('user_id', $user->id);
}

    // Accouchements en cours
    public function ongoing(Request $request)
    {
        $query = Labour::with('patient')->where('status', 'en_cours');
        $query = $this->applyVisibilityScope($query, $request);

        $labours = $query->orderBy('start_time', 'asc')->get();

        return response()->json(['labours' => $labours]);
    }

    // Détail d'un accouchement
    public function show($id, Request $request)
    {
        $query = Labour::with('patient')->where('id', $id);
        $query = $this->applyVisibilityScope($query, $request);

        $labour = $query->first();

        if (! $labour) {
            return response()->json(['message' => 'Accouchement non trouvé'], 404);
        }

        return response()->json($labour);
    }

    // Clôturer un accouchement
    public function close($labourId, Request $request)
    {
        $query = Labour::where('id', $labourId);
        $query = $this->applyVisibilityScope($query, $request);

        $labour = $query->first();

        if (! $labour) {
            return response()->json(['message' => 'Accouchement non trouvé'], 404);
        }

        $labour->status = 'termine';
        $labour->end_time = now();
        $labour->save();

        return response()->json(['message' => 'Accouchement clôturé', 'labour' => $labour]);
    }

    // Accouchement actif d'un patient
    public function active(Patient $patient, Request $request)
    {
        $query = Labour::where('patient_id', $patient->id)->where('status', 'en_cours');
        $query = $this->applyVisibilityScope($query, $request);

        $labour = $query->first();

        if (! $labour) {
            return response()->json(null, 204);
        }

        return response()->json($labour);
    }

    // Alertes liées à un accouchement
    public function alerts($labourId, Request $request)
    {
        // ✅ Vérifier que le labour est visible par l'utilisateur avant de montrer ses alertes
        $labourQuery = Labour::where('id', $labourId);
        $labourQuery = $this->applyVisibilityScope($labourQuery, $request);

        if (! $labourQuery->exists()) {
            return response()->json(['message' => 'Accouchement non trouvé'], 404);
        }

        $alerts = Alert::where('labour_id', $labourId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['alerts' => $alerts]);
    }

    // ✅ Fin d'un labour
    public function finish(Request $request, $id)
    {
        $query = Labour::where('id', $id);
        $query = $this->applyVisibilityScope($query, $request);

        $labour = $query->first();

        if (! $labour) {
            return response()->json(['message' => 'Accouchement non trouvé'], 404);
        }

        $labour->status = $request->status;
        $labour->save();

        return response()->json($labour);
    }


    //     return response()->json($formatted);
    //}
    public function monthlyStats(Request $request)
{
    /** @var User|null $user */
        $user = Auth::user();

    $query = DB::table('labours');

    if ($user->isAdmin() || $user->isSuperviseurRegional()) {
        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }
        if ($request->filled('poste_de_sante')) {
            $query->where('poste_de_sante', $request->poste_de_sante);
        }
    } elseif ($user->isSuperviseur()) {
        $query->where('district', $user->district);
        if ($request->filled('poste_de_sante')) {
            $query->where('poste_de_sante', $request->poste_de_sante);
        }
    } else {
        $query->where('user_id', $user->id);
    }

    $stats = $query
        ->selectRaw("
            DATE_FORMAT(start_time, '%Y-%m') as month,
            SUM(CASE WHEN status = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
            SUM(CASE WHEN status = 'refere' THEN 1 ELSE 0 END) as refere,
            SUM(CASE WHEN status = 'termine' THEN 1 ELSE 0 END) as termine,
            SUM(CASE WHEN status = 'delivery' THEN 1 ELSE 0 END) as delivery,
            SUM(CASE WHEN status = 'death' THEN 1 ELSE 0 END) as death
        ")
        ->groupBy('month')
        ->orderBy('month')
        ->get();

    $formatted = $stats->map(function ($s) {
        return [
            'month' => date('M', strtotime($s->month.'-01')),
            'en_cours' => (int) $s->en_cours,
            'refere' => (int) $s->refere,
            'termine' => (int) $s->termine,
            'delivery' => (int) $s->delivery,
            'death' => (int) $s->death,
        ];
    });

    return response()->json($formatted);
}

public function store(Request $request)
{
    /** @var User $user */
    $user = Auth::user();

    // ✅ Blocage explicite : superviseur ne peut jamais créer
    if ($user->isAnySuperviseur()) {
        return response()->json(['message' => 'Les superviseurs ne peuvent pas créer de labour'], 403);
    }

    // ✅ Blocage explicite : admin ne peut jamais créer
    if ($user->isAdmin()) {
        return response()->json(['message' => 'Les administrateurs ne peuvent pas créer de labour'], 403);
    }

    $request->validate([
        'patient_id' => 'required|exists:patients,id',
        'start_time' => 'required|date',
    ]);

    $existingLabour = Labour::where('patient_id', $request->patient_id)
        ->where('status', 'en_cours')
        ->first();

    if ($existingLabour) {
        return response()->json([
            'message' => 'Un labour est déjà en cours pour cette patiente',
            'labour' => $existingLabour,
        ], 409);
    }

    $labour = Labour::create([
        'patient_id' => $request->patient_id,
        'user_id' => $user->id,
        'district' => $user->district,
        'poste_de_sante' => $user->poste_de_sante,
        'start_time' => $request->start_time,
        'status' => 'en_cours',
        'labor_onset' => $request->labor_onset ?? null,
        'active_phase_diagnosis_at' => $request->active_phase_diagnosis_at ?? null,
        'membranes_ruptured_at' => $request->membranes_ruptured_at ?? null,
        'membranes_rupture_at' => $request->membranes_rupture_at ?? null,
        'membranes_rupture_unknown' => $request->membranes_rupture_unknown ?? null,
        'operation' => $request->operation ?? null,
    ]);

    return response()->json($labour, 201);
}

public function update(Request $request, $id)
{
    /** @var User $user */
    $user = Auth::user();

    // ✅ Blocage explicite : superviseur ne peut jamais modifier
    if ($user->isAnySuperviseur()) {
        return response()->json(['message' => 'Les superviseurs ne peuvent pas modifier un labour'], 403);
    }

    // ✅ Blocage explicite : admin ne peut jamais modifier
    if ($user->isAdmin()) {
        return response()->json(['message' => 'Les administrateurs ne peuvent pas modifier un labour'], 403);
    }

    if (! $user->isSageFemme()) {
        return response()->json(['message' => 'Action réservée aux sages-femmes'], 403);
    }

    $labour = Labour::where('id', $id)
        ->where('user_id', $user->id)
        ->first();

    if (! $labour) {
        return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 404);
    }

    $validated = $request->validate([
        'start_time' => 'sometimes|date',
        'end_time' => 'nullable|date',
        'status' => 'sometimes|in:en_cours,termine,refere,delivery,death',
    ]);

    $labour->update($validated);

    return response()->json($labour);
}

public function destroy($id)
{
    /** @var User $user */
    $user = Auth::user();

    // ✅ Blocage explicite : superviseur ne peut jamais supprimer
    if ($user->isAnySuperviseur()) {
        return response()->json(['message' => 'Les superviseurs ne peuvent pas supprimer un labour'], 403);
    }

    // ✅ Au-delà de ce point, seuls sage_femme et admin sont admis
    if (! $user->isSageFemme() && ! $user->isAdmin()) {
        return response()->json(['message' => 'Action non autorisée'], 403);
    }

    $query = Labour::where('id', $id);

    if (! $user->isAdmin()) {
        $query->where('user_id', $user->id);
    }

    $labour = $query->first();

    if (! $labour) {
        return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 404);
    }

    $labour->observations()->delete();
    $labour->delete();

    return response()->json(['message' => 'Accouchement supprimé']);
}

public function allLabours(Request $request)
{
    $query = Labour::with('patient');
    $query = $this->applyVisibilityScope($query, $request);

    $labours = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'success' => true,
        'data' => $labours,
    ]);
}

public function index(Request $request)
{
    $query = Labour::query();
    $query = $this->applyVisibilityScope($query, $request);

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('date')) {
        $query->whereDate('start_time', $request->date);
    }

    $labours = $query->with('patient')->orderBy('start_time', 'desc')->get();

    return response()->json($labours);
}
}
