<?php

namespace App\Http\Controllers;

use App\Models\Labour;
use App\Models\Observation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SyncController extends Controller
{
    public function syncObservations(Request $request)
    {
         /** @var User|null $user */
        $user = Auth::user();
        $results = [];

        foreach ($request->observations as $obs) {
            // ✅ Vérifier que le labour cible appartient bien à l'utilisateur (ou est visible)
            $labourQuery = Labour::where('id', $obs['labour_server_id']);

            if ($user->isSuperviseur()) {
                $labourQuery->where('district', $user->district);
            } elseif (! $user->isAdmin() && ! $user->isSuperviseurRegional()) {
                $labourQuery->where('user_id', $user->id);
            }

            $labour = $labourQuery->first();

            if (! $labour) {
                // ⛔ Ignorer silencieusement les observations dont le labour n'est pas autorisé
                continue;
            }

            $record = Observation::updateOrCreate(
                ['id' => $obs['server_id'] ?? null],
                [
                    'labour_id' => $obs['labour_server_id'],
                    'dilation' => $obs['dilation'],
                    'fcf' => $obs['fcf'],
                    'contractions' => $obs['contractions'],
                    'temperature' => $obs['temperature'],
                    'systolic_bp' => $obs['systolic_bp'],
                    'diastolic_bp' => $obs['diastolic_bp'],
                    'pulse' => $obs['pulse'],
                    'amniotic_fluid' => $obs['amniotic_fluid'],
                    'fetal_heart_deceleration' => $obs['fetal_heart_deceleration'],
                    'fetal_position' => $obs['fetal_position'],
                    'caput' => $obs['caput'],
                    'moulding' => $obs['moulding'],
                    'urines' => $obs['urines'],
                    'maternal_position' => $obs['maternal_position'],
                    'oral_fluids' => $obs['oral_fluids'],
                    'iv_fluids' => $obs['iv_fluids'],
                    'oxytocin_rate' => $obs['oxytocin_rate'],
                    'analgesia' => $obs['analgesia'],
                    'drugs' => $obs['drugs'],
                    'evaluation' => $obs['evaluation'],
                    'care_plan' => $obs['care_plan'],
                    'operation' => $obs['operation'],
                    'synced' => $obs['synced'] ?? false,
                    'created_at' => $obs['created_at'] ?? null,
                    'updated_at' => $obs['updated_at'] ?? null,
                    'deleted_at' => $obs['deleted_at'] ?? null,
                    'notes' => $obs['notes'],
                    'observed_at' => $obs['observed_at'],
                    'user_id' => $user->id,
                ]
            );

            $results[] = [
                'local_id' => $obs['id'],
                'server_id' => $record->id,
            ];
        }

        return response()->json($results);
    }
}
