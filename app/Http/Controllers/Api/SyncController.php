<?php

namespace App\Http\Controllers;

use App\Models\Observation;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function syncObservations(Request $request)
    {
        $results = [];

        foreach ($request->observations as $obs) {
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
