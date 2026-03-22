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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $inspector;
    protected InspectionBatch $batch1;
    protected InspectionBatch $batch2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Search',
            'username' => 'search_admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'lang_pref' => 'vn',
        ]);

        $this->inspector = User::create([
            'name' => 'Inspector Search',
            'username' => 'search_inspector',
            'password' => bcrypt('password'),
            'role' => 'inspector',
            'lang_pref' => 'vn',
        ]);

        $checklist = Checklist::create([
            'name' => 'Search Checklist',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        $item = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'category' => 'Test',
            'content_vn' => 'Test item',
            'content_en' => 'Test item',
            'content_kh' => 'Test',
            'max_score' => 10,
            'is_critical' => false,
        ]);

        Cabinet::create(['cabinet_code' => 'SRCH-001', 'bts_site' => 'BTS-A', 'lat' => 10.99, 'lng' => 104.78]);
        Cabinet::create(['cabinet_code' => 'SRCH-002', 'bts_site' => 'BTS-A', 'lat' => 10.98, 'lng' => 104.77]);
        Cabinet::create(['cabinet_code' => 'SRCH-003', 'bts_site' => 'BTS-B', 'lat' => 10.97, 'lng' => 104.76]);

        $this->batch1 = InspectionBatch::create([
            'name' => 'Search Batch 1',
            'user_id' => $this->inspector->id,
            'created_by' => $this->admin->id,
            'checklist_id' => $checklist->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $this->batch2 = InspectionBatch::create([
            'name' => 'Search Batch 2',
            'user_id' => $this->inspector->id,
            'created_by' => $this->admin->id,
            'checklist_id' => $checklist->id,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        // Batch 1: 2 inspections
        $plan1 = PlanDetail::create(['batch_id' => $this->batch1->id, 'cabinet_code' => 'SRCH-001', 'status' => 'done']);
        $plan2 = PlanDetail::create(['batch_id' => $this->batch1->id, 'cabinet_code' => 'SRCH-002', 'status' => 'done']);

        $ins1 = Inspection::create([
            'user_id' => $this->inspector->id,
            'checklist_id' => $checklist->id,
            'plan_detail_id' => $plan1->id,
            'cabinet_code' => 'SRCH-001',
            'lat' => 10.99, 'lng' => 104.78,
            'total_score' => 90,
            'critical_errors_count' => 0,
            'final_result' => 'PASS',
        ]);

        $ins2 = Inspection::create([
            'user_id' => $this->inspector->id,
            'checklist_id' => $checklist->id,
            'plan_detail_id' => $plan2->id,
            'cabinet_code' => 'SRCH-002',
            'lat' => 10.98, 'lng' => 104.77,
            'total_score' => 60,
            'critical_errors_count' => 2,
            'final_result' => 'FAIL',
        ]);

        // Batch 2: 1 inspection
        $plan3 = PlanDetail::create(['batch_id' => $this->batch2->id, 'cabinet_code' => 'SRCH-003', 'status' => 'done']);
        Inspection::create([
            'user_id' => $this->inspector->id,
            'checklist_id' => $checklist->id,
            'plan_detail_id' => $plan3->id,
            'cabinet_code' => 'SRCH-003',
            'lat' => 10.97, 'lng' => 104.76,
            'total_score' => 85,
            'critical_errors_count' => 0,
            'final_result' => 'PASS',
        ]);

        InspectionDetail::create(['inspection_id' => $ins1->id, 'item_id' => $item->id, 'is_failed' => false, 'score_awarded' => 10]);
        InspectionDetail::create(['inspection_id' => $ins2->id, 'item_id' => $item->id, 'is_failed' => true, 'score_awarded' => 0]);
    }

    public function test_search_returns_all_inspections_without_filters(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/reports/search');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'cabinet_code', 'final_result', 'total_score', 'batch_name', 'inspector_name', 'bts_site']]]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_search_filters_by_batch_id(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/reports/search?batch_id={$this->batch1->id}");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_search_filters_by_cabinet_code(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/reports/search?cabinet_code=SRCH-001');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('SRCH-001', $response->json('data.0.cabinet_code'));
    }

    public function test_search_filters_by_batch_and_cabinet(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/reports/search?batch_id={$this->batch1->id}&cabinet_code=SRCH-002");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('FAIL', $response->json('data.0.final_result'));
    }

    public function test_search_partial_cabinet_code(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/reports/search?cabinet_code=SRCH');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_search_returns_empty_for_nonexistent(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/reports/search?cabinet_code=NOTEXIST');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_inspector_can_only_see_own_inspections(): void
    {
        $otherInspector = User::create([
            'name' => 'Other',
            'username' => 'other_srch',
            'password' => bcrypt('password'),
            'role' => 'inspector',
            'lang_pref' => 'vn',
        ]);

        $response = $this->actingAs($otherInspector, 'sanctum')
            ->getJson('/api/reports/search');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }
}
