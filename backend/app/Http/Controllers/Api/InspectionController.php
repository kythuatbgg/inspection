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
use Illuminate\Support\Facades\Log;

class InspectionController extends Controller
{
    protected ScoringService $scoringService;

    public function __construct(ScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    /**
     * Get inspection for a plan with full nested data (1 API Call)
     * Returns: plan -> batch -> checklist -> items
     */
    public function show(Request $request, int $planId): JsonResponse
    {
        // Load plan with FULL nested relationships (1 query với eager load)
        $plan = PlanDetail::with([
            'cabinet',
            'batch.checklist.items'  // Full nested: batch -> checklist -> items
        ])->findOrFail($planId);

        if ($request->user()->role === 'inspector' && $plan->batch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $inspection = Inspection::where('plan_detail_id', $planId)
            ->with(['details.item', 'user:id,name'])
            ->first();

        if (!$inspection) {
            return response()->json([
                'data' => null,
                'plan' => $plan,
                'checklist_items' => $plan->batch->checklist->items ?? [],
                'message' => 'No inspection found for this plan',
            ]);
        }

        $data = $inspection->toArray();
        $data['overall_photo_urls'] = $inspection->overall_photos ?? [];

        foreach ($data['details'] as &$detail) {
            $detail['failure_proof_url'] = $detail['image_url'] ?? null;
        }

        return response()->json([
            'data' => $data,
            'plan' => $plan,
            'checklist_items' => $plan->batch->checklist->items ?? [],
        ]);
    }

    /**
     * Store new inspection with details.
     * Photos are already uploaded via /upload endpoint.
     * URLs are stored directly in DB - no Spatie processing needed.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'plan_detail_id' => 'required|exists:plan_details,id',
            'checklist_id' => 'required|exists:checklists,id',
            'cabinet_code' => 'required|exists:cabinets,cabinet_code',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'overall_photos' => 'required|array|min:4',
            'overall_photos.*' => 'required|string',
            'details' => 'required|array|min:1',
            'details.*.item_id' => 'required|exists:checklist_items,id',
            'details.*.is_failed' => 'required|boolean',
            'details.*.score_awarded' => 'required|integer|min:0',
            'details.*.image_url' => 'nullable|string',
            'details.*.note' => 'nullable|string',
        ]);

        $plan = PlanDetail::findOrFail($request->plan_detail_id);
        if ($request->user()->role === 'inspector' && $plan->batch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (Inspection::where('plan_detail_id', $request->plan_detail_id)->exists()) {
            return response()->json([
                'message' => 'Tủ này đã được kiểm tra rồi.',
            ], 422);
        }

        // Strip domains to store relative URLs only
        $cleanOverallPhotos = array_map(function ($url) {
            if ($url && preg_match('/\/storage\/(.+)$/', $url, $matches)) {
                return '/storage/' . $matches[1];
            }
            return $url;
        }, $request->overall_photos ?? []);

        // Single fast DB transaction - no file I/O
        $inspection = DB::transaction(function () use ($request, $cleanOverallPhotos) {
            $inspection = Inspection::create([
                'user_id' => $request->user()->id,
                'checklist_id' => $request->checklist_id,
                'plan_detail_id' => $request->plan_detail_id,
                'cabinet_code' => $request->cabinet_code,
                'overall_photos' => $cleanOverallPhotos,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'total_score' => 0,
                'critical_errors_count' => 0,
                'final_result' => null,
            ]);

            foreach ($request->details as $detail) {
                $cleanImageUrl = $detail['image_url'] ?? null;
                if ($cleanImageUrl && preg_match('/\/storage\/(.+)$/', $cleanImageUrl, $matches)) {
                    $cleanImageUrl = '/storage/' . $matches[1];
                }

                InspectionDetail::create([
                    'inspection_id' => $inspection->id,
                    'item_id' => $detail['item_id'],
                    'is_failed' => $detail['is_failed'],
                    'score_awarded' => $detail['score_awarded'],
                    'image_url' => $cleanImageUrl,
                    'note' => $detail['note'] ?? null,
                ]);
            }

            return $inspection;
        });

        // Calculate score
        $inspection = $this->scoringService->verifyAndScore($inspection);

        // Mark plan as done
        $plan->update(['status' => 'done']);

        return response()->json([
            'data' => $inspection->load('details'),
            'message' => 'Lưu kết quả kiểm tra thành công.',
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
