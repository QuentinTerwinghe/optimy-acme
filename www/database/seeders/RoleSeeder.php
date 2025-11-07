<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates the application roles with their respective permissions:
     * - admin: Full access with wildcard permission (*)
     * - campaign_manager: Manages campaigns
     * - user: Basic user role
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create the wildcard permission for full access
        $adminPermission = Permission::firstOrCreate([
            'name' => '*',
            'guard_name' => 'web',
        ]);

        // Create the admin role and assign the wildcard permission
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        // Assign the wildcard permission to admin role
        if (!$adminRole->hasPermissionTo('*')) {
            $adminRole->givePermissionTo($adminPermission);
        }

        // Create the campaign_manager role
        $campaignManagerRole = Role::firstOrCreate([
            'name' => 'campaign_manager',
            'guard_name' => 'web',
        ]);

        // Create the user role
        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════════════════╗');
        $this->command->info('║              ROLES CREATED SUCCESSFULLY                ║');
        $this->command->info('╚════════════════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('✓ admin role created with full access (*)');
        $this->command->info('✓ campaign_manager role created');
        $this->command->info('✓ user role created');
        $this->command->info('');
    }
}
