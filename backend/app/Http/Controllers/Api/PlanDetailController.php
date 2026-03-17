<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanDetail;
use App\Models\InspectionBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlanDetailController extends Controller
{
    /**
     * Get plan details for a batch
     */
    public function index(Request $request, int $batchId): JsonResponse
    {
        $batch = InspectionBatch::findOrFail($batchId);

        // Check access
        if ($request->user()->role === 'inspector' && $batch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $planDetails = $batch->planDetails()
            ->with('cabinet')
            ->orderBy('status')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $planDetails,
        ]);
    }

    /**
     * Mark plan as complete (update status to done)
     */
    public function markComplete(Request $request, int $planId): JsonResponse
    {
        $plan = PlanDetail::findOrFail($planId);

        // Verify inspector owns this plan
        $batch = $plan->batch;
        if ($request->user()->role === 'inspector' && $batch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $plan->update(['status' => 'done']);

        // Check if all plans in batch are done, update batch status
        $this->checkBatchCompletion($batch);

        return response()->json([
            'data' => $plan->load('cabinet'),
            'message' => 'Plan marked as complete',
        ]);
    }

    /**
     * Check if all plans in batch are complete
     */
    private function checkBatchCompletion(InspectionBatch $batch): void
    {
        $totalPlans = $batch->planDetails()->count();
        $completedPlans = $batch->planDetails()->where('status', 'done')->count();

        if ($totalPlans === $completedPlans && $batch->status !== 'completed') {
            $batch->update(['status' => 'completed']);
        }
    }
}
