# API Controllers Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Xây dựng RESTful API Controllers cho hệ thống FSM Inspection với authentication và offline sync

**Architecture:** Laravel 11 API-only với Sanctum authentication. Controllers sử dụng resource pattern với JSON responses. Tách biệt logic nghiệp vụ (scoring) ra Service classes.

**Tech Stack:** Laravel 11, Laravel Sanctum, PostgreSQL

---

## File Structure

```
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           ├── AuthController.php       # Login, logout, profile
│   │           ├── CabinetController.php   # Master data tủ cáp
│   │           ├── ChecklistController.php # Checklist & items
│   │           ├── BatchController.php     # Inspection batches
│   │           ├── PlanDetailController.php # Plan details
│   │           ├── InspectionController.php # Inspections & details
│   │           └── SyncController.php      # Offline sync
│   ├── Services/
│   │   └── ScoringService.php              # Business logic chấm điểm
│   └── Middleware/
│       └── EnsureTokenIsValid.php         # Token validation
├── routes/
│   └── api.php                            # API routes (create)
└── config/
    └── auth.php                           # Sanctum config
```

---

## Chunk 1: Cấu trúc cơ bản & Authentication

### Task 1: Cấu hình API Routes và Sanctum

**Files:**
- Create: `backend/routes/api.php`
- Modify: `backend/bootstrap/app.php`

- [ ] **Step 1: Tạo file api.php với route definitions**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CabinetController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\InspectionController;
use App\Http\Controllers\Api\SyncController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Master Data
    Route::apiResource('cabinets', CabinetController::class);

    // Checklists
    Route::get('checklists/{checklist}/items', [ChecklistController::class, 'items']);
    Route::apiResource('checklists', ChecklistController::class);

    // Batches
    Route::apiResource('batches', BatchController::class);

    // Plan Details
    Route::get('batches/{batch}/plans', [PlanDetailController::class, 'index']);
    Route::patch('plans/{plan}/complete', [PlanDetailController::class, 'markComplete']);

    // Inspections
    Route::get('plans/{plan}/inspection', [InspectionController::class, 'show']);
    Route::post('inspections', [InspectionController::class, 'store']);
    Route::get('inspections/{inspection}/details', [InspectionController::class, 'details']);

    // Sync (offline)
    Route::post('/sync', [SyncController::class, 'sync']);
});
```

- [ ] **Step 2: Cập nhật bootstrap/app.php để thêm api routes**

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',  // Thêm dòng này
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // ... rest of file
```

- [ ] **Step 3: Kiểm tra routes được load**

Run: `php artisan route:list --path=api`
Expected: Hiển thị các routes với prefix api

---

### Task 2: AuthController - Authentication

**Files:**
- Create: `backend/app/Http/Controllers/Api/AuthController.php`

- [ ] **Step 1: Tạo AuthController với login, logout, me**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Revoke old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'lang_pref' => $user->lang_pref,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Get current user info
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role,
            'lang_pref' => $user->lang_pref,
        ]);
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
```

- [ ] **Step 2: Test login endpoint**

Run: `php artisan serve --port=8000` (trong terminal khác)
Then: `curl -X POST http://localhost:8000/api/login -H "Content-Type: application/json" -d '{"username":"admin_quan","password":"password123"}'`
Expected: JSON response với user và token

---

## Chunk 2: Master Data & Checklist Controllers

### Task 3: CabinetController - Master Data

**Files:**
- Create: `backend/app/Http/Controllers/Api/CabinetController.php`

