<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Observation;
use Carbon\Carbon;

class PartographService
{
    /**
     * Analyse le partogramme OMS
     */
    public static function analyse($labour): void
    {
        $observations = Observation::where('labour_id', $labour->id)
            ->orderBy('created_at')
            ->get();

        if ($observations->count() < 2) {
            return; // pas assez de données
        }

        // Détecter le début de la phase active (≥ 5 cm)
        $activeStart = $observations->firstWhere('dilation', '>=', 5);

        if (! $activeStart) {
            return;
        }

        // Sauvegarder le début de phase active si non défini
        if (! $labour->active_phase_start) {
            $labour->update([
                'active_phase_start' => $activeStart->created_at,
            ]);
        }

        // Dernières observations
        $last = $observations->last();

        $hours = Carbon::parse($labour->active_phase_start)
            ->diffInMinutes($last->created_at) / 60;

        if ($hours <= 0) {
            return;
        }

        $dilationProgress = $last->dilation - $activeStart->dilation;
        $rate = $dilationProgress / $hours;

        // 🔶 TRAVAIL LENT (OMS)
        if ($rate < 0.5) {
            self::alertOnce(
                $labour->id,
                'orange',
                'Travail lent détecté (OMS – partogramme)'
            );
        }

        // 🔴 STAGNATION ≥ 2 HEURES
        $previous = $observations
            ->where('created_at', '>=', now()->subHours(2))
            ->pluck('dilation')
            ->unique();

        if ($previous->count() === 1) {
            self::alertOnce(
                $labour->id,
                'rouge',
                'Stagnation de la dilatation ≥ 2h (OMS)'
            );
        }
    }

    /**
     * Éviter les alertes en doublon
     */
    private static function alertOnce($labourId, $level, $message): void
    {
        $exists = Alert::where('labour_id', $labourId)
            ->where('message', $message)
            ->where('created_at', '>=', now()->subHours(1))
            ->exists();

        if (! $exists) {
            Alert::create([
                'labour_id' => $labourId,
                'level' => $level,
                'message' => $message,
            ]);
        }
    }
}
