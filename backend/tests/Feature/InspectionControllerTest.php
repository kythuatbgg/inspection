<?php

namespace Tests\Feature;

use App\Models\Cabinet;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Inspection;
use App\Models\InspectionBatch;
use App\Models\InspectionDetail;
use App\Models\PlanDetail;
use App\Models\User;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $inspectorUser;
    protected Checklist $checklist;
    protected InspectionBatch $batch;
    protected PlanDetail $planDetail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Test Admin',
            'username' => 'test_admin',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'lang_pref' => 'vn',
        ]);

        $this->inspectorUser = User::create([
            'name' => 'Test Inspector',
            'username' => 'test_inspector',
            'password' => bcrypt('password123'),
            'role' => 'inspector',
            'lang_pref' => 'en',
        ]);

        // Create checklist with items
        $this->checklist = Checklist::create([
            'name' => 'Test Checklist',
            'min_pass_score' => 30,  // 3 items × 10 max_score each = 30 total
            'max_critical_allowed' => 1,
        ]);

        // Create items - some critical, some not
        $item1 = ChecklistItem::create([
            'checklist_id' => $this->checklist->id,
            'category' => 'Test',
            'content_vn' => 'Item 1 - Critical',
            'content_en' => 'Item 1 - Critical',
            'content_kh' => 'Item 1 - Critical',
            'max_score' => 10,
            'is_critical' => true,
        ]);

        $item2 = ChecklistItem::create([
            'checklist_id' => $this->checklist->id,
            'category' => 'Test',
            'content_vn' => 'Item 2 - Normal',
            'content_en' => 'Item 2 - Normal',
            'content_kh' => 'Item 2 - Normal',
            'max_score' => 10,
            'is_critical' => false,
        ]);

        $item3 = ChecklistItem::create([
            'checklist_id' => $this->checklist->id,
            'category' => 'Test',
            'content_vn' => 'Item 3 - Normal',
            'content_en' => 'Item 3 - Normal',
            'content_kh' => 'Item 3 - Normal',
            'max_score' => 10,
            'is_critical' => false,
        ]);

        // Create cabinet
        $cabinet = Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'name' => 'Test Cabinet',
            'type' => 'FBB Box 24FO',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ]);

        // Create batch
        $this->batch = InspectionBatch::create([
            'name' => 'Test Batch',
            'user_id' => $this->inspectorUser->id,
            'checklist_id' => $this->checklist->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'status' => 'active',
        ]);

        // Create plan detail
        $this->planDetail = PlanDetail::create([
            'batch_id' => $this->batch->id,
            'cabinet_code' => 'CAB-001',
            'status' => 'planned',
        ]);
    }

    /**
     * Test scoring PASS when score >= 80 and critical errors < 2.
     */
    public function test_scoring_returns_pass_when_meets_criteria(): void
    {
        // All items pass (not failed)
        $inspection = Inspection::create([
            'user_id' => $this->inspectorUser->id,
            'checklist_id' => $this->checklist->id,
            'plan_detail_id' => $this->planDetail->id,
            'cabinet_code' => 'CAB-001',
            'lat' => 10.9908,
            'lng' => 104.7848,
            'total_score' => 0,
            'critical_errors_count' => 0,
            'final_result' => null,
        ]);

        // Create inspection details - all pass
        $items = $this->checklist->items;
        foreach ($items as $item) {
            InspectionDetail::create([
                'inspection_id' => $inspection->id,
                'item_id' => $item->id,
                'is_failed' => false,
                'score_awarded' => $item->max_score,
            ]);
        }

        // Run scoring
        $scoringService = new ScoringService();
        $result = $scoringService->verifyAndScore($inspection);

        $this->assertEquals('PASS', $result->final_result);
        $this->assertEquals(30, $result->total_score); // 10 + 10 + 10
    }

    /**
     * Test scoring FAIL when score < 80.
     */
    public function test_scoring_returns_fail_when_score_below_threshold(): void
    {
        $inspection = Inspection::create([
            'user_id' => $this->inspectorUser->id,
            'checklist_id' => $this->checklist->id,
            'plan_detail_id' => $this->planDetail->id,
            'cabinet_code' => 'CAB-001',
            'lat' => 10.9908,
            'lng' => 104.7848,
            'total_score' => 0,
            'critical_errors_count' => 0,
            'final_result' => null,
        ]);

        // Create inspection details - one fails (score = 0)
        $items = $this->checklist->items;
        foreach ($items as $item) {
            InspectionDetail::create([
                'inspection_id' => $inspection->id,
                'item_id' => $item->id,
                'is_failed' => true, // All failed
                'score_awarded' => 0,
            ]);
        }

        $scoringService = new ScoringService();
        $result = $scoringService->verifyAndScore($inspection);

        $this->assertEquals('FAIL', $result->final_result);
        $this->assertEquals(0, $result->total_score);
    }

    /**
     * Test scoring FAIL when critical errors >= 2.
     */
    public function test_scoring_returns_fail_when_critical_errors_exceed_limit(): void
    {
        // Create more critical items
        $criticalItem1 = ChecklistItem::create([
            'checklist_id' => $this->checklist->id,
            'category' => 'Test',
            'content_vn' => 'Critical Item 1',
            'content_en' => 'Critical Item 1',
            'content_kh' => 'Critical Item 1',
            'max_score' => 10,
            'is_critical' => true,
        ]);

        $criticalItem2 = ChecklistItem::create([
            'checklist_id' => $this->checklist->id,
            'category' => 'Test',
            'content_vn' => 'Critical Item 2',
            'content_en' => 'Critical Item 2',
            'content_kh' => 'Critical Item 2',
            'max_score' => 10,
            'is_critical' => true,
        ]);

        $inspection = Inspection::create([
            'user_id' => $this->inspectorUser->id,
            'checklist_id' => $this->checklist->id,
            'plan_detail_id' => $this->planDetail->id,
            'cabinet_code' => 'CAB-001',
            'lat' => 10.9908,
            'lng' => 104.7848,
            'total_score' => 0,
            'critical_errors_count' => 0,
            'final_result' => null,
        ]);

        // Two critical items fail
        InspectionDetail::create([
            'inspection_id' => $inspection->id,
            'item_id' => $criticalItem1->id,
            'is_failed' => true,
            'score_awarded' => 0,
        ]);

        InspectionDetail::create([
            'inspection_id' => $inspection->id,
            'item_id' => $criticalItem2->id,
            'is_failed' => true,
            'score_awarded' => 0,
        ]);

        // Other items pass
        foreach ($this->checklist->items as $item) {
            InspectionDetail::create([
                'inspection_id' => $inspection->id,
                'item_id' => $item->id,
                'is_failed' => false,
                'score_awarded' => $item->max_score,
            ]);
        }

        $scoringService = new ScoringService();
        $result = $scoringService->verifyAndScore($inspection);

        $this->assertEquals('FAIL', $result->final_result);
        $this->assertEquals(2, $result->critical_errors_count);
    }

    /**
     * Test can create inspection.
     */
    public function test_can_create_inspection(): void
    {
        $items = $this->checklist->items;

        $inspectionData = [
            'plan_detail_id' => $this->planDetail->id,
            'checklist_id' => $this->checklist->id,
            'cabinet_code' => 'CAB-001',
            'lat' => 10.9908,
            'lng' => 104.7848,
            'details' => [
                [
                    'item_id' => $items[0]->id,
                    'is_failed' => false,
                    'score_awarded' => $items[0]->max_score,
                ],
                [
                    'item_id' => $items[1]->id,
                    'is_failed' => false,
                    'score_awarded' => $items[1]->max_score,
                ],
                [
                    'item_id' => $items[2]->id,
                    'is_failed' => false,
                    'score_awarded' => $items[2]->max_score,
                ],
            ],
        ];

        $response = $this->actingAs($this->inspectorUser, 'sanctum')
            ->postJson('/api/inspections', $inspectionData);

        $response->assertStatus(201)
            ->assertJsonPath('data.final_result', 'PASS');

        // Verify plan marked as done
        $this->assertDatabaseHas('plan_details', [
            'id' => $this->planDetail->id,
            'status' => 'done',
        ]);
    }

    /**
     * Test can get inspection for a plan.
     */
    public function test_can_get_inspection_for_plan(): void
    {
        // Create existing inspection
        $inspection = Inspection::create([
            'user_id' => $this->inspectorUser->id,
            'checklist_id' => $this->checklist->id,
            'plan_detail_id' => $this->planDetail->id,
            'cabinet_code' => 'CAB-001',
            'total_score' => 30,
            'critical_errors_count' => 0,
            'final_result' => 'PASS',
        ]);

        $response = $this->actingAs($this->inspectorUser, 'sanctum')
            ->getJson("/api/plans/{$this->planDetail->id}/inspection");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $inspection->id)
            ->assertJsonPath('data.final_result', 'PASS');
    }

    /**
     * Test inspector cannot create inspection for other inspector's plan.
     */
    public function test_inspector_cannot_create_inspection_for_others_plan(): void
    {
        // Create plan for admin
        $adminBatch = InspectionBatch::create([
            'name' => 'Admin Batch',
            'user_id' => $this->adminUser->id,
            'checklist_id' => $this->checklist->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'status' => 'active',
        ]);

        $adminPlan = PlanDetail::create([
            'batch_id' => $adminBatch->id,
            'cabinet_code' => 'CAB-001',
            'status' => 'planned',
        ]);

        $items = $this->checklist->items;

        $inspectionData = [
            'plan_detail_id' => $adminPlan->id,
            'checklist_id' => $this->checklist->id,
            'cabinet_code' => 'CAB-001',
            'details' => [
                [
                    'item_id' => $items[0]->id,
                    'is_failed' => false,
                    'score_awarded' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->inspectorUser, 'sanctum')
            ->postJson('/api/inspections', $inspectionData);

        $response->assertStatus(403);
    }

    /**
     * Test inspection validation - requires details.
     */
    public function test_inspection_requires_details(): void
    {
        $inspectionData = [
            'plan_detail_id' => $this->planDetail->id,
            'checklist_id' => $this->checklist->id,
            'cabinet_code' => 'CAB-001',
            'details' => [],
        ];

        $response = $this->actingAs($this->inspectorUser, 'sanctum')
            ->postJson('/api/inspections', $inspectionData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['details']);
    }
}