- [ ] **Step 1: Tạo CabinetController**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabinet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CabinetController extends Controller
{
    /**
     * List all cabinets
     */
    public function index(Request $request): JsonResponse
    {
        $query = Cabinet::query();

        // Filter by BTS site
        if ($request->has('bts_site')) {
            $query->where('bts_site', $request->bts_site);
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cabinet_code', 'ilike', "%{$search}%")
                  ->orWhere('name', 'ilike', "%{$search}%");
            });
        }

        $cabinets = $query->orderBy('cabinet_code')->get();

        return response()->json([
            'data' => $cabinets,
        ]);
    }

    /**
     * Get single cabinet
     */
    public function show(string $cabinetCode): JsonResponse
    {
        $cabinet = Cabinet::findOrFail($cabinetCode);

        return response()->json([
            'data' => $cabinet,
        ]);
    }

    /**
     * Store new cabinet (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cabinet_code' => 'required|unique:cabinets,cabinet_code',
            'bts_site' => 'required|string',
            'name' => 'required|string',
            'type' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $cabinet = Cabinet::create($request->all());

        return response()->json([
            'data' => $cabinet,
            'message' => 'Cabinet created successfully',
        ], 201);
    }

    /**
     * Update cabinet (Admin only)
     */
    public function update(Request $request, string $cabinetCode): JsonResponse
    {
        $cabinet = Cabinet::findOrFail($cabinetCode);

        $request->validate([
            'bts_site' => 'sometimes|string',
            'name' => 'sometimes|string',
            'type' => 'sometimes|string',
            'lat' => 'sometimes|numeric',
            'lng' => 'sometimes|numeric',
        ]);

        $cabinet->update($request->all());

        return response()->json([
            'data' => $cabinet,
            'message' => 'Cabinet updated successfully',
        ]);
    }

    /**
     * Delete cabinet (Admin only)
     */
    public function destroy(string $cabinetCode): JsonResponse
    {
        $cabinet = Cabinet::findOrFail($cabinetCode);
        $cabinet->delete();

        return response()->json([
            'message' => 'Cabinet deleted successfully',
        ]);
    }
}
```

- [ ] **Step 2: Test cabinet endpoints**

Run: Test với Postman/curl sau khi login
- GET /api/cabinets
- GET /api/cabinets?bts_site=BTS-TAK-01

---

### Task 4: ChecklistController - Checklists & Items

**Files:**
- Create: `backend/app/Http/Controllers/Api/ChecklistController.php`

- [ ] **Step 1: Tạo ChecklistController**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChecklistController extends Controller
{
    /**
     * List all checklists
     */
    public function index(Request $request): JsonResponse
    {
        $checklists = Checklist::withCount('items')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $checklists,
        ]);
    }

    /**
     * Get single checklist with items
     */
    public function show(Request $request, int $checklistId): JsonResponse
    {
        $checklist = Checklist::with('items')->findOrFail($checklistId);

        $lang = $request->user()->lang_pref ?? 'vn';

        // Transform items to include content based on language preference
        $items = $checklist->items->map(function ($item) use ($lang) {
            return [
                'id' => $item->id,
                'category' => $item->category,
                'content' => $item->{"content_{$lang}"},
                'content_vn' => $item->content_vn,
                'content_en' => $item->content_en,
                'content_kh' => $item->content_kh,
                'max_score' => $item->max_score,
                'is_critical' => $item->is_critical,
            ];
        });

        return response()->json([
            'data' => [
                'id' => $checklist->id,
                'name' => $checklist->name,
                'min_pass_score' => $checklist->min_pass_score,
                'max_critical_allowed' => $checklist->max_critical_allowed,
                'items' => $items,
            ],
        ]);
    }

    /**
     * Get items for a checklist (alternative endpoint)
     */
    public function items(int $checklistId): JsonResponse
    {
        $checklist = Checklist::findOrFail($checklistId);
        $items = $checklist->items()->orderBy('id')->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Store new checklist (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'min_pass_score' => 'sometimes|integer|min:0|max:100',
            'max_critical_allowed' => 'sometimes|integer|min:0',
        ]);

        $checklist = Checklist::create($request->all());

        return response()->json([
            'data' => $checklist,
            'message' => 'Checklist created successfully',
        ], 201);
    }
}
```

- [ ] **Step 2: Test checklist endpoints**

Run:
- GET /api/checklists
- GET /api/checklists/1 (với auth token)

---

## Chunk 3: Batch & Plan Controllers

### Task 5: BatchController - Inspection Batches

**Files:**
- Create: `backend/app/Http/Controllers/Api/BatchController.php`

- [ ] **Step 1: Tạo BatchController**

```php
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
            'planDetails.cabinet',
        ])->findOrFail($batchId);

        // Check access for inspectors
        if ($request->user()->role === 'inspector' && $batch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Calculate progress
        $totalPlans = $batch->planDetails->count();
        $completedPlans = $batch->planDetails->where('status', 'done')->count();
        $progress = $totalPlans > 0 ? round(($completedPlans / $totalPlans) * 100) : 0;

        return response()->json([
            'data' => [
                'id' => $batch->id,
                'name' => $batch->name,
                'user' => $batch->user,
                'checklist' => $batch->checklist,
                'start_date' => $batch->start_date,
                'end_date' => $batch->end_date,
                'status' => $batch->status,
                'progress' => [
                    'total' => $totalPlans,
                    'completed' => $completedPlans,
                    'percentage' => $progress,
                ],
                'plan_details' => $batch->planDetails,
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
```

