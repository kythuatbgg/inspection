<?php

namespace Tests\Feature;

use App\Models\Cabinet;
use App\Models\Checklist;
use App\Models\InspectionBatch;
use App\Models\PlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $inspectorUser;

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
    }

    /**
     * Test unauthenticated user cannot access batches.
     */
    public function test_unauthenticated_user_cannot_access_batches(): void
    {
        $response = $this->getJson('/api/batches');
        $response->assertStatus(401);
    }

    /**
     * Test admin can list all batches.
     */
    public function test_admin_can_list_all_batches(): void
    {
        $checklist = Checklist::create([
            'name' => 'Test Checklist',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        InspectionBatch::create([
            'name' => 'Batch 1',
            'user_id' => $this->adminUser->id,
            'checklist_id' => $checklist->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/batches');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test inspector can only see their own batches.
     */
    public function test_inspector_only_sees_own_batches(): void
    {
        $checklist = Checklist::create([
            'name' => 'Test Checklist',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        // Create batch for admin
        InspectionBatch::create([
            'name' => 'Admin Batch',
            'user_id' => $this->adminUser->id,
            'checklist_id' => $checklist->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'status' => 'pending',
        ]);

        // Create batch for inspector
        InspectionBatch::create([
            'name' => 'Inspector Batch',
            'user_id' => $this->inspectorUser->id,
            'checklist_id' => $checklist->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->inspectorUser, 'sanctum')
            ->getJson('/api/batches');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Inspector Batch');
    }

    /**
     * Test admin can create batch with plan details.
     */
    public function test_admin_can_create_batch(): void
    {
        $checklist = Checklist::create([
            'name' => 'Test Checklist',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        $cabinet1 = Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'name' => 'Cabinet 1',
            'type' => 'FBB Box 24FO',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ]);

        $cabinet2 = Cabinet::create([
            'cabinet_code' => 'CAB-002',
            'bts_site' => 'BTS-01',
            'name' => 'Cabinet 2',
            'type' => 'FBB Box 48FO',
            'lat' => 10.9950,
            'lng' => 104.7800,
        ]);

        $batchData = [
            'name' => 'New Batch',
            'user_id' => $this->inspectorUser->id,
            'checklist_id' => $checklist->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'cabinet_codes' => ['CAB-001', 'CAB-002'],
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/batches', $batchData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Batch');

        $this->assertDatabaseHas('inspection_batches', ['name' => 'New Batch']);
        $this->assertDatabaseCount('plan_details', 2);
    }

    /**
     * Test can get single batch with plan details.
     */
    public function test_can_get_single_batch(): void
    {
        $checklist = Checklist::create([
            'name' => 'Test Checklist',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        $batch = InspectionBatch::create([
            'name' => 'Test Batch',
            'user_id' => $this->adminUser->id,
            'checklist_id' => $checklist->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'status' => 'pending',
        ]);

        $cabinet = Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'name' => 'Cabinet 1',
            'type' => 'FBB Box 24FO',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ]);

        PlanDetail::create([
            'batch_id' => $batch->id,
            'cabinet_code' => 'CAB-001',
            'status' => 'planned',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/batches/{$batch->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Test Batch')
            ->assertJsonCount(1, 'data.plan_details');
    }

    /**
     * Test can update batch status.
     */
    public function test_can_update_batch_status(): void
    {
        $checklist = Checklist::create([
            'name' => 'Test Checklist',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        $batch = InspectionBatch::create([
            'name' => 'Test Batch',
            'user_id' => $this->adminUser->id,
            'checklist_id' => $checklist->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/batches/{$batch->id}", [
                'status' => 'active',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    /**
     * Test create batch validation.
     */
    public function test_create_batch_requires_required_fields(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/batches', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'user_id',
                'checklist_id',
                'start_date',
                'end_date',
                'cabinet_codes',
            ]);
    }

    /**
     * Test inspector cannot access other inspector's batch.
     */
    public function test_inspector_cannot_access_other_batch(): void
    {
        $checklist = Checklist::create([
            'name' => 'Test Checklist',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        $batch = InspectionBatch::create([
            'name' => 'Admin Batch',
            'user_id' => $this->adminUser->id,
            'checklist_id' => $checklist->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->inspectorUser, 'sanctum')
            ->getJson("/api/batches/{$batch->id}");

        $response->assertStatus(403);
    }
}
