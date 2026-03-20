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

        try {
            // ✅ OPTIMIZED: Load plans với inspection trong 1 API call
            $planDetails = $batch->planDetails()
                ->with(['cabinet', 'inspection'])
                ->orderBy('status')
                ->orderBy('id')
                ->get();
        } catch (\Exception $e) {
            // Fallback: load without cabinet relation if cabinet_code is corrupted
            $planDetails = $batch->planDetails()
                ->with('inspection')
                ->orderBy('status')
                ->orderBy('id')
                ->get();
        }

        return response()->json([
            'data' => $planDetails,
        ]);
    }

    /**
     * Get a single plan detail by ID
     */
    public function show(Request $request, int $planId): JsonResponse
    {
        $plan = PlanDetail::with('cabinet', 'batch')->findOrFail($planId);

        // Check access
        if ($request->user()->role === 'inspector' && $plan->batch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $plan,
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
     * Admin/Manager review a plan (approve/reject)
     */
    public function review(Request $request, int $planId): JsonResponse
    {
        $plan = PlanDetail::findOrFail($planId);
        $batch = $plan->batch;

        if ($batch->status === 'completed') {
            return response()->json(['message' => 'Không thể duyệt - lô đã kết thúc.'], 422);
        }

        if ($plan->status !== 'done') {
            return response()->json(['message' => 'Tủ này chưa được kiểm tra.'], 422);
        }

        $request->validate([
            'review_status' => 'required|in:approved,rejected,pending',
            'review_note' => 'nullable|string|max:500',
        ]);

        $reviewedAt = $request->review_status === 'pending' ? null : now();
        $reviewNote = $request->review_status === 'pending' ? null : $request->review_note;

        $plan->update([
            'review_status' => $request->review_status,
            'review_note' => $reviewNote,
            'reviewed_at' => $reviewedAt,
        ]);

        $msg = 'Đã lưu trạng thái duyệt.';
        if ($request->review_status === 'approved') $msg = 'Đã duyệt tủ.';
        elseif ($request->review_status === 'rejected') $msg = 'Đã từ chối tủ.';
        elseif ($request->review_status === 'pending') $msg = 'Đã bỏ duyệt tủ.';

        return response()->json([
            'data' => $plan->fresh(),
            'message' => $msg,
        ]);
    }

    /**
     * Check if all plans in batch are complete → set batch to active
     */
    private function checkBatchCompletion(InspectionBatch $batch): void
    {
        $totalPlans = $batch->planDetails()->count();
        $completedPlans = $batch->planDetails()->where('status', 'done')->count();

        if ($completedPlans > 0 && $batch->status === 'pending') {
            $batch->update(['status' => 'active']);
        }
    }
}
