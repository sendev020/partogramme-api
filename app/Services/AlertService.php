<?php

namespace App\Services;

use App\Models\Alert;

class AlertService
{
    public static function analyse($labour, array $data): void
    {
        // Souffrance fœtale
        if (isset($data['fcf']) && ($data['fcf'] < 110 || $data['fcf'] > 160)) {
            self::createAlert($labour, 'rouge', 'Rythme cardiaque fœtal anormal (FCF)');
        }

        // Décélération fœtale
        if (isset($data['fetal_heart_deceleration']) && $data['fetal_heart_deceleration'] !== 'aucun') {
            self::createAlert($labour, 'rouge', 'Décélération fœtale détectée');
        }

        // Liquide amniotique anormal
        if (isset($data['amniotic_fluid']) && ! in_array($data['amniotic_fluid'], ['intact', 'clair'], true)) {
            self::createAlert($labour, 'rouge', 'Liquide amniotique anormal');
        }

        // Dilation active insuffisante (OMS)
        if (isset($data['dilation']) && $data['dilation'] < 1 && isset($labour->active_phase_start)) {
            self::createAlert($labour, 'orange', 'Dilatation lente ou insuffisante');
        }

        // Contractions anormales
        if (isset($data['contractions']) && $data['contractions'] > 5) {
            self::createAlert($labour, 'orange', 'Contractions trop fréquentes');
        }

        // Hypertension maternelle
        if (
            isset($data['systolic_bp'], $data['diastolic_bp']) &&
            ($data['systolic_bp'] >= 160 || $data['diastolic_bp'] >= 110)
        ) {
            self::createAlert($labour, 'rouge', 'Hypertension sévère maternelle');
        } elseif (
            isset($data['systolic_bp'], $data['diastolic_bp']) &&
            ($data['systolic_bp'] >= 140 || $data['diastolic_bp'] >= 90)
        ) {
            self::createAlert($labour, 'orange', 'Hypertension maternelle');
        }

        // Température anormale
        if (isset($data['temperature']) && ($data['temperature'] < 36 || $data['temperature'] > 38)) {
            self::createAlert($labour, 'orange', 'Température maternelle anormale');
        }

        // Pouls anormal
        if (isset($data['pulse']) && ($data['pulse'] < 50 || $data['pulse'] > 120)) {
            self::createAlert($labour, 'orange', 'Pouls maternel anormal');
        }

        // Station anormale
        if (isset($data['station']) && ($data['station'] < -3 || $data['station'] > 3)) {
            self::createAlert($labour, 'orange', 'Station fœtale anormale');
        }

        // Caput ou modelage anormal
        if (isset($data['caput']) && $data['caput'] !== '0') {
            self::createAlert($labour, 'orange', 'Caput suspecté');
        }
        if (isset($data['moulding']) && $data['moulding'] !== '0') {
            self::createAlert($labour, 'orange', 'Modelage fœtal anormal');
        }

        // Urines anormales
        if (isset($data['urines']) && in_array($data['urines'], ['acetone', 'proteine'], true)) {
            self::createAlert($labour, 'orange', 'Urines anormales détectées');
        }
    }

    private static function createAlert($labour, $level, $message): void
    {
        $exists = Alert::where('labour_id', $labour->id)
            ->where('message', $message)
            ->where('created_at', '>=', now()->subHours(1))
            ->exists();

        if ($exists) {
            return;
        }

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
