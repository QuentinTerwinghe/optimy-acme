<?php

namespace Database\Seeders;

use App\Enums\Campaign\CampaignPermissions;
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
     * - campaign_manager: Manages all campaigns
     * - user: Basic user role (manages only own campaigns)
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions from enum
        foreach (CampaignPermissions::cases() as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission->value, 'guard_name' => 'web']
            );
        }

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

        // Create the campaign_manager role and assign permissions
        $campaignManagerRole = Role::firstOrCreate([
            'name' => 'campaign_manager',
            'guard_name' => 'web',
        ]);

        // Campaign managers can manage all campaigns
        $campaignManagerRole->syncPermissions(
            CampaignPermissions::campaignManagerPermissions()
        );

        // Create the user role and assign permissions
        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        // Regular users can only manage their own campaigns
        $userRole->syncPermissions(
            CampaignPermissions::regularUserPermissions()
        );

        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════════════════╗');
        $this->command->info('║         ROLES & PERMISSIONS CREATED SUCCESSFULLY       ║');
        $this->command->info('╚════════════════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('✓ admin role created with full access (*)');
        $this->command->info('✓ campaign_manager role created with manageAllCampaigns permission');
        $this->command->info('✓ user role created with own campaigns permissions');
        $this->command->info('');
        $this->command->info('Permissions created:');
        $this->command->info('  • manageAllCampaigns (admin, campaign_manager)');
        $this->command->info('  • createCampaign (all roles)');
        $this->command->info('  • editOwnCampaign (all roles)');
        $this->command->info('  • deleteOwnCampaign (all roles)');
        $this->command->info('  • viewCampaigns (all roles)');
        $this->command->info('');
    }
}
