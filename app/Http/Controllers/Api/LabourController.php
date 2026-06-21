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
    /**
     * ✅ Applique le filtrage de visibilité selon le rôle de l'utilisateur connecté
     * - sage_femme  → uniquement ses propres labours
     * - superviseur → tous les labours de son district
     * - admin       → tous les labours, tous districts
     */
    private function applyVisibilityScope($query)
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            // Aucun filtre supplémentaire
            return $query;
        }

        if ($user->isSuperviseur()) {
            return $query->where('district', $user->district);
        }

        // sage_femme (par défaut) → uniquement ses propres labours
        return $query->where('user_id', $user->id);
    }

    // Liste complète (alias) — respecte aussi le scope
    public function allLabours()
    {
        $query = Labour::with('patient');
        $query = $this->applyVisibilityScope($query);

        $labours = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $labours,
        ]);
    }

    // Liste filtrée des accouchements
    public function index(Request $request)
    {
        $query = Labour::query();
        $query = $this->applyVisibilityScope($query);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('start_time', $request->date);
        }

        $labours = $query->with('patient')->orderBy('start_time', 'desc')->get();

        return response()->json($labours);
    }

    // Accouchements en cours
    public function ongoing()
    {
        $query = Labour::with('patient')->where('status', 'en_cours');
        $query = $this->applyVisibilityScope($query);

        $labours = $query->orderBy('start_time', 'asc')->get();

        return response()->json(['labours' => $labours]);
    }

    // Détail d'un accouchement
    public function show($id)
    {
        $query = Labour::with('patient')->where('id', $id);
        $query = $this->applyVisibilityScope($query);

        $labour = $query->first();

        if (! $labour) {
            return response()->json(['message' => 'Accouchement non trouvé'], 404);
        }

        return response()->json($labour);
    }

    // Clôturer un accouchement
    public function close($labourId)
    {
        $query = Labour::where('id', $labourId);
        $query = $this->applyVisibilityScope($query);

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
    public function active(Patient $patient)
    {
        $query = Labour::where('patient_id', $patient->id)->where('status', 'en_cours');
        $query = $this->applyVisibilityScope($query);

        $labour = $query->first();

        if (! $labour) {
            return response()->json(null, 204);
        }

        return response()->json($labour);
    }

    // Alertes liées à un accouchement
    public function alerts($labourId)
    {
        // ✅ Vérifier que le labour est visible par l'utilisateur avant de montrer ses alertes
        $labourQuery = Labour::where('id', $labourId);
        $labourQuery = $this->applyVisibilityScope($labourQuery);

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
        $query = $this->applyVisibilityScope($query);

        $labour = $query->first();

        if (! $labour) {
            return response()->json(['message' => 'Accouchement non trouvé'], 404);
        }

        $labour->status = $request->status;
        $labour->save();

        return response()->json($labour);
    }

    public function monthlyStats()
    {
        /** @var User|null $user */
        $user = Auth::user();

        $query = DB::table('labours');

        if ($user->isSuperviseur()) {
            $query->where('district', $user->district);
        } elseif (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        // admin → pas de filtre

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
    if ($user->isSuperviseur()) {
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
    ]);

    return response()->json($labour, 201);
}

public function update(Request $request, $id)
{
    /** @var User $user */
    $user = Auth::user();

    // ✅ Blocage explicite : superviseur ne peut jamais modifier
    if ($user->isSuperviseur()) {
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
    if ($user->isSuperviseur()) {
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
}
