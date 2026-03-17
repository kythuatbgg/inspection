<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CabinetController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\PlanDetailController;
use App\Http\Controllers\Api\InspectionController;
use App\Http\Controllers\Api\SyncController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Master Data
    Route::apiResource('cabinets', CabinetController::class);

    // Checklists
    Route::get('checklists/{checklist}/items', [ChecklistController::class, 'items']);
    Route::apiResource('checklists', ChecklistController::class);

    // Batches
    Route::apiResource('batches', BatchController::class);

    // Plan Details
    Route::get('batches/{batch}/plans', [PlanDetailController::class, 'index']);
    Route::patch('plans/{plan}/complete', [PlanDetailController::class, 'markComplete']);

    // Inspections
    Route::get('plans/{plan}/inspection', [InspectionController::class, 'show']);
    Route::post('inspections', [InspectionController::class, 'store']);
    Route::get('inspections/{inspection}/details', [InspectionController::class, 'details']);

    // Sync (offline)
    Route::post('/sync', [SyncController::class, 'sync']);
});
