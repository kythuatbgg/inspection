<?php

namespace Tests\Feature;

use App\Models\Cabinet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CabinetControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $inspectorUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->adminUser = User::create([
            'name' => 'Test Admin',
            'username' => 'test_admin',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'lang_pref' => 'vn',
        ]);

        // Create inspector user
        $this->inspectorUser = User::create([
            'name' => 'Test Inspector',
            'username' => 'test_inspector',
            'password' => bcrypt('password123'),
            'role' => 'inspector',
            'lang_pref' => 'en',
        ]);
    }

    /**
     * Test unauthenticated user cannot access cabinets.
     */
    public function test_unauthenticated_user_cannot_access_cabinets(): void
    {
        $response = $this->getJson('/api/cabinets');

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can list cabinets.
     */
    public function test_authenticated_user_can_list_cabinets(): void
    {
        // Create test cabinets
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'name' => 'Test Cabinet 1',
            'type' => 'FBB Box 24FO',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ]);

        Cabinet::create([
            'cabinet_code' => 'CAB-002',
            'bts_site' => 'BTS-02',
            'name' => 'Test Cabinet 2',
            'type' => 'FBB Box 48FO',
            'lat' => 10.9950,
            'lng' => 104.7800,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/cabinets');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test filter cabinets by BTS site.
     */
    public function test_can_filter_cabinets_by_bts_site(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-TAK-01',
            'name' => 'Test Cabinet 1',
            'type' => 'FBB Box 24FO',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ]);

        Cabinet::create([
            'cabinet_code' => 'CAB-002',
            'bts_site' => 'BTS-TAK-02',
            'name' => 'Test Cabinet 2',
            'type' => 'FBB Box 48FO',
            'lat' => 10.9950,
            'lng' => 104.7800,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/cabinets?bts_site=BTS-TAK-01');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.bts_site', 'BTS-TAK-01');
    }

    /**
     * Test can search cabinets by name or code.
     */
    public function test_can_search_cabinets(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'name' => 'Tủ hộp cáp ngã tư',
            'type' => 'FBB Box 24FO',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ]);

        Cabinet::create([
            'cabinet_code' => 'CAB-002',
            'bts_site' => 'BTS-02',
            'name' => 'Another Cabinet',
            'type' => 'FBB Box 48FO',
            'lat' => 10.9950,
            'lng' => 104.7800,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/cabinets?search=Tủ');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test can get single cabinet.
     */
    public function test_can_get_single_cabinet(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'name' => 'Test Cabinet',
            'type' => 'FBB Box 24FO',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/cabinets/CAB-001');

        $response->assertStatus(200)
            ->assertJsonPath('data.cabinet_code', 'CAB-001')
            ->assertJsonPath('data.name', 'Test Cabinet');
    }

    /**
     * Test admin can create cabinet.
     */
    public function test_admin_can_create_cabinet(): void
    {
        $cabinetData = [
            'cabinet_code' => 'CAB-NEW-001',
            'bts_site' => 'BTS-NEW-01',
            'name' => 'New Cabinet',
            'type' => 'FBB Box 24FO',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/cabinets', $cabinetData);

        $response->assertStatus(201)
            ->assertJsonPath('data.cabinet_code', 'CAB-NEW-001');

        $this->assertDatabaseHas('cabinets', [
            'cabinet_code' => 'CAB-NEW-001',
            'name' => 'New Cabinet',
        ]);
    }

    /**
     * Test admin can update cabinet.
     */
    public function test_admin_can_update_cabinet(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'name' => 'Original Name',
            'type' => 'FBB Box 24FO',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson('/api/cabinets/CAB-001', [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    /**
     * Test admin can delete cabinet.
     */
    public function test_admin_can_delete_cabinet(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'name' => 'Test Cabinet',
            'type' => 'FBB Box 24FO',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson('/api/cabinets/CAB-001');

        $response->assertStatus(200);

        $this->assertDatabaseMissing('cabinets', [
            'cabinet_code' => 'CAB-001',
        ]);
    }

    /**
     * Test create cabinet validation - required fields.
     */
    public function test_create_cabinet_requires_required_fields(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/cabinets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'cabinet_code',
                'bts_site',
                'name',
                'type',
                'lat',
                'lng',
            ]);
    }

    /**
     * Test create cabinet validates unique cabinet_code.
     */
    public function test_create_cabinet_validates_unique_code(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'name' => 'Existing Cabinet',
            'type' => 'FBB Box 24FO',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/cabinets', [
                'cabinet_code' => 'CAB-001',
                'bts_site' => 'BTS-02',
                'name' => 'Duplicate Cabinet',
                'type' => 'FBB Box 24FO',
                'lat' => 10.9908,
                'lng' => 104.7848,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cabinet_code']);
    }
}
