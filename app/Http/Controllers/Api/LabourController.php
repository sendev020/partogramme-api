<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Labour;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabourController extends Controller
{
    // Liste de tous les accouchements
    // public function index()
    // {
    //     $labours = Labour::with('patient')->orderBy('created_at', 'desc')->get();
    //     return response()->json(['labours' => $labours]);
    // }

    // Liste complète (alias)
    public function allLabours()
    {
        $labours = Labour::with('patient')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $labours,
        ]);
    }

    // Liste filtrée des accouchements
    public function index(Request $request)
    {
        $query = Labour::query();

        // Filtrer par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtrer par date (par exemple date de début)
        if ($request->filled('date')) {
            $query->whereDate('start_time', $request->date);
        }

        $labours = $query->with('patient')->orderBy('start_time', 'desc')->get();

        return response()->json($labours);
    }

    // Accouchements en cours
    public function ongoing()
    {
        $labours = Labour::with('patient')
            ->where('status', 'en_cours')
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json(['labours' => $labours]);
    }

    // Détail d'un accouchement
    public function show($id)
    {
        $labour = Labour::with('patient')->find($id);

        if (! $labour) {
            return response()->json(['message' => 'Accouchement non trouvé'], 404);
        }

        return response()->json($labour);
    }

    // Créer un nouvel accouchement
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'start_time' => 'required|date',
        ]);

        // 🚫 Vérifier s’il existe déjà un labour actif
        $existingLabour = Labour::where('patient_id', $request->patient_id)
            ->where('status', 'en_cours')
            ->first();

        if ($existingLabour) {
            return response()->json([
                'message' => 'Un labour est déjà en cours pour cette patiente',
                'labour' => $existingLabour,
            ], 409); // Conflict
        }

        // ✅ Création du labour
        $labour = Labour::create([
            'patient_id' => $request->patient_id,
            'user_id' => auth()->id(),
            'start_time' => $request->start_time,
            'status' => 'en_cours',
        ]);

        return response()->json($labour, 201);
    }

    // Clôturer un accouchement
    public function close($labourId)
    {
        $labour = Labour::find($labourId);

        if (! $labour) {
            return response()->json(['message' => 'Accouchement non trouvé'], 404);
        }

        $labour->status = 'termine';
        $labour->end_time = now();
        $labour->save();

        return response()->json(['message' => 'Accouchement clôturé', 'labour' => $labour]);
    }

    // Accouchement actif d'un patient
    public function active(Patient $patient)
    {
        $labour = Labour::where('patient_id', $patient->id)
            ->where('status', 'en_cours')
            ->first();

        if (! $labour) {
            return response()->json(null, 204);
        }

        return response()->json($labour);
    }

    // Alertes liées à un accouchement
    public function alerts($labourId)
    {
        $alerts = Alert::where('labour_id', $labourId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['alerts' => $alerts]);
    }

    // ✅ Fin d’un labour
    public function finish(Request $request, $id)
    {
        $labour = Labour::findOrFail($id);
        $labour->status = $request->status; // accouche / referer / mort
        $labour->save();

        return response()->json($labour);
    }

    public function monthlyStats()
    {
        // On récupère le nombre d'accouchements par mois et par statut
        $stats = DB::table('labours')
            ->selectRaw("
            DATE_FORMAT(start_time, '%Y-%m') as month,
            SUM(CASE WHEN status = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
            SUM(CASE WHEN status = 'refere' THEN 1 ELSE 0 END) as refere,
            SUM(CASE WHEN status = 'termine' THEN 1 ELSE 0 END) as termine,
            SUM(CASE WHEN status = 'delivery' THEN 1 ELSE 0 END) as delivery,
            SUM(CASE WHEN status = 'death' THEN 1 ELSE 0 END) as death
        ")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Formater le mois en texte si besoin (Jan, Feb, etc.)
        $formatted = $stats->map(function ($s) {
            return [
                'month' => date('M', strtotime($s->month.'-01')),
                'en_cours' => (int) $s->en_cours,
                'refere' => (int) $s->refere,
                'termine' => (int) $s->termine,
                'delivery' => (int) $s->delivery,
                'death' => (int) $s->death,
            ];
        });

        return response()->json($formatted);
    }

}
