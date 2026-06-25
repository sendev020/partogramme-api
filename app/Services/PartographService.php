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

        // Détecter le début de la phase active (≥ 4 cm)
        $activeStart = $observations->firstWhere('dilation', '>=', 4);

        if (! $activeStart) {
            return;
        }

        // Sauvegarder le début de phase active si non défini
        if (! $labour->active_phase_start) {
            $labour->update([
                'active_phase_start' => $activeStart->observed_at ?? $activeStart->created_at,
            ]);
        }

        // Dernières observations
        $last = $observations->last();

        $lastObservedAt = Carbon::parse($last->observed_at ?? $last->created_at);
        $startObservedAt = Carbon::parse($labour->active_phase_start);

        $hours = $startObservedAt->diffInMinutes($lastObservedAt) / 60;

        if ($hours <= 0) {
            return;
        }

        $dilationProgress = $last->dilation - $activeStart->dilation;
        $rate = $dilationProgress / $hours;

        // 🔶 TRAVAIL LENT (OMS)
        if ($rate < 1) {
            self::alertOnce(
                $labour,
                'orange',
                'Travail lent détecté (OMS – partogramme)'
            );
        }

        // 🔴 STAGNATION DE LA DILATATION ≥ 2 HEURES
        $windowStart = Carbon::parse($last->created_at)->subHours(2);
        $lastWindow = $observations
            ->where('created_at', '>=', $windowStart)
            ->pluck('dilation');

        if ($lastWindow->count() >= 2 && $lastWindow->unique()->count() === 1) {
            self::alertOnce(
                $labour,
                'rouge',
                'Stagnation de la dilatation ≥ 2h (OMS)'
            );
        }
    }

    /**
     * Éviter les alertes en doublon
     */
    private static function alertOnce($labour, $level, $message): void
    {
        $exists = Alert::where('labour_id', $labour->id)
            ->where('message', $message)
            ->where('created_at', '>=', now()->subHours(1))
            ->exists();

        if (! $exists) {
            Alert::create([
                'labour_id' => $labour->id,
                'user_id' => $labour->user_id,
                'district' => $labour->district,
                'poste_de_sante' => $labour->poste_de_sante,
                'level' => $level,
                'message' => $message,
            ]);
        }
    }
}
