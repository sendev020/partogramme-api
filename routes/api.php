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
use Illuminate\Support\Facades\Route;

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
    | PATIENTS
    |--------------------------------------------------------------------------
    */
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients/{patient}/active-labour', [LabourController::class, 'active']);

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
    Route::post('/labours', [LabourController::class, 'store']);
    Route::post('/labours/{labour}/close', [LabourController::class, 'close']);
    Route::post('/labours/{id}/finish', [LabourController::class, 'finish']);
    Route::get('/labours/{labour}/alerts', [LabourController::class, 'alerts']);

    /*
    |--------------------------------------------------------------------------
    | OBSERVATIONS
    |--------------------------------------------------------------------------
    */
    Route::get('/labours/{labour}/observations', [ObservationController::class, 'index']);
    Route::post('/observations', [ObservationController::class, 'store']);
    Route::put('/observations/{id}', [ObservationController::class, 'update']);
    Route::get('/observations/sync', [ObservationController::class, 'sync']);

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
});
