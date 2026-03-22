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
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);

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
    Route::apiResource('checklists', ChecklistController::class)->only(['index', 'show']);

    Route::get('batches', [BatchController::class, 'index']);
    Route::post('batches', [BatchController::class, 'store']);
    Route::get('batches/{batch}', [BatchController::class, 'show']);

    // Manager-only routes (admin + manager)
    Route::middleware('manager')->group(function () {
        // Checklist management
        Route::post('checklists', [ChecklistController::class, 'store']);
        Route::put('checklists/{checklist}', [ChecklistController::class, 'update']);
        Route::delete('checklists/{checklist}', [ChecklistController::class, 'destroy']);
        Route::post('checklists/{checklist}/clone', [ChecklistController::class, 'duplicate']);
        Route::post('checklists/{checklist}/items', [ChecklistController::class, 'storeItem']);
        Route::put('checklists/{checklist}/items/{item}', [ChecklistController::class, 'updateItem']);
        Route::delete('checklists/{checklist}/items/{item}', [ChecklistController::class, 'destroyItem']);
        Route::put('batches/{batch}', [BatchController::class, 'update']);
        Route::delete('batches/{batch}', [BatchController::class, 'destroy']);
        Route::get('batches/{batch}/results', [BatchController::class, 'results']);
        Route::patch('batches/{batch}/close', [BatchController::class, 'close']);
        Route::patch('batches/{batch}/reopen', [BatchController::class, 'reopen']);
        Route::post('batches/{batch}/cabinets', [BatchController::class, 'addCabinets']);
        Route::delete('batches/{batch}/plans/{plan}', [BatchController::class, 'removeCabinet']);
        Route::patch('batches/{batch}/plans/{plan}/swap', [BatchController::class, 'swapCabinet']);
        Route::post('batches/{batch}/approve', [BatchController::class, 'approve']);
        Route::post('batches/{batch}/reject', [BatchController::class, 'reject']);

        // Plan review
        Route::patch('plans/{plan}/review', [PlanDetailController::class, 'review']);

        // Storage management
        Route::get('/admin/storage-stats', [StorageController::class, 'stats']);
        Route::post('/admin/storage-cleanup', [StorageController::class, 'cleanup']);

        // Statistics (admin/manager only)
        Route::prefix('statistics')->group(function () {
            Route::get('overview', [StatisticsController::class, 'overview']);
            Route::get('by-bts', [StatisticsController::class, 'byBts']);
            Route::get('by-inspector', [StatisticsController::class, 'byInspector']);
            Route::get('by-error-type', [StatisticsController::class, 'byErrorType']);
            Route::get('export', [StatisticsController::class, 'export']);
        });
    });

    Route::get('batches/{batch}/plans', [PlanDetailController::class, 'index']);
    Route::get('inspector/tasks', [PlanDetailController::class, 'tasks']);
    Route::get('plans/{plan}', [PlanDetailController::class, 'show']);
    Route::patch('plans/{plan}/complete', [PlanDetailController::class, 'markComplete']);

    Route::get('plans/{plan}/inspection', [InspectionController::class, 'show']);
    Route::post('inspections', [InspectionController::class, 'store']);
    Route::get('inspections/{inspection}/details', [InspectionController::class, 'details']);

    Route::post('/sync', [SyncController::class, 'sync']);
    
    // File upload
    Route::post('/upload', [UploadController::class, 'store']);

    // Reports (accessible by both admin and inspector)
    Route::prefix('reports')->group(function () {
        Route::get('search', [ReportController::class, 'search']);
        Route::get('inspection/{inspection}', [ReportController::class, 'inspectionReport']);
        Route::get('batch/{batch}/summary', [ReportController::class, 'batchSummary']);
        Route::get('batch/{batch}/export', [ReportController::class, 'batchExport']);
        Route::get('critical-errors', [ReportController::class, 'criticalErrors']);
        Route::get('acceptance/{batch}', [ReportController::class, 'acceptance']);
    });
});
