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

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $inspector;
    protected User $otherInspector;
    protected InspectionBatch $batch;
    protected Inspection $inspection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'lang_pref' => 'vn',
        ]);

        $this->inspector = User::create([
            'name' => 'Inspector A',
            'username' => 'inspector_a',
            'password' => bcrypt('password'),
            'role' => 'inspector',
            'lang_pref' => 'vn',
        ]);

        $this->otherInspector = User::create([
            'name' => 'Inspector B',
            'username' => 'inspector_b',
            'password' => bcrypt('password'),
            'role' => 'inspector',
            'lang_pref' => 'en',
        ]);

        $checklist = Checklist::create([
            'name' => 'Bảo dưỡng tủ GPON',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        $item1 = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'category' => 'Vệ sinh',
            'content_vn' => 'Kiểm tra vệ sinh bên ngoài tủ',
            'content_en' => 'Check exterior cleanliness',
            'content_kh' => 'ពិនិត្យភាពស្អាតខាងក្រៅ',
            'max_score' => 10,
            'is_critical' => false,
        ]);

        $item2 = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'category' => 'Cáp quang',
            'content_vn' => 'Kiểm tra đầu nối cáp quang',
            'content_en' => 'Check fiber optic connectors',
            'content_kh' => 'ពិនិត្យឧបករណ៍ភ្ជាប់',
            'max_score' => 20,
            'is_critical' => true,
        ]);

        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-ALPHA',
            'lat' => 10.990800,
            'lng' => 104.784800,
        ]);

        Cabinet::create([
            'cabinet_code' => 'CAB-002',
            'bts_site' => 'BTS-ALPHA',
            'lat' => 10.995000,
            'lng' => 104.780000,
        ]);

        $this->batch = InspectionBatch::create([
            'name' => 'Đợt KT tháng 3/2026',
            'user_id' => $this->inspector->id,
            'created_by' => $this->admin->id,
            'checklist_id' => $checklist->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $plan1 = PlanDetail::create([
            'batch_id' => $this->batch->id,
            'cabinet_code' => 'CAB-001',
            'status' => 'done',
            'review_status' => 'approved',
        ]);

        PlanDetail::create([
            'batch_id' => $this->batch->id,
            'cabinet_code' => 'CAB-002',
            'status' => 'done',
            'review_status' => 'approved',
        ]);

        $this->inspection = Inspection::create([
            'user_id' => $this->inspector->id,
            'checklist_id' => $checklist->id,
            'plan_detail_id' => $plan1->id,
            'cabinet_code' => 'CAB-001',
            'lat' => 10.990800,
            'lng' => 104.784800,
            'total_score' => 90,
            'critical_errors_count' => 0,
            'final_result' => 'PASS',
        ]);

        InspectionDetail::create([
            'inspection_id' => $this->inspection->id,
            'item_id' => $item1->id,
            'is_failed' => false,
            'score_awarded' => 10,
        ]);

        InspectionDetail::create([
            'inspection_id' => $this->inspection->id,
            'item_id' => $item2->id,
            'is_failed' => false,
            'score_awarded' => 20,
        ]);
    }

    public function test_can_generate_inspection_pdf(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/reports/inspection/{$this->inspection->id}");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_can_generate_batch_summary_pdf(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/reports/batch/{$this->batch->id}/summary");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_can_export_batch_excel(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/reports/batch/{$this->batch->id}/export");

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );
    }

    public function test_can_generate_critical_errors_report(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/reports/critical-errors?batch_id={$this->batch->id}");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_can_generate_acceptance_pdf(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/reports/acceptance/{$this->batch->id}");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_inspector_can_access_own_report(): void
    {
        $response = $this->actingAs($this->inspector, 'sanctum')
            ->getJson("/api/reports/inspection/{$this->inspection->id}");

        $response->assertStatus(200);
    }

    public function test_inspector_cannot_access_other_report(): void
    {
        $response = $this->actingAs($this->otherInspector, 'sanctum')
            ->getJson("/api/reports/inspection/{$this->inspection->id}");

        $response->assertStatus(403);
    }
}
