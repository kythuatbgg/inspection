<?php

namespace Database\Seeders;

use App\Models\Cabinet;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Path to the JSON seed data file.
     */
    protected string $seedDataPath;

    /**
     * Create a new seeder instance.
     */
    public function __construct()
    {
        // Look for the JSON file in the project root or parent directory
        $possiblePaths = [
            base_path('../mock_data_seed_full.json'),
            base_path('../../mock_data_seed_full.json'),
            base_path('mock_data_seed_full.json'),
            database_path('../mock_data_seed_full.json'),
            database_path('mock_data_seed_full.json'),
        ];

        $this->seedDataPath = '';
        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                $this->seedDataPath = $path;
                break;
            }
        }
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Starting database seeding...');

        if (empty($this->seedDataPath)) {
            $this->command->error('Mock data file not found! Searching in:');
            $this->command->error(base_path('../mock_data_seed_full.json'));
            $this->command->error(base_path('../../mock_data_seed_full.json'));
            $this->command->error(base_path('mock_data_seed_full.json'));
            return;
        }

        $this->command->info('Found seed data file: ' . $this->seedDataPath);

        // Read JSON file
        $json = File::get($this->seedDataPath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Failed to parse JSON: ' . json_last_error_msg());
            return;
        }

        // Seed users
        $this->seedUsers($data['users'] ?? []);

        // Seed cabinets
        $this->seedCabinets($data['cabinets'] ?? []);

        // Seed checklists
        $this->seedChecklists($data['checklists'] ?? []);

        // Seed checklist items
        $this->seedChecklistItems($data['checklist_items'] ?? []);

        $this->command->info('Database seeding completed successfully!');
    }

    /**
     * Seed users from JSON data.
     */
    protected function seedUsers(array $users): void
    {
        $this->command->info('Seeding users...');

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['username' => $userData['username']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'role' => $userData['role'],
                    'lang_pref' => $userData['lang_pref'],
                ]
            );
        }

        $this->command->info('Seeded ' . count($users) . ' users.');
    }

    /**
     * Seed cabinets from JSON data.
     */
    protected function seedCabinets(array $cabinets): void
    {
        $this->command->info('Seeding cabinets...');

        foreach ($cabinets as $cabinetData) {
            $cabinet = Cabinet::find($cabinetData['cabinet_code']);

            if (!$cabinet) {
                $cabinet = new Cabinet();
                $cabinet->cabinet_code = $cabinetData['cabinet_code'];
            }

            $cabinet->bts_site = $cabinetData['bts_site'];
            $cabinet->name = $cabinetData['name'];
            $cabinet->type = $cabinetData['type'];
            $cabinet->lat = $cabinetData['lat'];
            $cabinet->lng = $cabinetData['lng'];
            $cabinet->save();
        }

        $this->command->info('Seeded ' . count($cabinets) . ' cabinets.');
    }

    /**
     * Seed checklists from JSON data.
     */
    protected function seedChecklists(array $checklists): void
    {
        $this->command->info('Seeding checklists...');

        foreach ($checklists as $checklistData) {
            Checklist::updateOrCreate(
                ['id' => $checklistData['id']],
                [
                    'name' => $checklistData['name'],
                    'min_pass_score' => $checklistData['min_pass_score'],
                    'max_critical_allowed' => $checklistData['max_critical_allowed'],
                ]
            );
        }

        $this->command->info('Seeded ' . count($checklists) . ' checklists.');
    }

    /**
     * Seed checklist items from JSON data.
     */
    protected function seedChecklistItems(array $items): void
    {
        $this->command->info('Seeding checklist items...');

        foreach ($items as $itemData) {
            ChecklistItem::updateOrCreate(
                ['id' => $itemData['id']],
                [
                    'checklist_id' => $itemData['checklist_id'],
                    'category' => $itemData['category'],
                    'content_vn' => $itemData['content_vn'],
                    'content_en' => $itemData['content_en'],
                    'content_kh' => $itemData['content_kh'],
                    'max_score' => $itemData['max_score'],
                    'is_critical' => $itemData['is_critical'],
                ]
            );
        }

        $this->command->info('Seeded ' . count($items) . ' checklist items.');
    }
}
