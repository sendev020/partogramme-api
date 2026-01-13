<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index()
    {
        return Patient::orderBy('created_at', 'desc')->get();
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string',
        'age' => 'required|integer|min:10|max:60',
        'parity' => 'required|integer|min:0',
        'gestational_age' => 'required|numeric|min:1',
        'risk_factors' => 'nullable|string',
    ]);

    $patient = Patient::create($validated);

    return response()->json($patient, 201);
}

}
