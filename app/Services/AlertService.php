<?php

namespace App\Services;

use App\Models\Alert;

class AlertService
{
    public static function analyse($labour, array $data): void
    {
        // Souffrance fœtale
        if (isset($data['fcf']) && ($data['fcf'] < 120 || $data['fcf'] > 160)) {
            self::createAlert($labour->id, 'rouge', 'Souffrance fœtale suspectée (OMS)');
        }

        // Hypertension
        if (
            isset($data['systolic_bp'], $data['diastolic_bp']) &&
            ($data['systolic_bp'] >= 140 || $data['diastolic_bp'] >= 90)
        ) {
            self::createAlert($labour->id, 'rouge', 'Hypertension gravidique (OMS)');
        }

        // Fièvre
        if (isset($data['temperature']) && $data['temperature'] >= 38) {
            self::createAlert($labour->id, 'orange', 'Fièvre maternelle (OMS)');
        }
    }

    private static function createAlert($labourId, $level, $message): void
    {
        Alert::create([
            'labour_id' => $labourId,
            'level' => $level,
            'message' => $message,
        ]);
    }
}