- [ ] **Step 2: Test batch endpoints với admin user**

---

### Task 6: PlanDetailController - Plan Details

**Files:**
- Create: `backend/app/Http/Controllers/Api/PlanDetailController.php`

- [ ] **Step 1: Tạo PlanDetailController**

```php
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
```

---

## Chunk 4: Inspection & Sync Controllers

### Task 7: InspectionController - Inspections & Scoring

**Files:**
- Create: `backend/app/Services/ScoringService.php`
- Create: `backend/app/Http/Controllers/Api/InspectionController.php`

- [ ] **Step 1: Tạo ScoringService**

```php
<?php

namespace App\Services;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Inspection;
use App\Models\InspectionDetail;

class ScoringService
{
    /**
     * Calculate inspection score and determine PASS/FAIL
     */
    public function calculateScore(Inspection $inspection): array
    {
        $checklist = $inspection->checklist;
        $details = $inspection->details;

        // Calculate total score
        $totalScore = 0;
        $criticalErrors = 0;

        foreach ($details as $detail) {
            if ($detail->is_failed) {
                // Check if this is a critical item
                $item = ChecklistItem::find($detail->item_id);
                if ($item && $item->is_critical) {
                    $criticalErrors++;
                }
            } else {
                $totalScore += $detail->score_awarded;
            }
        }

        // Determine PASS/FAIL
        $passesScoreThreshold = $totalScore >= $checklist->min_pass_score;
        $passesCriticalThreshold = $criticalErrors < $checklist->max_critical_allowed;

        $finalResult = ($passesScoreThreshold && $passesCriticalThreshold) ? 'PASS' : 'FAIL';

        return [
            'total_score' => $totalScore,
            'critical_errors_count' => $criticalErrors,
            'final_result' => $finalResult,
        ];
    }

    /**
     * Verify and update inspection with calculated scores
     */
    public function verifyAndScore(Inspection $inspection): Inspection
    {
        $scores = $this->calculateScore($inspection);

        $inspection->update([
            'total_score' => $scores['total_score'],
            'critical_errors_count' => $scores['critical_errors_count'],
            'final_result' => $scores['final_result'],
        ]);

        return $inspection->fresh();
    }
}
```

- [ ] **Step 2: Tạo InspectionController**

```php
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
```

- [ ] **Step 3: Test inspection creation**

---

### Task 8: SyncController - Offline Data Synchronization

**Files:**
- Create: `backend/app/Http/Controllers/Api/SyncController.php`

- [ ] **Step 1: Tạo SyncController**

```php
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
```

- [ ] **Step 2: Publish Sanctum config and setup**

Run: `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
Expected: Tạo file config/sanctum.php

- [ ] **Step 3: Test sync endpoint**

---

## Chunk 5: Final Integration & Testing

### Task 9: Verify Complete API

**Files:**
- Test all endpoints

- [ ] **Step 1: Chạy route list để verify all routes**

Run: `php artisan route:list --path=api`
Expected: Hiển thị tất cả routes đã tạo

- [ ] **Step 2: Test với Postman/curl**

1. POST /api/login - nhận token
2. GET /api/me - verify token
3. GET /api/cabinets - lấy danh sách tủ
4. GET /api/checklists/1 - lấy checklist với items
5. GET /api/batches - lấy danh sách batches
6. POST /api/inspections - tạo inspection mới

- [ ] **Step 3: Test scoring logic**

Tạo inspection với các details khác nhau và verify:
- Score >= 80 AND critical errors < 2 = PASS
- Score < 80 OR critical errors >= 2 = FAIL

---

## Summary

Plan hoàn thành tạo các API endpoints:

| Controller | Endpoints | Mô tả |
|------------|-----------|--------|
| AuthController | POST /login, GET /me, POST /logout | Authentication |
| CabinetController | GET, POST, PUT, DELETE /cabinets | Master data tủ cáp |
| ChecklistController | GET /checklists, GET /checklists/{id}/items | Checklists & items |
| BatchController | GET, POST, PUT /batches | Quản lý đợt kiểm tra |
| PlanDetailController | GET /batches/{id}/plans, PATCH /plans/{id}/complete | Chi tiết kế hoạch |
| InspectionController | GET /plans/{id}/inspection, POST /inspections | Phiên kiểm tra |
| SyncController | POST /sync | Đồng bộ offline |

---

**Plan complete and saved to `docs/superpowers/plans/2026-03-17-api-controllers.md`. Ready to execute?**
