<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // déjà protégé par auth:sanctum
    }

    public function rules(): array
    {
        return [
            'labour_id' => 'required|exists:labours,id',
            'dilation' => 'nullable|numeric|min:0|max:10',
            'fcf' => 'nullable|integer|min:60|max:220',
            'contractions' => 'nullable|integer|min:0|max:5',
            'systolic_bp' => 'nullable|integer|min:70|max:200',
            'diastolic_bp' => 'nullable|integer|min:40|max:130',
            'temperature' => 'nullable|numeric|min:35|max:42',
        ];
    }

    public function messages(): array
    {
        return [
            'dilation.max' => 'La dilatation ne peut pas dépasser 10 cm',
            'contractions.max' => 'Contractions excessives (>5/10 min)',
            'fcf.min' => 'FCF trop basse',
            'fcf.max' => 'FCF trop élevée',
        ];
    }
}
