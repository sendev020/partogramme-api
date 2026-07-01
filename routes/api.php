<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeathController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\LabourController;
use App\Http\Controllers\Api\ObservationController;
use App\Http\Controllers\Api\PartographController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\SupportCareController;
use App\Http\Controllers\Api\MedicamentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


/*
|--------------------------------------------------------------------------
| AUTHENTIFICATION (libre)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| ROUTES PROTÉGÉES (SANCTUM) — tout passe par les contrôleurs sécurisés
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | GESTION DES UTILISATEURS (admin)
    |--------------------------------------------------------------------------
    */
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | PATIENTS
    |--------------------------------------------------------------------------
    */
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients/{patient}/active-labour', [LabourController::class, 'active']);
    Route::put('/patients/{id}', [PatientController::class, 'update']);
    Route::delete('/patients/{id}', [PatientController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | LABOURS
    |--------------------------------------------------------------------------
    */
    Route::get('/labours', [LabourController::class, 'index']);
    Route::get('/labours/all', [LabourController::class, 'allLabours']);
    Route::get('/labours/ongoing', [LabourController::class, 'ongoing']);
    Route::get('/labours/monthly', [LabourController::class, 'monthlyStats']);
    Route::get('/labours/{id}', [LabourController::class, 'show']);
    Route::put('/labours/{id}', [LabourController::class, 'update']);
    Route::post('/labours', [LabourController::class, 'store']);
    // Route::post('/labours/{labour}/close', [LabourController::class, 'close']);
    // Route::post('/labours/{id}/finish', [LabourController::class, 'finish']);
    //Route::get('/labours/{labour}/alerts', [LabourController::class, 'alerts']);
    Route::delete('/labours/{id}', [LabourController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | OBSERVATIONS
    |--------------------------------------------------------------------------
    */
    Route::get('/labours/{labour}/observations', [ObservationController::class, 'index']);
    Route::post('/observations', [ObservationController::class, 'store']);
    Route::put('/observations/{id}', [ObservationController::class, 'update']);
    //Route::get('/observations/sync', [ObservationController::class, 'sync']);
    Route::delete('/observations/{id}', [ObservationController::class, 'destroy']);
    Route::get('/observations/all', [ObservationController::class, 'allForUser']);

    /*
    |--------------------------------------------------------------------------
    | SUPPORT CARE
    |--------------------------------------------------------------------------
    */
    Route::get('/labours/{labour}/support-cares', [SupportCareController::class, 'index']);
    Route::post('/support-cares', [SupportCareController::class, 'store']);
    Route::get('/support-cares/{id}', [SupportCareController::class, 'show']);
    Route::put('/support-cares/{id}', [SupportCareController::class, 'update']);
    Route::delete('/support-cares/{id}', [SupportCareController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | MEDICAMENTS
    |--------------------------------------------------------------------------
    */
    Route::get('/labours/{labour}/medicaments', [MedicamentController::class, 'index']);
    Route::post('/medicaments', [MedicamentController::class, 'store']);
    Route::get('/medicaments/{id}', [MedicamentController::class, 'show']);
    Route::put('/medicaments/{id}', [MedicamentController::class, 'update']);
    Route::delete('/medicaments/{id}', [MedicamentController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | PARTOGRAMME
    |--------------------------------------------------------------------------
    */
    Route::get('/labours/{labour}/partogramme', [PartographController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | HOME / DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/home-data', [HomeController::class, 'homeData']);

    /*
    |--------------------------------------------------------------------------
    | REFERRALS / DEATHS / DELIVERIES
    |--------------------------------------------------------------------------
    */
    Route::post('/referrals', [ReferralController::class, 'store']);

    Route::post('/deaths', [DeathController::class, 'store']);

    Route::get('/deliveries', [DeliveryController::class, 'index']);
    Route::get('/deliveries/{id}', [DeliveryController::class, 'show']);
    Route::post('/deliveries', [DeliveryController::class, 'store']);



    // Dans un contrôleur approprié, ou directement dans une route
    Route::get('/postes-de-sante', function (Request $request) {
        /** @var User $user */
        $user = Auth::user();

        $query = User::query()->whereNotNull('poste_de_sante');

        if ($user->isSuperviseur()) {
            $query->where('district', $user->district);
        } elseif ($user->isSuperviseurRegional() && $request->filled('district')) {
            $query->where('district', $request->district);
        } elseif ($user->isAdmin() && $request->filled('district')) {
            $query->where('district', $request->district);
        }

        $postes = $query->distinct()->pluck('poste_de_sante');

        return response()->json($postes);
    });
});
