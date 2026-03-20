<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\CabinetController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\InspectionController;
use App\Http\Controllers\Api\PlanDetailController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\StorageController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // User Management
    Route::get('/users/stats', [UserController::class, 'stats']);
    Route::apiResource('users', UserController::class);

    Route::get('cabinets/template', [CabinetController::class, 'downloadTemplate']);
    Route::get('cabinets/map', [CabinetController::class, 'map']);
    Route::post('cabinets/import', [CabinetController::class, 'import']);
    Route::get('cabinets/export', [CabinetController::class, 'export']);
    Route::match(['get', 'post'], 'cabinets/export-result', [CabinetController::class, 'exportResult']);
    Route::apiResource('cabinets', CabinetController::class);

    Route::get('checklists/{checklist}/items', [ChecklistController::class, 'items']);
    Route::apiResource('checklists', ChecklistController::class);

    Route::get('batches', [BatchController::class, 'index']);
    Route::get('batches/{batch}', [BatchController::class, 'show']);

    // Manager-only routes (admin + manager)
    Route::middleware('manager')->group(function () {
        Route::post('batches', [BatchController::class, 'store']);
        Route::put('batches/{batch}', [BatchController::class, 'update']);
        Route::delete('batches/{batch}', [BatchController::class, 'destroy']);
        Route::get('batches/{batch}/results', [BatchController::class, 'results']);
        Route::patch('batches/{batch}/close', [BatchController::class, 'close']);
        Route::patch('batches/{batch}/reopen', [BatchController::class, 'reopen']);
        Route::post('batches/{batch}/cabinets', [BatchController::class, 'addCabinets']);
        Route::delete('batches/{batch}/plans/{plan}', [BatchController::class, 'removeCabinet']);

        // Plan review
        Route::patch('plans/{plan}/review', [PlanDetailController::class, 'review']);

        // Storage management
        Route::get('/admin/storage-stats', [StorageController::class, 'stats']);
        Route::post('/admin/storage-cleanup', [StorageController::class, 'cleanup']);
    });

    Route::get('batches/{batch}/plans', [PlanDetailController::class, 'index']);
    Route::get('plans/{plan}', [PlanDetailController::class, 'show']);
    Route::patch('plans/{plan}/complete', [PlanDetailController::class, 'markComplete']);

    Route::get('plans/{plan}/inspection', [InspectionController::class, 'show']);
    Route::post('inspections', [InspectionController::class, 'store']);
    Route::get('inspections/{inspection}/details', [InspectionController::class, 'details']);

    Route::post('/sync', [SyncController::class, 'sync']);
    
    // File upload
    Route::post('/upload', [UploadController::class, 'store']);
});
