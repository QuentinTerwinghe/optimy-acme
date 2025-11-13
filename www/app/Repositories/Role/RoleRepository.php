<?php

declare(strict_types=1);

namespace App\Repositories\Role;

use App\Contracts\Role\RoleRepositoryInterface;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role Repository
 *
 * Handles all data access operations for roles.
 * This class follows the Repository Pattern and Single Responsibility Principle.
 */
final class RoleRepository implements RoleRepositoryInterface
{
    /**
     * Get all roles with their permissions and users count
     *
     * @return Collection<int, Role>
     */
    public function getAllWithRelations(): Collection
    {
        return Role::with(['permissions', 'users'])
            ->withCount('users')
            ->orderBy('name')
            ->get();
    }

    /**
     * Find a role by ID with its relationships
     *
     * @param int $id
     * @return Role|null
     */
    public function findById(int $id): ?Role
    {
        return Role::with(['permissions', 'users'])
            ->withCount('users')
            ->find($id);
    }

    /**
     * Find a role by name
     *
     * @param string $name
     * @param string $guardName
     * @return Role|null
     */
    public function findByName(string $name, string $guardName = 'web'): ?Role
    {
        return Role::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();
    }

    /**
     * Create a new role
     *
     * @param array<string, mixed> $data
     * @return Role
     */
    public function create(array $data): Role
    {
        /** @var Role $role */
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);

        return $role;
    }

    /**
     * Update an existing role
     *
     * @param Role $role
     * @param array<string, mixed> $data
     * @return Role
     */
    public function update(Role $role, array $data): Role
    {
        if (isset($data['name'])) {
            $role->name = $data['name'];
        }

        $role->save();

        /** @var Role $freshRole */
        $freshRole = $role->fresh(['permissions', 'users']);

        return $freshRole;
    }

    /**
     * Delete a role
     *
     * @param Role $role
     * @return bool
     */
    public function delete(Role $role): bool
    {
        return (bool) $role->delete();
    }

    /**
     * Sync permissions to a role
     *
     * @param Role $role
     * @param array<int, string> $permissions
     * @return Role
     */
    public function syncPermissions(Role $role, array $permissions): Role
    {
        $role->syncPermissions($permissions);

        /** @var Role $freshRole */
        $freshRole = $role->fresh(['permissions', 'users']);

        return $freshRole;
    }

    /**
     * Sync users to a role
     *
     * @param Role $role
     * @param array<int, string> $userIds
     * @return Role
     */
    public function syncUsers(Role $role, array $userIds): Role
    {
        // Get existing users with this role
        $existingUsers = User::role($role->name)->pluck('id')->toArray();

        // Remove role from users not in the new list
        $usersToRemove = array_diff($existingUsers, $userIds);
        if (!empty($usersToRemove)) {
            User::whereIn('id', $usersToRemove)
                ->get()
                ->each(fn(User $user) => $user->removeRole($role));
        }

        // Add role to new users
        $usersToAdd = array_diff($userIds, $existingUsers);
        if (!empty($usersToAdd)) {
            User::whereIn('id', $usersToAdd)
                ->get()
                ->each(fn(User $user) => $user->assignRole($role));
        }

        /** @var Role $freshRole */
        $freshRole = $role->fresh(['permissions', 'users']);

        return $freshRole;
    }

    /**
     * Get all available permissions
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        return Permission::orderBy('name')->get();
    }

    /**
     * Check if role name exists (excluding a specific role ID)
     *
     * @param string $name
     * @param string $guardName
     * @param int|null $excludeId
     * @return bool
     */
    public function roleNameExists(string $name, string $guardName = 'web', ?int $excludeId = null): bool
    {
        $query = Role::where('name', $name)
            ->where('guard_name', $guardName);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
