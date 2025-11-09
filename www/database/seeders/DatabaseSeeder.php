<?php

namespace Database\Seeders;

use App\Models\Auth\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order is important: RoleSeeder must run before UserSeeder
     * to ensure roles exist when assigning them to users.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,      // Must run first to create roles
            UserSeeder::class,      // Then create users and assign roles
            CampaignSeeder::class,
        ]);
    }
}
