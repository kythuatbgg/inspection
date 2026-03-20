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

    public function test_unauthenticated_user_cannot_access_cabinets(): void
    {
        $response = $this->getJson('/api/cabinets');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_cabinets(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'lat' => 10.9908,
            'lng' => 104.7848,
            'note' => 'Near gate',
        ]);

        Cabinet::create([
            'cabinet_code' => 'CAB-002',
            'bts_site' => 'BTS-02',
            'lat' => 10.9950,
            'lng' => 104.7800,
            'note' => 'Near office',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')->getJson('/api/cabinets');

        $response
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_cabinets_by_bts_site(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-TAK-01',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ]);

        Cabinet::create([
            'cabinet_code' => 'CAB-002',
            'bts_site' => 'BTS-TAK-02',
            'lat' => 10.9950,
            'lng' => 104.7800,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/cabinets?bts_site=BTS-TAK-01');

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.bts_site', 'BTS-TAK-01');
    }

    public function test_can_search_cabinets_by_code_or_site(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-SEARCH-01',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ]);

        Cabinet::create([
            'cabinet_code' => 'CAB-ABC-002',
            'bts_site' => 'BTS-OTHER-02',
            'lat' => 10.9950,
            'lng' => 104.7800,
        ]);

        $codeResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/cabinets?search=ABC');

        $codeResponse
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cabinet_code', 'CAB-ABC-002');

        $siteResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/cabinets?search=SEARCH');

        $siteResponse
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.bts_site', 'BTS-SEARCH-01');
    }

    public function test_can_get_single_cabinet(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'lat' => 10.9908,
            'lng' => 104.7848,
            'note' => 'Single cabinet',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/cabinets/CAB-001');

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.cabinet_code', 'CAB-001')
            ->assertJsonPath('data.bts_site', 'BTS-01');
    }

    public function test_admin_can_create_cabinet(): void
    {
        $cabinetData = [
            'cabinet_code' => 'CAB-NEW-001',
            'bts_site' => 'BTS-NEW-01',
            'lat' => 10.9908,
            'lng' => 104.7848,
            'note' => 'Created by test',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/cabinets', $cabinetData);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.cabinet_code', 'CAB-NEW-001');

        $this->assertDatabaseHas('cabinets', [
            'cabinet_code' => 'CAB-NEW-001',
            'bts_site' => 'BTS-NEW-01',
            'note' => 'Created by test',
        ]);
    }

    public function test_admin_can_update_cabinet(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'lat' => 10.9908,
            'lng' => 104.7848,
            'note' => 'Original note',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson('/api/cabinets/CAB-001', [
                'bts_site' => 'BTS-UPDATED-01',
                'note' => 'Updated note',
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.bts_site', 'BTS-UPDATED-01')
            ->assertJsonPath('data.note', 'Updated note');
    }

    public function test_admin_can_delete_cabinet(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
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

    public function test_create_cabinet_requires_required_fields(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/cabinets', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'cabinet_code',
                'bts_site',
                'lat',
                'lng',
            ]);
    }

    public function test_create_cabinet_validates_unique_code(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'lat' => 10.9908,
            'lng' => 104.7848,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/cabinets', [
                'cabinet_code' => 'CAB-001',
                'bts_site' => 'BTS-02',
                'lat' => 10.9908,
                'lng' => 104.7848,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cabinet_code']);
    }

    public function test_download_template_returns_excel_file(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->get('/api/cabinets/template');

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type', '')
        );
        $this->assertStringContainsString(
            'cabinet_template.xlsx',
            $response->headers->get('content-disposition', '')
        );
    }

    public function test_export_streams_csv_file(): void
    {
        Cabinet::create([
            'cabinet_code' => 'CAB-001',
            'bts_site' => 'BTS-01',
            'lat' => 10.9908,
            'lng' => 104.7848,
            'note' => 'Export me',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->get('/api/cabinets/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type', ''));
        $this->assertStringContainsString('cabinets.csv', $response->headers->get('content-disposition', ''));
    }

    public function test_import_returns_export_token_and_export_result_uses_token(): void
    {
        $fileContent = implode("\n", [
            'cabinet_code,bts_site,lat,lng,note',
            'CAB-100,BTS-100,10.9908,104.7848,Imported row',
            'CAB-101,,10.9910,104.7850,Missing site',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'cabinet-import');
        file_put_contents($path, $fileContent);

        $upload = new \Illuminate\Http\UploadedFile(
            $path,
            'cabinet-import.csv',
            'text/csv',
            null,
            true
        );

        $importResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->post('/api/cabinets/import', [
                'file' => $upload,
            ]);

        $importResponse
            ->assertStatus(200)
            ->assertJsonPath('imported', 1)
            ->assertJsonPath('failed', 1)
            ->assertJsonStructure([
                'export_token',
                'results',
            ]);

        $token = $importResponse->json('export_token');

        $exportResponse = $this->actingAs($this->adminUser, 'sanctum')
            ->get('/api/cabinets/export-result?token=' . $token);

        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('content-type', ''));
        $this->assertStringContainsString('import_results.csv', $exportResponse->headers->get('content-disposition', ''));

        @unlink($path);
    }
}
