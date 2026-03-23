<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name'      => 'Administrator',
                'password'  => Hash::make('Admin@2025'),
                'role'      => 'admin',
                'lang_pref' => 'vn',
            ]
        );

        $this->command->info('Admin account seeded: admin / Admin@2025');
    }
}
