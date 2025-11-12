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
     * Order is important:
     * 1. RoleSeeder must run before UserSeeder to ensure roles exist
     * 2. UserSeeder must run before CampaignSeeder (for created_by/updated_by)
     * 3. CampaignSeeder must run before DonationSeeder (donations need campaigns)
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,      // Must run first to create roles
            UserSeeder::class,      // Then create users and assign roles
            CampaignSeeder::class,  // Create campaigns with current_amount set
            DonationSeeder::class,  // Create donations that match campaign current_amount
        ]);
    }
}
