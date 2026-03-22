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

class InspectorStatsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $inspector;
    protected User $otherInspector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'username' => 'istats_admin',
            'password' => bcrypt('password'), 'role' => 'admin', 'lang_pref' => 'en',
        ]);

        $this->inspector = User::create([
            'name' => 'Inspector A', 'username' => 'istats_insp_a',
            'password' => bcrypt('password'), 'role' => 'inspector', 'lang_pref' => 'en',
        ]);

        $this->otherInspector = User::create([
            'name' => 'Inspector B', 'username' => 'istats_insp_b',
            'password' => bcrypt('password'), 'role' => 'inspector', 'lang_pref' => 'en',
        ]);

        $checklist = Checklist::create([
            'name' => 'CL-IStats', 'min_pass_score' => 70, 'max_critical_allowed' => 1,
        ]);

        $criticalItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'category' => 'Cáp quang',
            'content_vn' => 'Kiểm tra đầu nối',
            'content_en' => 'Check connectors',
            'content_kh' => 'ពិនិត្យ',
            'max_score' => 20,
            'is_critical' => true,
        ]);

        Cabinet::create(['cabinet_code' => 'IS-001', 'bts_site' => 'BTS-A', 'lat' => 10.9, 'lng' => 104.7]);
        Cabinet::create(['cabinet_code' => 'IS-002', 'bts_site' => 'BTS-A', 'lat' => 10.9, 'lng' => 104.7]);

        $batch = InspectionBatch::create([
            'name' => 'IStats Batch', 'user_id' => $this->inspector->id,
            'created_by' => $this->admin->id, 'checklist_id' => $checklist->id,
            'start_date' => '2026-01-01', 'end_date' => '2026-06-30',
            'status' => 'active', 'approval_status' => 'approved',
        ]);

        $plan1 = PlanDetail::create(['batch_id' => $batch->id, 'cabinet_code' => 'IS-001', 'status' => 'done']);
        $plan2 = PlanDetail::create(['batch_id' => $batch->id, 'cabinet_code' => 'IS-002', 'status' => 'done']);

        // Inspector A: 1 PASS, 1 FAIL
        $insp1 = Inspection::create([
            'user_id' => $this->inspector->id, 'checklist_id' => $checklist->id,
            'plan_detail_id' => $plan1->id, 'cabinet_code' => 'IS-001',
            'total_score' => 90, 'critical_errors_count' => 0, 'final_result' => 'PASS',
        ]);

        $insp2 = Inspection::create([
            'user_id' => $this->inspector->id, 'checklist_id' => $checklist->id,
            'plan_detail_id' => $plan2->id, 'cabinet_code' => 'IS-002',
            'total_score' => 50, 'critical_errors_count' => 1, 'final_result' => 'FAIL',
        ]);

        InspectionDetail::create([
            'inspection_id' => $insp2->id, 'item_id' => $criticalItem->id,
            'is_failed' => true, 'score_awarded' => 0, 'note' => 'Broken',
        ]);

        // Inspector B: 1 PASS (should NOT appear in Inspector A's stats)
        Inspection::create([
            'user_id' => $this->otherInspector->id, 'checklist_id' => $checklist->id,
            'plan_detail_id' => $plan1->id, 'cabinet_code' => 'IS-001',
            'total_score' => 95, 'critical_errors_count' => 0, 'final_result' => 'PASS',
        ]);
    }

    public function test_inspector_can_get_own_overview(): void
    {
        $response = $this->actingAs($this->inspector, 'sanctum')
            ->getJson('/api/inspector/stats/overview');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.passed', 1)
            ->assertJsonPath('data.failed', 1)
            ->assertJsonPath('data.critical_errors', 1);
    }

    public function test_overview_excludes_other_inspectors_data(): void
    {
        $response = $this->actingAs($this->inspector, 'sanctum')
            ->getJson('/api/inspector/stats/overview');

        // Inspector A has 2 inspections, NOT 3 (Inspector B's is excluded)
        $response->assertJsonPath('data.total', 2);
    }

    public function test_inspector_can_get_timeline(): void
    {
        $response = $this->actingAs($this->inspector, 'sanctum')
            ->getJson('/api/inspector/stats/timeline');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['month', 'total', 'passed', 'failed']]]);
    }

    public function test_inspector_can_get_top_errors(): void
    {
        $response = $this->actingAs($this->inspector, 'sanctum')
            ->getJson('/api/inspector/stats/top-errors');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['error_content', 'category', 'count']]]);

        // Inspector A has 1 critical error
        $this->assertCount(1, $response->json('data'));
    }

    public function test_inspector_can_get_inspections_list(): void
    {
        $response = $this->actingAs($this->inspector, 'sanctum')
            ->getJson('/api/inspector/stats/inspections');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'cabinet_code', 'final_result', 'total_score', 'created_at']]])
            ->assertJsonCount(2, 'data');
    }

    public function test_inspections_list_can_search_by_cabinet(): void
    {
        $response = $this->actingAs($this->inspector, 'sanctum')
            ->getJson('/api/inspector/stats/inspections?search=IS-001');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cabinet_code', 'IS-001');
    }

    public function test_inspections_list_can_filter_by_result(): void
    {
        $response = $this->actingAs($this->inspector, 'sanctum')
            ->getJson('/api/inspector/stats/inspections?result=FAIL');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_unauthenticated_cannot_access(): void
    {
        $this->getJson('/api/inspector/stats/overview')->assertStatus(401);
    }
}
