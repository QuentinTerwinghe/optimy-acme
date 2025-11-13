<?php

declare(strict_types=1);

namespace Tests\Feature\Role;

use App\Models\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature tests for Role Management API
 *
 * Tests the complete flow of role management operations including authorization.
 */
class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create wildcard permission for admin
        Permission::create(['name' => '*', 'guard_name' => 'web']);

        // Create admin user with wildcard permission
        $this->adminUser = User::factory()->create();
        $this->adminUser->givePermissionTo('*');

        // Create regular user without admin permission
        $this->regularUser = User::factory()->create();
    }

    public function test_non_admin_cannot_access_role_management(): void
    {
        $this->actingAs($this->regularUser);

        $response = $this->getJson('/api/admin/roles');

        $response->assertStatus(403);
    }

    public function test_admin_can_list_all_roles(): void
    {
        Role::create(['name' => 'editor', 'guard_name' => 'web']);
        Role::create(['name' => 'viewer', 'guard_name' => 'web']);

        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'guard_name',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    public function test_admin_can_view_specific_role(): void
    {
        $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);

        $this->actingAs($this->adminUser);

        $response = $this->getJson("/api/admin/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $role->id,
                    'name' => 'manager',
                    'guard_name' => 'web',
                ]
            ]);
    }

    public function test_admin_can_create_new_role(): void
    {
        Permission::create(['name' => 'edit_posts', 'guard_name' => 'web']);

        $this->actingAs($this->adminUser);

        $response = $this->postJson('/api/admin/roles', [
            'name' => 'content_editor',
            'guard_name' => 'web',
            'permissions' => ['edit_posts'],
            'user_ids' => [],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'content_editor',
                    'guard_name' => 'web',
                ]
            ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'content_editor',
            'guard_name' => 'web',
        ]);
    }

    public function test_cannot_create_role_with_duplicate_name(): void
    {
        Role::create(['name' => 'existing', 'guard_name' => 'web']);

        $this->actingAs($this->adminUser);

        $response = $this->postJson('/api/admin/roles', [
            'name' => 'existing',
            'guard_name' => 'web',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_update_role(): void
    {
        $role = Role::create(['name' => 'old_name', 'guard_name' => 'web']);
        Permission::create(['name' => 'new_permission', 'guard_name' => 'web']);

        $this->actingAs($this->adminUser);

        $response = $this->putJson("/api/admin/roles/{$role->id}", [
            'name' => 'new_name',
            'permissions' => ['new_permission'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'new_name',
                ]
            ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'new_name',
        ]);
    }

    public function test_cannot_rename_protected_role(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $this->actingAs($this->adminUser);

        $response = $this->putJson("/api/admin/roles/{$adminRole->id}", [
            'name' => 'super_admin',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_delete_non_protected_role(): void
    {
        $role = Role::create(['name' => 'temporary', 'guard_name' => 'web']);

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/api/admin/roles/{$role->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_cannot_delete_protected_role(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/api/admin/roles/{$adminRole->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('roles', [
            'id' => $adminRole->id,
            'name' => 'admin',
        ]);
    }

    public function test_admin_can_get_all_permissions(): void
    {
        Permission::create(['name' => 'perm1', 'guard_name' => 'web']);
        Permission::create(['name' => 'perm2', 'guard_name' => 'web']);

        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/permissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'guard_name',
                    ]
                ]
            ]);
    }

    public function test_admin_can_get_all_users(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                    ]
                ]
            ]);
    }

    public function test_admin_can_assign_role_to_users(): void
    {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $user = User::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->postJson('/api/admin/roles', [
            'name' => 'test_role',
            'user_ids' => [$user->id],
        ]);

        $response->assertStatus(200);

        $createdRole = Role::where('name', 'test_role')->first();
        $this->assertTrue($user->fresh()->hasRole($createdRole));
    }

    public function test_updating_role_syncs_users(): void
    {
        $role = Role::create(['name' => 'test_role', 'guard_name' => 'web']);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user1->assignRole($role);

        $this->actingAs($this->adminUser);

        $response = $this->putJson("/api/admin/roles/{$role->id}", [
            'user_ids' => [$user2->id],
        ]);

        $response->assertStatus(200);

        $this->assertFalse($user1->fresh()->hasRole($role));
        $this->assertTrue($user2->fresh()->hasRole($role));
    }
}
