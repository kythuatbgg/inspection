<?php

namespace Tests\Feature;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistControllerTest extends TestCase
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
     * Test unauthenticated user cannot access checklists.
     */
    public function test_unauthenticated_user_cannot_access_checklists(): void
    {
        $response = $this->getJson('/api/checklists');

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can list checklists.
     */
    public function test_authenticated_user_can_list_checklists(): void
    {
        // Create test checklist
        Checklist::create([
            'name' => 'Test Checklist 1',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/checklists');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test can get single checklist with items.
     */
    public function test_can_get_single_checklist_with_items(): void
    {
        $checklist = Checklist::create([
            'name' => 'Test Checklist',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'category' => 'Test Category',
            'content_vn' => 'Test content VN',
            'content_en' => 'Test content EN',
            'content_kh' => 'Test content KH',
            'max_score' => 5,
            'is_critical' => false,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/checklists/{$checklist->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Test Checklist')
            ->assertJsonCount(1, 'data.items');
    }

    /**
     * Test can get checklist items.
     */
    public function test_can_get_checklist_items(): void
    {
        $checklist = Checklist::create([
            'name' => 'Test Checklist',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'category' => 'Test Category',
            'content_vn' => 'Test content',
            'content_en' => 'Test content',
            'content_kh' => 'Test content',
            'max_score' => 5,
            'is_critical' => false,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/checklists/{$checklist->id}/items");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test admin can create checklist.
     */
    public function test_admin_can_create_checklist(): void
    {
        $checklistData = [
            'name' => 'New Checklist',
            'min_pass_score' => 75,
            'max_critical_allowed' => 2,
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/checklists', $checklistData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Checklist');

        $this->assertDatabaseHas('checklists', [
            'name' => 'New Checklist',
            'min_pass_score' => 75,
        ]);
    }

    /**
     * Test checklist returns items in user's language preference.
     */
    public function test_checklist_returns_items_in_user_language(): void
    {
        $checklist = Checklist::create([
            'name' => 'Test Checklist',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        $item = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'category' => 'Test Category',
            'content_vn' => 'Nội dung tiếng Việt',
            'content_en' => 'English content',
            'content_kh' => 'Khmer content',
            'max_score' => 5,
            'is_critical' => false,
        ]);

        // Test with English user
        $response = $this->actingAs($this->inspectorUser, 'sanctum')
            ->getJson("/api/checklists/{$checklist->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.items.0.content', 'English content');
    }

    /**
     * Test create checklist validation.
     */
    public function test_create_checklist_requires_name(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/checklists', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test inspector can also list checklists.
     */
    public function test_inspector_can_list_checklists(): void
    {
        Checklist::create([
            'name' => 'Test Checklist',
            'min_pass_score' => 80,
            'max_critical_allowed' => 1,
        ]);

        $response = $this->actingAs($this->inspectorUser, 'sanctum')
            ->getJson('/api/checklists');

        $response->assertStatus(200);
    }
}
