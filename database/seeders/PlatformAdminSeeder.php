<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => config('platform.admin.email')],
            [
                'name' => config('platform.admin.name'),
                'email_verified_at' => now(),
                'password' => Hash::make(config('platform.admin.password')),
                'global_role' => 'platform_admin',
                'current_school_id' => null,
            ],
        );
    }
}
