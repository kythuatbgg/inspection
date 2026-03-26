<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\InspectionBatch;
use App\Models\PlanDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BatchController extends Controller
{
    /**
     * List batches - Admin sees all, Inspector sees own
     */
    public function index(Request $request): JsonResponse
    {
        $query = InspectionBatch::with(['user:id,name,username', 'checklist:id,name'])
            ->withCount('planDetails as plans_count')
            ->withCount(['planDetails as completed_count' => function ($q) {
                $q->where('status', 'done');
            }]);

        if ($request->has('search') && $request->search) {
            $searchTerm = strtolower($request->search);
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
        }

        if ($request->has('status') && $request->status) {
            if ($request->status === 'ongoing') {
                $query->where('status', '!=', 'completed');
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->has('approval_status') && $request->approval_status) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->has('created_by')) {
            // ProposalsView: inspector sees batches they CREATED (any status)
            $query->where('created_by', $request->created_by);
        } elseif ($request->user()->role === 'inspector') {
            // Dashboard/Tasks: inspector sees batches ASSIGNED to them (must be approved)
            $query->where('user_id', $request->user()->id);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $batches = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $batches->items(),
            'current_page' => $batches->currentPage(),
            'last_page' => $batches->lastPage(),
            'per_page' => $batches->perPage(),
            'total' => $batches->total(),
            'from' => $batches->firstItem(),
            'to' => $batches->lastItem()
        ]);
    }

    /**
     * Get single batch with plan details and progress
     */
    public function show(Request $request, int $batchId): JsonResponse
    {
        $batch = InspectionBatch::with([
            'user:id,name,username',
            'checklist:id,name,min_pass_score,max_critical_allowed',
            'planDetails.inspection',
            'planDetails.account',
        ])->findOrFail($batchId);

        if ($request->user()->role === 'inspector' && $batch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $totalPlans = $batch->planDetails->count();
        $completedPlans = $batch->planDetails->where('status', 'done')->count();
        $progress = $totalPlans > 0 ? round(($completedPlans / $totalPlans) * 100) : 0;

        $planDetails = $batch->planDetails->map(function ($plan) {
            return [
                'id' => $plan->id,
                'batch_id' => $plan->batch_id,
                'cabinet_code' => $plan->cabinet_code,
                'account_id' => $plan->account_id,
                'account' => $plan->account ? [
                    'id' => $plan->account->id,
                    'account_code' => $plan->account->account_code,
                ] : null,
                'status' => $plan->status,
                'review_status' => $plan->review_status,
                'review_note' => $plan->review_note,
                'reviewed_at' => $plan->reviewed_at,
                'cabinet' => \App\Models\Cabinet::find($plan->cabinet_code),
                'inspection' => $plan->inspection,
                'created_at' => $plan->created_at,
                'updated_at' => $plan->updated_at,
            ];
        });

        // Group plan_details by account for deployment batches
        $accounts = null;
        if ($batch->type === 'deployment') {
            $accountGroups = $batch->planDetails->groupBy('account_id');
            $accounts = $accountGroups->map(function ($plans) {
                $firstPlan = $plans->first();
                $account = $firstPlan->account;
                return [
                    'id' => $account?->id,
                    'account_code' => $account?->account_code,
                    'cabinet_codes' => $plans->pluck('cabinet_code')->values()->toArray(),
                    'plan_ids' => $plans->pluck('id')->values()->toArray(),
                ];
            })->values()->toArray();
        }

        return response()->json([
            'data' => [
                'id' => $batch->id,
                'name' => $batch->name,
                'type' => $batch->type,
                'user' => $batch->user,
                'checklist_id' => $batch->checklist_id,
                'checklist' => $batch->checklist,
                'start_date' => $batch->start_date,
                'end_date' => $batch->end_date,
                'status' => $batch->status,
                'approval_status' => $batch->approval_status,
                'approval_note' => $batch->approval_note,
                'closed_at' => $batch->closed_at,
                'progress' => [
                    'total' => $totalPlans,
                    'completed' => $completedPlans,
                    'percentage' => $progress,
                ],
                'plan_details' => $planDetails,
                'accounts' => $accounts,
                'created_at' => $batch->created_at,
            ],
        ]);
    }

    /**
     * Create new batch (Manager only)
     */
    public function store(Request $request): JsonResponse
    {
        $isCreatorInspector = $request->user()->role === 'inspector';
        $batchType = $request->input('type', 'inspection');

        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:inspection,deployment',
            'user_id' => $isCreatorInspector ? 'nullable' : 'required|exists:users,id',
            'checklist_id' => 'required|exists:checklists,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];

        $messages = [
            'name.required' => 'Tên lô kiểm tra là bắt buộc.',
            'name.max' => 'Tên lô không được vượt quá 255 ký tự.',
            'type.required' => 'Loại batch là bắt buộc.',
            'type.in' => 'Loại batch không hợp lệ.',
            'user_id.required' => 'Vui lòng chọn người kiểm tra.',
            'user_id.exists' => 'Người kiểm tra không tồn tại.',
            'checklist_id.required' => 'Vui lòng chọn checklist.',
            'checklist_id.exists' => 'Checklist không tồn tại.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'start_date.after_or_equal' => 'Ngày bắt đầu phải từ hôm nay trở đi.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
        ];

        // Validate based on batch type
        if ($batchType === 'inspection') {
            $rules['cabinet_codes'] = 'required|array|min:1';
            $rules['cabinet_codes.*'] = 'distinct|exists:cabinets,cabinet_code';
            $messages['cabinet_codes.required'] = 'Vui lòng chọn ít nhất 1 tủ cáp.';
            $messages['cabinet_codes.min'] = 'Vui lòng chọn ít nhất 1 tủ cáp.';
            $messages['cabinet_codes.*.distinct'] = 'Không được chọn tủ cáp trùng lặp.';
            $messages['cabinet_codes.*.exists'] = 'Mã tủ ":input" không tồn tại.';
        } elseif ($batchType === 'deployment') {
            $rules['accounts'] = 'required|array|min:1';
            $rules['accounts.*.account_code'] = 'required|string|max:100';
            $rules['accounts.*.cabinet_codes'] = 'required|array|min:1';
            $rules['accounts.*.cabinet_codes.*'] = 'distinct|exists:cabinets,cabinet_code';
            $messages['accounts.required'] = 'Vui lòng thêm ít nhất 1 account.';
            $messages['accounts.min'] = 'Vui lòng thêm ít nhất 1 account.';
            $messages['accounts.*.account_code.required'] = 'Mã account là bắt buộc.';
            $messages['accounts.*.cabinet_codes.required'] = 'Mỗi account phải có ít nhất 1 tủ cáp.';
            $messages['accounts.*.cabinet_codes.min'] = 'Mỗi account phải có ít nhất 1 tủ cáp.';
            $messages['accounts.*.cabinet_codes.*.distinct'] = 'Không được chọn tủ cáp trùng lặp trong cùng account.';
            $messages['accounts.*.cabinet_codes.*.exists'] = 'Mã tủ ":input" không tồn tại.';
        }

        $request->validate($rules, $messages);

        // Additional business validation for deployment batches
        if ($batchType === 'deployment') {
            $accountCodes = collect($request->input('accounts', []))
                ->pluck('account_code')
                ->map(fn ($code) => trim((string) $code));

            if ($accountCodes->duplicates()->isNotEmpty()) {
                return response()->json([
                    'message' => 'Mã account bị trùng trong cùng một batch.',
                    'errors' => [
                        'accounts' => ['Mã account bị trùng trong cùng một batch.']
                    ],
                ], 422);
            }

            $allCabinetCodes = collect($request->input('accounts', []))
                ->flatMap(fn ($acc) => $acc['cabinet_codes'] ?? []);

            if ($allCabinetCodes->duplicates()->isNotEmpty()) {
                return response()->json([
                    'message' => 'Một tủ cáp chỉ được gán cho một account trong cùng batch.',
                    'errors' => [
                        'accounts' => ['Một tủ cáp chỉ được gán cho một account trong cùng batch.']
                    ],
                ], 422);
            }
        }

        $userId = $isCreatorInspector ? $request->user()->id : $request->user_id;

        $assignedUser = User::find($userId);
        if (!$assignedUser || $assignedUser->role !== 'inspector') {
            return response()->json([
                'message' => __('messages.inspector_role_required'),
                'errors' => ['user_id' => [__('messages.inspector_role_required')]]
            ], 422);
        }

        $batch = InspectionBatch::create([
            'name' => $request->name,
            'type' => $batchType,
            'user_id' => $userId,
            'created_by' => $request->user()->id,
            'checklist_id' => $request->checklist_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'pending',
            'approval_status' => $isCreatorInspector ? 'pending' : 'approved',
        ]);

        if ($batchType === 'inspection') {
            foreach ($request->cabinet_codes as $cabinetCode) {
                $batch->planDetails()->create([
                    'cabinet_code' => $cabinetCode,
                    'status' => 'planned',
                ]);
            }
        } else {
            // Deployment: create accounts and link plan_details
            foreach ($request->accounts as $accountData) {
                $account = Account::firstOrCreate([
                    'account_code' => $accountData['account_code'],
                ]);

                foreach ($accountData['cabinet_codes'] as $cabinetCode) {
                    $batch->planDetails()->create([
                        'cabinet_code' => $cabinetCode,
                        'account_id' => $account->id,
                        'status' => 'planned',
                    ]);
                }
            }
        }

        return response()->json([
            'data' => $batch->load('planDetails'),
            'message' => __('messages.batch_create_success'),
        ], 201);
    }

    /**
     * Update batch info (Manager only, not completed)
     */
    public function update(Request $request, int $batchId): JsonResponse
    {
        $batch = InspectionBatch::findOrFail($batchId);

        if ($batch->status === 'completed') {
            return response()->json(['message' => __('messages.batch_cannot_edit_completed')], 422);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'user_id' => 'sometimes|exists:users,id',
            'checklist_id' => 'sometimes|exists:checklists,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:pending,active,completed',
        ]);

        if ($request->has('user_id')) {
            $assignedUser = User::find($request->user_id);
            if ($assignedUser && $assignedUser->role !== 'inspector') {
                return response()->json([
                    'message' => __('messages.inspector_role_required'),
                ], 422);
            }
        }

        $batch->update($request->only(['name', 'user_id', 'checklist_id', 'start_date', 'end_date', 'status']));

        return response()->json([
            'data' => $batch->fresh(),
            'message' => __('messages.batch_update_success'),
        ]);
    }

    /**
     * Delete batch (Manager only, only pending batches)
     */
    public function destroy(Request $request, int $batchId): JsonResponse
    {
        $batch = InspectionBatch::findOrFail($batchId);

        $hasInspections = $batch->planDetails()
            ->whereHas('inspections')
            ->exists();

        if ($hasInspections && !$request->boolean('force')) {
            return response()->json([
                'message' => __('messages.batch_has_data_force'),
                'has_data' => true,
            ], 422);
        }

        // Cascade delete: inspections → inspection_details → plan_details → batch
        foreach ($batch->planDetails as $plan) {
            foreach ($plan->inspections as $inspection) {
                $inspection->details()->delete();
                $inspection->delete();
            }
        }
        $batch->planDetails()->delete();
        $batch->delete();

        return response()->json([
            'message' => __('messages.batch_delete_success'),
        ]);
    }

    /**
     * Get inspection results for each cabinet in batch
     */
    public function results(int $batchId): JsonResponse
    {
        $batch = InspectionBatch::with([
            'planDetails.inspection.details',
            'checklist:id,name,min_pass_score,max_critical_allowed',
        ])->findOrFail($batchId);

        $results = $batch->planDetails->map(function ($plan) {
            $inspection = $plan->inspection;

            $data = [
                'plan_id' => $plan->id,
                'cabinet_code' => $plan->cabinet_code,
                'cabinet' => \App\Models\Cabinet::find($plan->cabinet_code),
                'status' => $plan->status,
                'review_status' => $plan->review_status ?? 'pending',
                'review_note' => $plan->review_note,
                'reviewed_at' => $plan->reviewed_at,
                'inspection' => null,
            ];

            if ($inspection) {
                $details = $inspection->details ?? collect();
                $totalItems = $details->count();
                $failedItems = $details->where('is_failed', true)->count();
                $passedItems = $totalItems - $failedItems;

                $data['inspection'] = [
                    'id' => $inspection->id,
                    'final_result' => $inspection->final_result,
                    'total_score' => $inspection->total_score,
                    'critical_errors_count' => $inspection->critical_errors_count,
                    'overall_photos' => $inspection->overall_photos ?? [],
                    'total_items' => $totalItems,
                    'passed_items' => $passedItems,
                    'failed_items' => $failedItems,
                    'created_at' => $inspection->created_at,
                ];
            }

            return $data;
        });

        // Summary
        $totalPlans = $results->count();
        $inspected = $results->filter(fn($r) => $r['inspection'] !== null)->count();
        $passed = $results->filter(fn($r) => $r['inspection'] && strtoupper($r['inspection']['final_result']) === 'PASS')->count();
        $failed = $results->filter(fn($r) => $r['inspection'] && strtoupper($r['inspection']['final_result']) === 'FAIL')->count();
        $reviewed = $results->where('review_status', '!=', 'pending')->count();

        return response()->json([
            'data' => $results,
            'summary' => [
                'total' => $totalPlans,
                'inspected' => $inspected,
                'not_inspected' => $totalPlans - $inspected,
                'passed' => $passed,
                'failed' => $failed,
                'reviewed' => $reviewed,
                'pending_review' => $inspected - $reviewed,
            ],
            'batch' => [
                'id' => $batch->id,
                'name' => $batch->name,
                'status' => $batch->status,
                'approval_status' => $batch->approval_status,
                'checklist' => $batch->checklist,
            ],
        ]);
    }

    /**
     * Close/finalize batch (Manager only)
     */
    public function close(int $batchId): JsonResponse
    {
        $batch = InspectionBatch::with('planDetails.inspection')
            ->findOrFail($batchId);

        if ($batch->status === 'completed') {
            return response()->json(['message' => 'Lô này đã được kết thúc.'], 422);
        }

        // Check: all plans must be inspected
        $uninspected = $batch->planDetails->filter(fn($p) => $p->status !== 'done')->count();
        if ($uninspected > 0) {
            return response()->json([
                'message' => "Còn {$uninspected} tủ chưa kiểm tra. Không thể kết thúc.",
            ], 422);
        }

        // Check: all plans must be reviewed
        $unreviewed = $batch->planDetails->filter(fn($p) => ($p->review_status ?? 'pending') === 'pending')->count();
        if ($unreviewed > 0) {
            return response()->json([
                'message' => "Còn {$unreviewed} tủ chưa duyệt. Vui lòng duyệt tất cả trước khi kết thúc.",
            ], 422);
        }

        $batch->update([
            'status' => 'completed',
            'closed_at' => now(),
        ]);

        // Summary
        $total = $batch->planDetails->count();
        $approved = $batch->planDetails->where('review_status', 'approved')->count();
        $rejected = $batch->planDetails->where('review_status', 'rejected')->count();

        return response()->json([
            'message' => __('messages.batch_closed'),
            'summary' => [
                'total' => $total,
                'approved' => $approved,
                'rejected' => $rejected,
                'closed_at' => $batch->closed_at,
            ],
        ]);
    }

    /**
     * Reopen/rollback a closed batch (Admin/Manager only)
     */
    public function reopen(int $batchId): JsonResponse
    {
        $batch = InspectionBatch::findOrFail($batchId);

        if ($batch->status !== 'completed') {
            return response()->json([
                'message' => 'Lô kiểm tra này chưa kết thúc, không thể mở lại.'
            ], 422);
        }

        $batch->update([
            'status' => 'active',
            'closed_at' => null,
        ]);

        return response()->json([
            'message' => __('messages.batch_reopened'),
            'batch' => $batch
        ]);
    }

    /**
     * Add cabinets to batch
     * POST /api/batches/{batch}/cabinets
     */
    public function addCabinets(Request $request, int $batchId): JsonResponse
    {
        $batch = InspectionBatch::findOrFail($batchId);
        if ($batch->status === 'completed') {
            return response()->json(['message' => 'Không thể thêm tủ vào lô đã kết thúc.'], 422);
        }

        $request->validate([
            'cabinet_codes' => 'required|array',
            'cabinet_codes.*' => 'string|exists:cabinets,cabinet_code',
        ]);

        $codes = $request->cabinet_codes;
        // Exclude codes that are already in plan
        $existingCodes = $batch->planDetails()->pluck('cabinet_code')->toArray();
        $newCodes = array_diff($codes, $existingCodes);

        $insertedCount = 0;
        foreach ($newCodes as $code) {
            PlanDetail::create([
                'batch_id' => $batch->id,
                'cabinet_code' => $code,
                'status' => 'planned',
                'review_status' => 'pending'
            ]);
            $insertedCount++;
        }

        return response()->json([
            'message' => "Đã thêm thành công {$insertedCount} tủ.",
            'added' => $insertedCount
        ]);
    }

    /**
     * Remove cabinet from batch
     * DELETE /api/batches/{batch}/plans/{plan}
     * Supports ?force=true to cascade-delete inspections
     */
    public function removeCabinet(Request $request, int $batchId, int $planId): JsonResponse
    {
        $batch = InspectionBatch::findOrFail($batchId);
        if ($batch->status === 'completed') {
            return response()->json(['message' => 'Không thể xóa tủ khỏi lô đã kết thúc.'], 422);
        }

        $plan = $batch->planDetails()->where('id', $planId)->firstOrFail();

        $hasInspection = $plan->status === 'done' || $plan->inspections()->exists();

        if ($hasInspection && !$request->boolean('force')) {
            return response()->json([
                'message' => 'Tủ này đã có kết quả kiểm tra. Thêm ?force=true để xóa.',
                'has_inspection' => true,
            ], 422);
        }

        // Cascade delete: inspection_details → inspections → plan
        if ($hasInspection) {
            foreach ($plan->inspections as $inspection) {
                $inspection->details()->delete();
                $inspection->delete();
            }
        }

        $plan->delete();

        return response()->json([
            'message' => 'Đã xóa tủ khỏi lô thành công.'
        ]);
    }

    /**
     * Swap cabinet in batch (replace old cabinet with new one)
     * PATCH /api/batches/{batch}/plans/{plan}/swap
     */
    public function swapCabinet(Request $request, int $batchId, int $planId): JsonResponse
    {
        $batch = InspectionBatch::findOrFail($batchId);
        if ($batch->status === 'completed') {
            return response()->json(['message' => 'Không thể thay đổi tủ trong lô đã kết thúc.'], 422);
        }

        $request->validate([
            'new_cabinet_code' => 'required|string|exists:cabinets,cabinet_code',
        ], [
            'new_cabinet_code.required' => 'Vui lòng chọn tủ mới.',
            'new_cabinet_code.exists' => 'Mã tủ mới không tồn tại.',
        ]);

        // Check new cabinet is not already in this batch
        $alreadyExists = $batch->planDetails()
            ->where('cabinet_code', $request->new_cabinet_code)
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'message' => 'Tủ mới đã tồn tại trong lô này.',
            ], 422);
        }

        $plan = $batch->planDetails()->where('id', $planId)->firstOrFail();
        $oldCode = $plan->cabinet_code;
        $hasInspection = $plan->status === 'done' || $plan->inspections()->exists();

        if ($hasInspection && !$request->boolean('force')) {
            return response()->json([
                'message' => "Tủ [{$oldCode}] đã có kết quả kiểm tra. Thêm ?force=true để thay thế (dữ liệu kiểm tra cũ sẽ bị xóa).",
                'has_inspection' => true,
            ], 422);
        }

        // Cascade delete old inspection data if force
        if ($hasInspection) {
            foreach ($plan->inspections as $inspection) {
                $inspection->details()->delete();
                $inspection->delete();
            }
        }

        // Swap the cabinet code and reset status
        $plan->update([
            'cabinet_code' => $request->new_cabinet_code,
            'status' => 'planned',
            'review_status' => 'pending',
            'review_note' => null,
            'reviewed_at' => null,
        ]);

        return response()->json([
            'message' => "Đã thay thế tủ [{$oldCode}] bằng [{$request->new_cabinet_code}] thành công.",
            'old_cabinet_code' => $oldCode,
            'new_cabinet_code' => $request->new_cabinet_code,
        ]);
    }

    /**
     * Approve proposed batch (Manager only)
     */
    public function approve(int $batchId): JsonResponse
    {
        $batch = InspectionBatch::findOrFail($batchId);
        
        $batch->update([
            'approval_status' => 'approved',
            'approval_note' => null,
        ]);

        return response()->json([
            'message' => __('messages.batch_approved'),
            'data' => $batch
        ]);
    }

    /**
     * Reject proposed batch (Manager only)
     */
    public function reject(Request $request, int $batchId): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000'
        ], [
            'reason.required' => 'Vui lòng nhập lý do từ chối.'
        ]);

        $batch = InspectionBatch::findOrFail($batchId);
        
        $batch->update([
            'approval_status' => 'rejected',
            'approval_note' => $request->reason,
        ]);

        return response()->json([
            'message' => __('messages.batch_rejected'),
            'data' => $batch
        ]);
    }
}
