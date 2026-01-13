<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LabourController;
use App\Http\Controllers\Api\ObservationController;
use App\Http\Controllers\Api\PartographController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\DeathController;
use App\Http\Controllers\Api\DeliveryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTHENTIFICATION
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| ROUTES PROTÉGÉES (SANCTUM)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | PATIENTS
    |--------------------------------------------------------------------------
    */
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
    /*
    |--------------------------------------------------------------------------
    | LABOURS
    |--------------------------------------------------------------------------
    */
    Route::get('/labours', [LabourController::class, 'index']);
    Route::post('/labours', [LabourController::class, 'store']);
    Route::get('/labours/all', [LabourController::class, 'allLabours']);

    /*
    |--------------------------------------------------------------------------
    | OBSERVATIONS
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')
        ->get('/labours/{labour}/observations', [ObservationController::class, 'index']);

    Route::post('/observations', [ObservationController::class, 'store']);
    Route::post('/observations', [ObservationController::class, 'index']);
    Route::put('/observations/{id}', [ObservationController::class, 'update']);
    Route::get('/observations/sync', [ObservationController::class, 'sync']);

    /*
    |--------------------------------------------------------------------------
    | PARTOGRAMME
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/labours/{labour}/partogramme',
        [PartographController::class, 'show']
    );
});

    /*--------------------------------------------------------------------------
    | Statistiques mensuelles des accouchements
    |--------------------------------------------------------------------------
    */
Route::middleware('auth:sanctum')->get('/labours/monthly', [LabourController::class, 'monthlyStats']);

    /*
    |--------------------------------------------------------------------------
    | Récupérer l'accouchement en cours d'un patient
    |--------------------------------------------------------------------------
    */
Route::middleware('auth:sanctum')->get('/patients/{patient}/active_labour', function ($patientId) {
    $labour = App\Models\Labour::where('patient_id', $patientId)
        ->where('status', 'en_cours')
        ->first();

    if ($labour) {
        return response()->json($labour);
    }

    return response()->json(null, 200);
});

    /*--------------------------------------------------------------------------
    | Récupérer les observations d'un accouchement
    |--------------------------------------------------------------------------
    */
Route::middleware('auth:sanctum')->get('/labours/{labour}/observations', function ($labourId) {
    $labour = \App\Models\Labour::find($labourId);
    if ($labour) {
        return $labour->observations()
            ->orderBy('observed_at', 'desc')
            ->get();
    }

    return response()->json(null, 404);
});

    /*--------------------------------------------------------------------------
    | Créer un nouvel accouchement
    |--------------------------------------------------------------------------
    */
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/labours', [\App\Http\Controllers\Api\LabourController::class, 'store']);
});

    /*--------------------------------------------------------------------------
    | Récupérer l'accouchement actif d'un patient
    |--------------------------------------------------------------------------
    */
Route::middleware('auth:sanctum')->get(
    '/patients/{patient}/active-labour',
    [LabourController::class, 'active']
);

    /*--------------------------------------------------------------------------
    | Clôturer un accouchement
    |--------------------------------------------------------------------------
    */
Route::post('/labours/{labour}/close', [LabourController::class, 'close'])
    ->middleware('auth:sanctum');

    /*--------------------------------------------------------------------------
    | Accouchements en cours
    |--------------------------------------------------------------------------
    */
Route::middleware('auth:sanctum')->get('/labours/ongoing', function () {
    return \App\Models\Labour::with('patient')
        ->where('status', 'en_cours')
        ->orderBy('start_time', 'asc')
        ->get();
});

    /*--------------------------------------------------------------------------
    | Détail d'un accouchement
    |--------------------------------------------------------------------------
    */
Route::get('/labours/{id}', [LabourController::class, 'show']);

Route::middleware('auth:sanctum')->get('/home-data', function () {

    return response()->json([
        'ongoing_births' => \App\Models\Labour::where('status', 'en_cours')->count(),

        'recent_births' => \App\Models\Labour::orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'patient_id', 'status', 'created_at']),

        'protocols' => [
            'Surveillance du travail',
            'Pré-éclampsie',
            'Hémorragie du post-partum',
            'Souffrance fœtale',
        ],
    ]);
});

    /*--------------------------------------------------------------------------
    | Récupérer les alertes d'un accouchement
    |--------------------------------------------------------------------------
    */
