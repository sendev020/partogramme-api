<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Labour;
use App\Models\Observation;
use App\Models\User;
use App\Services\AlertService;
use App\Services\PartographService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ObservationController extends Controller
{
    private function recordAudit(int $actorId, Observation $observation, string $action, array $changedFields): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'entity_type' => 'observation',
            'entity_id' => $observation->id,
            'action' => $action,
            'changed_fields' => json_encode(array_values(array_unique($changedFields)), JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function visibleLabour($labourId)
    {
        /** @var User|null $user */
        $user = Auth::user();
        $query = Labour::where('id', $labourId);

        if ($user->isSuperviseur()) {
            $query->where('district', $user->district);
        } elseif (! $user->isAdmin() && ! $user->isSuperviseurRegional()) {
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


    public function store(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json(['message' => 'Non authentifié'], 401);
            }

            if ($user->isAnySuperviseur()) {
                return response()->json(['message' => 'Les superviseurs ne peuvent pas ajouter d\'observation'], 403);
            }

            if ($user->isAdmin()) {
                return response()->json(['message' => 'Les administrateurs ne peuvent pas ajouter d\'observation'], 403);
            }

            $data = $request->validate([
                'local_id' => 'nullable|integer',
                'labour_id' => 'required|exists:labours,id',
                'dilation' => 'nullable|numeric',
                'contractions' => 'nullable|integer',
                'fcf' => 'nullable|integer',
                'station' => 'nullable|integer',
                'systolic_bp' => 'nullable|integer',
                'diastolic_bp' => 'nullable|integer',
                'temperature' => 'nullable|numeric',
                'pulse' => 'nullable|integer',
                'amniotic_fluid' => 'nullable|in:intact,clair,meconial+,meconial++,meconial+++,sanglant',
                'fetal_heart_deceleration' => 'nullable|in:aucun,precoce,tardif,variable',
                'fetal_position' => 'nullable|in:anterieure,posterieure,transverse',
                'caput' => 'nullable|in:0,+,++,+++',
                'moulding' => 'nullable|in:0,+,++,+++',
                'urines' => 'nullable|string',
                'maternal_position' => 'nullable|string',
                'oral_fluids' => 'nullable|string',
                'iv_fluids' => 'nullable|string',
                'oxytocin_ui_per_l' => 'nullable|integer',
                'oxytocin_drops_per_min' => 'nullable|integer',
                'analgesia' => 'nullable|string',
                'drugs' => 'nullable|string',
                'evaluation' => 'nullable|string',
                'care_plan' => 'nullable|string',
                'companion_present' => 'nullable|string',
                'notes' => 'nullable|string',
                'observed_at' => 'nullable|date',
                'updated_at' => 'nullable|date',
            ]);

            $labour = $this->visibleLabour($data['labour_id']);
            if (! $labour) {
                return response()->json(['message' => 'Accouchement non trouvé ou non autorisé'], 403);
            }

            $observation = DB::transaction(function () use ($data, $labour, $user) {
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
                'amniotic_fluid' => $data['amniotic_fluid'] ?? null,
                'fetal_heart_deceleration' => $data['fetal_heart_deceleration'] ?? null,
                'fetal_position' => $data['fetal_position'] ?? null,
                'caput' => $data['caput'] ?? null,
                'moulding' => $data['moulding'] ?? null,
                'urines' => $data['urines'] ?? null,
                'maternal_position' => $data['maternal_position'] ?? null,
                'oral_fluids' => $data['oral_fluids'] ?? null,
                'iv_fluids' => $data['iv_fluids'] ?? null,
                'oxytocin_ui_per_l' => $data['oxytocin_ui_per_l'] ?? null,
                'oxytocin_drops_per_min' => $data['oxytocin_drops_per_min'] ?? null,
                'analgesia' => $data['analgesia'] ?? null,
                'drugs' => $data['drugs'] ?? null,
                'evaluation' => $data['evaluation'] ?? null,
                'care_plan' => $data['care_plan'] ?? null,
                'companion_present' => $data['companion_present'] ?? null,
                'notes' => $data['notes'] ?? null,
                'observed_at' => $data['observed_at'] ?? now(),
                'synced' => true,
                ]);

                $this->recordAudit(
                    $user->id,
                    $observation,
                    'created',
                    array_keys(array_intersect_key($data, array_flip([
                        'dilation', 'contractions', 'fcf', 'station',
                        'systolic_bp', 'diastolic_bp', 'temperature', 'pulse',
                        'amniotic_fluid', 'fetal_heart_deceleration',
                        'fetal_position', 'caput', 'moulding', 'urines',
                        'maternal_position', 'oral_fluids', 'iv_fluids',
                        'oxytocin_ui_per_l', 'oxytocin_drops_per_min',
                        'analgesia', 'drugs', 'evaluation', 'care_plan',
                        'companion_present', 'notes',
                    ]))),
                );

                AlertService::analyse($labour, $observation->toArray());
                PartographService::analyse($labour);
                return $observation;
            });

            return response()->json([
                'message' => 'Observation enregistrée',
                'server_id' => $observation->id,
                'local_id' => $data['local_id'] ?? null,
            ], 201);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erreur lors de l’enregistrement de l’observation'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isAnySuperviseur()) {
            return response()->json(['message' => 'Les superviseurs ne peuvent pas modifier une observation'], 403);
        }

        if ($user->isAdmin()) {
            return response()->json(['message' => 'Les administrateurs ne peuvent pas modifier une observation'], 403);
        }

        $obs = Observation::findOrFail($id);

        $labour = $this->visibleLabour($obs->labour_id);
        if (! $labour) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'dilation' => 'nullable|numeric',
            'fcf' => 'nullable|integer',
            'contractions' => 'nullable|integer',
            'station' => 'nullable|integer',
            'systolic_bp' => 'nullable|integer',
            'diastolic_bp' => 'nullable|integer',
            'temperature' => 'nullable|numeric',
            'pulse' => 'nullable|integer',
            'amniotic_fluid' => 'nullable|in:intact,clair,meconial+,meconial++,meconial+++,sanglant',
            'fetal_heart_deceleration' => 'nullable|in:aucun,precoce,tardif,variable',
            'fetal_position' => 'nullable|in:anterieure,posterieure,transverse',
            'caput' => 'nullable|in:0,+,++,+++',
            'moulding' => 'nullable|in:0,+,++,+++',
            'urines' => 'nullable|string',
            'maternal_position' => 'nullable|string',
            'oral_fluids' => 'nullable|string',
            'iv_fluids' => 'nullable|string',
            'oxytocin_ui_per_l' => 'nullable|integer',
            'oxytocin_drops_per_min' => 'nullable|integer',
            'analgesia' => 'nullable|string',
            'drugs' => 'nullable|string',
            'evaluation' => 'nullable|string',
            'care_plan' => 'nullable|string',
            'companion_present' => 'nullable|string',
            'observed_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($obs, $validated, $labour, $user) {
                $before = $obs->only(array_keys($validated));
                $obs->fill($validated);
                $changedFields = array_keys(array_filter(
                    $validated,
                    fn ($value, $key) => ($before[$key] ?? null) != $value,
                    ARRAY_FILTER_USE_BOTH,
                ));
                $obs->save();
                if ($changedFields !== []) {
                    $this->recordAudit($user->id, $obs, 'updated', $changedFields);
                }
            AlertService::analyse($labour, $obs->toArray());
            PartographService::analyse($labour);
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erreur lors du traitement de l’observation'], 500);
        }

        return response()->json($obs);
    }

    public function destroy($id)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isAnySuperviseur()) {
            return response()->json(['message' => 'Les superviseurs ne peuvent pas supprimer une observation'], 403);
        }

        if (! $user->isAdmin()) {
            return response()->json(['message' => 'Action réservée aux administrateurs'], 403);
        }

        $observation = Observation::findOrFail($id);
        DB::transaction(function () use ($observation, $user) {
            $observation->delete();
            $this->recordAudit($user->id, $observation, 'deleted', ['deleted_at']);
        });

        return response()->json(['message' => 'Observation supprimée']);
    }

    public function allForUser()
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Observation::query();

        if ($user->isSuperviseur()) {
            $query->whereHas('labour', function ($q) use ($user) {
                $q->where('district', $user->district);
            });
        } elseif (! $user->isAdmin() && ! $user->isSuperviseurRegional()) {
            $query->whereHas('labour', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return response()->json(['data' => $query->get()]);
    }
}
