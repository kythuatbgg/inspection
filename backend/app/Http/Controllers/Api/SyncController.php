<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\InspectionDetail;
use App\Models\PlanDetail;
use App\Services\ScoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SyncController extends Controller
{
    protected ScoringService $scoringService;

    public function __construct(ScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    /**
     * Sync offline inspections to server
     * Accepts bulk data from offline mobile app
     */
    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'inspections' => 'required|array',
            'inspections.*.plan_detail_id' => 'required|exists:plan_details,id',
            'inspections.*.checklist_id' => 'required|exists:checklists,id',
            'inspections.*.cabinet_code' => 'required|string',
            'inspections.*.lat' => 'nullable|numeric',
            'inspections.*.lng' => 'nullable|numeric',
            'inspections.*.details' => 'required|array',
            'inspections.*.details.*.item_id' => 'required|exists:checklist_items,id',
            'inspections.*.details.*.is_failed' => 'required|boolean',
            'inspections.*.details.*.score_awarded' => 'required|integer',
            'inspections.*.details.*.image_base64' => 'nullable|string',
        ]);

        $syncedCount = 0;
        $errors = [];

        DB::transaction(function () use ($request, &$syncedCount, &$errors) {
            foreach ($request->inspections as $inspectionData) {
                try {
                    // Check if already synced (idempotency)
                    $existing = Inspection::where('plan_detail_id', $inspectionData['plan_detail_id'])->first();
                    if ($existing) {
                        $errors[] = [
                            'plan_detail_id' => $inspectionData['plan_detail_id'],
                            'error' => 'Already synced',
                        ];
                        continue;
                    }

                    // Process images first
                    $details = $this->processDetailsWithImages($inspectionData['details'] ?? []);

                    // Create inspection
                    $inspection = Inspection::create([
                        'user_id' => $request->user()->id,
                        'checklist_id' => $inspectionData['checklist_id'],
                        'plan_detail_id' => $inspectionData['plan_detail_id'],
                        'cabinet_code' => $inspectionData['cabinet_code'],
                        'lat' => $inspectionData['lat'] ?? null,
                        'lng' => $inspectionData['lng'] ?? null,
                        'total_score' => 0,
                        'critical_errors_count' => 0,
                        'final_result' => null,
                    ]);

                    // Create details
                    foreach ($details as $detail) {
                        InspectionDetail::create([
                            'inspection_id' => $inspection->id,
                            'item_id' => $detail['item_id'],
                            'is_failed' => $detail['is_failed'],
                            'score_awarded' => $detail['score_awarded'],
                            'image_url' => $detail['image_url'],
                        ]);
                    }

                    // Calculate score
                    $inspection = $this->scoringService->verifyAndScore($inspection);

                    // Mark plan as done
                    PlanDetail::where('id', $inspectionData['plan_detail_id'])
                        ->update(['status' => 'done']);

                    $syncedCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'plan_detail_id' => $inspectionData['plan_detail_id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'synced' => $syncedCount,
            'errors' => $errors,
            'message' => "Synced {$syncedCount} inspections",
        ]);
    }

    /**
     * Process details and save base64 images
     */
    protected function processDetailsWithImages(array $details): array
    {
        $processed = [];

        foreach ($details as $detail) {
            $imageUrl = null;

            // Handle base64 image
            if (!empty($detail['image_base64'])) {
                try {
                    $imageUrl = $this->saveBase64Image(
                        $detail['image_base64'],
                        $detail['item_id']
                    );
                } catch (\Exception $e) {
                    // Log error but continue
                }
            }

            $processed[] = [
                'item_id' => $detail['item_id'],
                'is_failed' => $detail['is_failed'],
                'score_awarded' => $detail['score_awarded'],
                'image_url' => $imageUrl,
            ];
        }

        return $processed;
    }

    /**
     * Save base64 image to storage
     */
    protected function saveBase64Image(string $base64Data, int $itemId): string
    {
        // Extract image data from base64 string
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
            $extension = $matches[1];
            $imageData = substr($base64Data, strpos($base64Data, ',') + 1);
        } else {
            $extension = 'jpg';
            $imageData = $base64Data;
        }

        $filename = sprintf(
            'inspections/%d_%d_%s.%s',
            auth()->id(),
            $itemId,
            time(),
            $extension
        );

        Storage::disk('public')->put(
            $filename,
            base64_decode($imageData)
        );

        return $filename;
    }
}
