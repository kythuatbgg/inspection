<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InspectionBatch;
use App\Models\PlanDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $batchQuery = InspectionBatch::query();

        if ($request->user()->role === 'inspector') {
            $batchQuery->where('user_id', $request->user()->id);
        }

        $totalBatches = (clone $batchQuery)->count();
        $completedBatches = (clone $batchQuery)->where('status', 'completed')->count();
        $batchCompletedPercent = $totalBatches > 0 ? round(($completedBatches / $totalBatches) * 100) : 0;
        
        // "Chờ duyệt": Lô chưa kết thúc (status != completed) nhưng TẤT CẢ các tủ (planDetails) đều đã 'done'.
        $waitingApprovalBatches = (clone $batchQuery)
            ->where('status', '!=', 'completed')
            ->whereHas('planDetails')
            ->whereDoesntHave('planDetails', function ($q) {
                $q->where('status', '!=', 'done');
            })
            ->count();

        $planQuery = PlanDetail::query();
        if ($request->user()->role === 'inspector') {
            $planQuery->whereHas('batch', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        $totalPlans = (clone $planQuery)->count();
        $completedPlans = (clone $planQuery)->where('status', 'done')->count();
        $planCompletedPercent = $totalPlans > 0 ? round(($completedPlans / $totalPlans) * 100) : 0;

        $passedPlans = (clone $planQuery)->where('status', 'done')
            ->whereHas('inspection', function($q) {
                $q->where('final_result', 'PASS');
            })->count();
            
        $failedPlans = (clone $planQuery)->where('status', 'done')
            ->whereHas('inspection', function($q) {
                $q->where('final_result', 'FAIL');
            })->count();

        return response()->json([
            'batches' => [
                'total' => $totalBatches,
                'completed' => $completedBatches,
                'completed_percent' => $batchCompletedPercent,
                'waiting_approval' => $waitingApprovalBatches,
            ],
            'plans' => [
                'total' => $totalPlans,
                'completed' => $completedPlans,
                'completed_percent' => $planCompletedPercent,
                'passed' => $passedPlans,
                'failed' => $failedPlans,
            ],
            // Legacy fallbacks
            'total_batches' => $totalBatches,
            'completed' => $completedBatches,
            'pending' => $waitingApprovalBatches,
            'in_progress' => 0,
            'failed' => $failedPlans,
        ]);
    }
}
