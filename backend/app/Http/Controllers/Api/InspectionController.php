<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\PlanDetail;
use App\Models\InspectionDetail;
use App\Services\ScoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InspectionController extends Controller
{
    protected ScoringService $scoringService;

    public function __construct(ScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    /**
     * Get inspection for a plan (if exists)
     */
    public function show(Request $request, int $planId): JsonResponse
    {
        $plan = PlanDetail::with('cabinet', 'batch.checklist')->findOrFail($planId);

        // Check access
        if ($request->user()->role === 'inspector' && $plan->batch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $inspection = Inspection::where('plan_detail_id', $planId)
            ->with(['details.item', 'user:id,name'])
            ->first();

        if (!$inspection) {
            return response()->json([
                'data' => null,
                'message' => 'No inspection found for this plan',
            ]);
        }

        return response()->json([
            'data' => $inspection,
        ]);
    }

    /**
     * Store new inspection with details
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'plan_detail_id' => 'required|exists:plan_details,id',
            'checklist_id' => 'required|exists:checklists,id',
            'cabinet_code' => 'required|exists:cabinets,cabinet_code',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'details' => 'required|array|min:1',
            'details.*.item_id' => 'required|exists:checklist_items,id',
            'details.*.is_failed' => 'required|boolean',
            'details.*.score_awarded' => 'required|integer|min:0',
        ]);

        // Verify plan belongs to user
        $plan = PlanDetail::findOrFail($request->plan_detail_id);
        if ($request->user()->role === 'inspector' && $plan->batch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if inspection already exists for this plan
        $existingInspection = Inspection::where('plan_detail_id', $request->plan_detail_id)->first();
        if ($existingInspection) {
            return response()->json([
                'message' => 'Inspection already exists for this plan. Use update endpoint.',
            ], 422);
        }

        // Create inspection with details in transaction
        $inspection = DB::transaction(function () use ($request) {
            $inspection = Inspection::create([
                'user_id' => $request->user()->id,
                'checklist_id' => $request->checklist_id,
                'plan_detail_id' => $request->plan_detail_id,
                'cabinet_code' => $request->cabinet_code,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'total_score' => 0,
                'critical_errors_count' => 0,
                'final_result' => null,
            ]);

            // Create inspection details
            foreach ($request->details as $detail) {
                InspectionDetail::create([
                    'inspection_id' => $inspection->id,
                    'item_id' => $detail['item_id'],
                    'is_failed' => $detail['is_failed'],
                    'score_awarded' => $detail['score_awarded'],
                    'image_url' => $detail['image_url'] ?? null,
                ]);
            }

            return $inspection;
        });

        // Calculate score and result
        $inspection = $this->scoringService->verifyAndScore($inspection);

        // Mark plan as done
        $plan->update(['status' => 'done']);

        return response()->json([
            'data' => $inspection->load('details'),
            'message' => 'Inspection completed successfully',
        ], 201);
    }

    /**
     * Get inspection details
     */
    public function details(int $inspectionId): JsonResponse
    {
        $inspection = Inspection::with('details.item')->findOrFail($inspectionId);

        return response()->json([
            'data' => $inspection->details,
        ]);
    }
}
