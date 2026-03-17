<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InspectionBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BatchController extends Controller
{
    /**
     * List batches - Admin sees all, Inspector sees own
     */
    public function index(Request $request): JsonResponse
    {
        $query = InspectionBatch::with(['user:id,name,username', 'checklist:id,name']);

        // If inspector, only show their batches
        if ($request->user()->role === 'inspector') {
            $query->where('user_id', $request->user()->id);
        }

        $batches = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $batches,
        ]);
    }

    /**
     * Get single batch with plan details
     */
    public function show(Request $request, int $batchId): JsonResponse
    {
        $batch = InspectionBatch::with([
            'user:id,name,username',
            'checklist:id,name,min_pass_score,max_critical_allowed',
            'planDetails',
        ])->findOrFail($batchId);

        // Check access for inspectors
        if ($request->user()->role === 'inspector' && $batch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Calculate progress
        $totalPlans = $batch->planDetails->count();
        $completedPlans = $batch->planDetails->where('status', 'done')->count();
        $progress = $totalPlans > 0 ? round(($completedPlans / $totalPlans) * 100) : 0;

        // Get cabinet info for each plan
        $planDetails = $batch->planDetails->map(function ($plan) {
            return [
                'id' => $plan->id,
                'batch_id' => $plan->batch_id,
                'cabinet_code' => $plan->cabinet_code,
                'status' => $plan->status,
                'cabinet' => \App\Models\Cabinet::find($plan->cabinet_code),
                'created_at' => $plan->created_at,
                'updated_at' => $plan->updated_at,
            ];
        });

        return response()->json([
            'data' => [
                'id' => $batch->id,
                'name' => $batch->name,
                'user' => $batch->user,
                'checklist_id' => $batch->checklist_id,
                'checklist' => $batch->checklist,
                'start_date' => $batch->start_date,
                'end_date' => $batch->end_date,
                'status' => $batch->status,
                'progress' => [
                    'total' => $totalPlans,
                    'completed' => $completedPlans,
                    'percentage' => $progress,
                ],
                'plan_details' => $planDetails,
                'created_at' => $batch->created_at,
            ],
        ]);
    }

    /**
     * Create new batch (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'checklist_id' => 'required|exists:checklists,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'cabinet_codes' => 'required|array|min:1',
            'cabinet_codes.*' => 'exists:cabinets,cabinet_code',
        ]);

        $batch = InspectionBatch::create([
            'name' => $request->name,
            'user_id' => $request->user_id,
            'checklist_id' => $request->checklist_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'pending',
        ]);

        // Create plan details for each cabinet
        foreach ($request->cabinet_codes as $cabinetCode) {
            $batch->planDetails()->create([
                'cabinet_code' => $cabinetCode,
                'status' => 'planned',
            ]);
        }

        return response()->json([
            'data' => $batch->load('planDetails'),
            'message' => 'Batch created successfully',
        ], 201);
    }

    /**
     * Update batch status (Admin only)
     */
    public function update(Request $request, int $batchId): JsonResponse
    {
        $batch = InspectionBatch::findOrFail($batchId);

        $request->validate([
            'status' => 'sometimes|in:pending,active,completed',
        ]);

        $batch->update($request->only(['status']));

        return response()->json([
            'data' => $batch,
            'message' => 'Batch updated successfully',
        ]);
    }
}
