<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Labour;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ObservationController extends Controller
{
    private function visibleLabour($labourId)
    {
        /** @var User|null $user */
        $user = Auth::user();
        $query = Labour::where('id', $labourId);

        if ($user->isSuperviseur()) {
            $query->where('district', $user->district);
        } elseif (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query->first();
    }

    public function index($labourId)
    {
        $labour = $this->visibleLabour($labourId);

        if (! $labour) {
            return response()->json(['message' => 'Accouchement non trouvé'], 404);
        }

        $observations = $labour->observations()
            ->orderBy('observed_at', 'desc')
            ->get();

        return response()->json(['observations' => $observations]);
    }

    private function checkAlerts($observation)
    {
        $alerts = [];

        if ($observation->fcf < 120 || $observation->fcf > 160) {
            $alerts[] = [
                'labour_id' => $observation->labour_id,
                'level' => 'rouge',
                'message' => 'Anomalie du rythme cardiaque fœtal (FCF)',
            ];
        }

        if ($observation->dilation < 1) {
            $alerts[] = [
                'labour_id' => $observation->labour_id,
                'level' => 'orange',
                'message' => 'Dilatation faible',
            ];
        }

        if ($observation->temperature != null &&
            ($observation->temperature < 36 || $observation->temperature > 38)) {
            $alerts[] = [
                'labour_id' => $observation->labour_id,
                'level' => 'orange',
                'message' => 'Température anormale',
            ];
        }

        if ($observation->systolic_bp >= 140 || $observation->diastolic_bp >= 90) {
            $alerts[] = [
                'labour_id' => $observation->labour_id,
                'level' => 'orange',
                'message' => 'Hypertension maternelle',
            ];
        }

        if ($observation->station != null && ($observation->station < -3 || $observation->station > 3)) {
            $alerts[] = [
                'labour_id' => $observation->labour_id,
                'level' => 'orange',
                'message' => 'Station anormale',
            ];
        }

        foreach ($alerts as $alert) {
            \App\Models\Alert::create($alert);
        }
    }

    public function sync(Request $request)
    {
        $since = $request->query('since');
        /** @var User|null $user */
        $user = Auth::user();

        $query = Observation::where('updated_at', '>', $since);

        if ($user->isSuperviseur()) {
            $query->whereHas('labour', function ($q) use ($user) {
                $q->where('district', $user->district);
            });
        } elseif (! $user->isAdmin()) {
            $query->whereHas('labour', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query->get();
    }

public function store(Request $request)
{
    /** @var User $user */
    $user = Auth::user();

    // ✅ Blocage explicite : superviseur ne peut jamais créer
    if ($user->isSuperviseur()) {
        return response()->json(['message' => 'Les superviseurs ne peuvent pas ajouter d\'observation'], 403);
    }

    // ✅ Blocage explicite : admin ne peut jamais créer
    if ($user->isAdmin()) {
        return response()->json(['message' => 'Les administrateurs ne peuvent pas ajouter d\'observation'], 403);
    }

    $data = $request->validate([
        'local_id' => 'nullable|integer',
        'labour_id' => 'required|exists:labours,id',

        'dilation' => 'nullable|numeric|min:0|max:10',
        'contractions' => 'nullable|integer|min:0',
        'fcf' => 'nullable|integer|min:60|max:220',
        'station' => 'nullable|integer|min:-3|max:3',

        'systolic_bp' => 'nullable|integer',
        'diastolic_bp' => 'nullable|integer',
        'temperature' => 'nullable|numeric',
        'pulse' => 'nullable|integer',

        'notes' => 'nullable|string',
        'observed_at' => 'nullable|date',
        'updated_at' => 'nullable|date',
    ]);

    $labour = $this->visibleLabour($data['labour_id']);
    if (! $labour) {
        return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 403);
    }

    $observation = Observation::create([
        'labour_id' => $data['labour_id'],
        'user_id' => $labour->user_id,
        'district' => $labour->district,
        'poste_de_sante' => $labour->poste_de_sante,
        'dilation' => $data['dilation'] ?? null,
        'contractions' => $data['contractions'] ?? null,
        'fcf' => $data['fcf'] ?? null,
        'station' => $data['station'] ?? null,

        'systolic_bp' => $data['systolic_bp'] ?? null,
        'diastolic_bp' => $data['diastolic_bp'] ?? null,
        'temperature' => $data['temperature'] ?? null,
        'pulse' => $data['pulse'] ?? null,

        'notes' => $data['notes'] ?? null,
        'observed_at' => $data['observed_at'] ?? now(),

        'synced' => true,
    ]);

    $this->checkAlerts($observation);

    return response()->json([
        'message' => 'Observation enregistrée',
        'server_id' => $observation->id,
        'local_id' => $data['local_id'] ?? null,
    ], 201);
}

public function update(Request $request, $id)
{
    /** @var User $user */
    $user = Auth::user();

    // ✅ Blocage explicite : superviseur ne peut jamais modifier
    if ($user->isSuperviseur()) {
        return response()->json(['message' => 'Les superviseurs ne peuvent pas modifier une observation'], 403);
    }

    // ✅ Blocage explicite : admin ne peut jamais modifier
    if ($user->isAdmin()) {
        return response()->json(['message' => 'Les administrateurs ne peuvent pas modifier une observation'], 403);
    }

    $obs = Observation::findOrFail($id);

    $labour = $this->visibleLabour($obs->labour_id);
    if (! $labour) {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    $request->validate([
        'dilation' => 'nullable|numeric',
        'fcf' => 'nullable|integer',
        'contractions' => 'nullable|integer',
        'station' => 'nullable|integer',
        'systolic_bp' => 'nullable|integer',
        'diastolic_bp' => 'nullable|integer',
        'temperature' => 'nullable|numeric',
        'pulse' => 'nullable|integer',
        'observed_at' => 'nullable|date',
        'notes' => 'nullable|string',
    ]);

    $obs->update($request->all());

    return response()->json($obs);
}

public function destroy($id)
{
    /** @var User $user */
    $user = Auth::user();

    // ✅ Blocage explicite : superviseur ne peut jamais supprimer
    if ($user->isSuperviseur()) {
        return response()->json(['message' => 'Les superviseurs ne peuvent pas supprimer une observation'], 403);
    }

    // ✅ Au-delà de ce point, seuls admin (et potentiellement sage_femme propriétaire) sont admis
    if (! $user->isAdmin()) {
        return response()->json(['message' => 'Action réservée aux administrateurs'], 403);
    }

    $observation = Observation::findOrFail($id);
    $observation->delete();

    return response()->json(['message' => 'Observation supprimée']);
}
}