Route::middleware('auth:sanctum')->get(
    '/labours/{labour}/alerts',
    fn ($labour) => \App\Models\Alert::where('labour_id', $labour)->latest()->get()
);

    /*--------------------------------------------------------------------------
    | Référés
    |--------------------------------------------------------------------------
    */

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/referrals', [ReferralController::class, 'store']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/deaths', [DeathController::class, 'store']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/deliveries', [DeliveryController::class, 'index']);
    Route::get('/deliveries/{id}', [DeliveryController::class, 'show']);
    Route::post('/deliveries', [DeliveryController::class, 'store']);
});




// /*
// |--------------------------------------------------------------------------
// | AUTH (libre)
// |--------------------------------------------------------------------------
// */
// Route::post('/login', [AuthController::class, 'login']);
// Route::post('/logout', [AuthController::class, 'logout']);

// /*
// |--------------------------------------------------------------------------
// | PATIENTS
// |--------------------------------------------------------------------------
// */
// Route::get('/patients', [PatientController::class, 'index']);
// Route::post('/patients', [PatientController::class, 'store']);
// Route::get('/patients/{patient}/active-labour', [LabourController::class, 'active']);

// /*
// |--------------------------------------------------------------------------
// | LABOURS
// |--------------------------------------------------------------------------
// */
// Route::get('/labours', [LabourController::class, 'index']);
// Route::get('/labours/all', [LabourController::class, 'allLabours']);
// Route::get('/labours/{id}', [LabourController::class, 'show']);
// Route::post('/labours', [LabourController::class, 'store']);
// Route::post('/labours/{labour}/close', [LabourController::class, 'close']);

// Route::get('/labours/ongoing', function () {
//     return \App\Models\Labour::with('patient')
//         ->where('status', 'en_cours')
//         ->orderBy('start_time', 'asc')
//         ->get();
// });

// Route::get('/labours/monthly', [LabourController::class, 'monthlyStats']);

// /*
// |--------------------------------------------------------------------------
// | OBSERVATIONS
// |--------------------------------------------------------------------------
// */
// Route::get('/labours/{labour}/observations', [ObservationController::class, 'index']);
// Route::post('/observations', [ObservationController::class, 'store']);
// Route::put('/observations/{id}', [ObservationController::class, 'update']);
// Route::get('/observations/sync', [ObservationController::class, 'sync']);

// /*
// |--------------------------------------------------------------------------
// | PARTOGRAMME
// |--------------------------------------------------------------------------
// */
// Route::get('/labours/{labour}/partogramme', [PartographController::class, 'show']);

// /*
// |--------------------------------------------------------------------------
// | ALERTES
// |--------------------------------------------------------------------------
// */
// Route::get('/labours/{labour}/alerts', function ($labour) {
//     return \App\Models\Alert::where('labour_id', $labour)
//         ->latest()
//         ->get();
// });

// /*
// |--------------------------------------------------------------------------
// | HOME / DASHBOARD
// |--------------------------------------------------------------------------
// */
// Route::get('/home-data', function () {
//     return response()->json([
//         'ongoing_births' => \App\Models\Labour::where('status', 'en_cours')->count(),
//         'recent_births' => \App\Models\Labour::orderBy('created_at', 'desc')
//             ->limit(10)
//             ->get(['id', 'patient_id', 'status', 'created_at']),
//         'protocols' => [
//             'Surveillance du travail',
//             'Pré-éclampsie',
//             'Hémorragie du post-partum',
//             'Souffrance fœtale',
//         ],
//     ]);
// });

// /*
// |--------------------------------------------------------------------------
// | REFERRALS / DEATHS / DELIVERIES
// |--------------------------------------------------------------------------
// */
// Route::post('/referrals', [ReferralController::class, 'store']);

// Route::post('/deaths', [DeathController::class, 'store']);

// Route::get('/deliveries', [DeliveryController::class, 'index']);
// Route::get('/deliveries/{id}', [DeliveryController::class, 'show']);
// Route::post('/deliveries', [DeliveryController::class, 'store']);
