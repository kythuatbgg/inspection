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

class StatisticsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $inspector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'username' => 'stats_admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'lang_pref' => 'vn',
        ]);

        $this->inspector = User::create([
            'name' => 'Inspector Stats',
            'username' => 'stats_inspector',
            'password' => bcrypt('password'),
            'role' => 'inspector',
            'lang_pref' => 'vn',
        ]);

        $checklist = Checklist::create([
            'name' => 'Checklist Stats',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        $item = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'category' => 'Cáp quang',
            'content_vn' => 'Kiểm tra đầu nối',
            'content_en' => 'Check connectors',
            'content_kh' => 'ពិនិត្យ',
            'max_score' => 20,
            'is_critical' => true,
        ]);

        Cabinet::create(['cabinet_code' => 'STAT-001', 'bts_site' => 'BTS-X', 'lat' => 10.99, 'lng' => 104.78]);
        Cabinet::create(['cabinet_code' => 'STAT-002', 'bts_site' => 'BTS-Y', 'lat' => 10.98, 'lng' => 104.77]);

        $batch = InspectionBatch::create([
            'name' => 'Stats Batch',
            'user_id' => $this->inspector->id,
            'created_by' => $this->admin->id,
            'checklist_id' => $checklist->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $plan = PlanDetail::create([
            'batch_id' => $batch->id,
            'cabinet_code' => 'STAT-001',
            'status' => 'done',
        ]);

        $inspection = Inspection::create([
            'user_id' => $this->inspector->id,
            'checklist_id' => $checklist->id,
            'plan_detail_id' => $plan->id,
            'cabinet_code' => 'STAT-001',
            'lat' => 10.99,
            'lng' => 104.78,
            'total_score' => 85,
            'critical_errors_count' => 0,
            'final_result' => 'PASS',
        ]);

        InspectionDetail::create([
            'inspection_id' => $inspection->id,
            'item_id' => $item->id,
            'is_failed' => false,
            'score_awarded' => 20,
        ]);
    }

    public function test_admin_can_get_overview_stats(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/statistics/overview');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['total_batches', 'total_cabinets_inspected', 'pass_rate', 'total_critical_errors']]);
    }

    public function test_can_filter_by_date_range(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/statistics/overview?from=2026-03-01&to=2026-03-31');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_batches', 1);
    }

    public function test_can_get_stats_by_bts(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/statistics/by-bts');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['bts_site', 'total', 'passed', 'failed']]]);
    }

    public function test_can_get_stats_by_inspector(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/statistics/by-inspector');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['inspector_name', 'total_inspections', 'pass_rate']]]);
    }

    public function test_can_get_stats_by_error_type(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/statistics/by-error-type');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_can_export_statistics_excel(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/statistics/export?format=xlsx');

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );
    }

    public function test_inspector_cannot_access_statistics(): void
    {
        $response = $this->actingAs($this->inspector, 'sanctum')
            ->getJson('/api/statistics/overview');

        $response->assertStatus(403);
    }
}
