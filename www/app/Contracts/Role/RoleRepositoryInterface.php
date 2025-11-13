<?php

declare(strict_types=1);

namespace App\Contracts\Role;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

/**
 * Role Repository Interface
 *
 * Defines the contract for role data access operations.
 * This interface follows the Repository Pattern and Dependency Inversion Principle.
 */
interface RoleRepositoryInterface
{
    /**
     * Get all roles with their permissions and users count
     *
     * @return Collection<int, Role>
     */
    public function getAllWithRelations(): Collection;

    /**
     * Find a role by ID with its relationships
     *
     * @param int $id
     * @return Role|null
     */
    public function findById(int $id): ?Role;

    /**
     * Find a role by name
     *
     * @param string $name
     * @param string $guardName
     * @return Role|null
     */
    public function findByName(string $name, string $guardName = 'web'): ?Role;

    /**
     * Create a new role
     *
     * @param array<string, mixed> $data
     * @return Role
     */
    public function create(array $data): Role;

    /**
     * Update an existing role
     *
     * @param Role $role
     * @param array<string, mixed> $data
     * @return Role
     */
    public function update(Role $role, array $data): Role;

    /**
     * Delete a role
     *
     * @param Role $role
     * @return bool
     */
    public function delete(Role $role): bool;

    /**
     * Sync permissions to a role
     *
     * @param Role $role
     * @param array<int, string> $permissions
     * @return Role
     */
    public function syncPermissions(Role $role, array $permissions): Role;

    /**
     * Sync users to a role
     *
     * @param Role $role
     * @param array<int, string> $userIds
     * @return Role
     */
    public function syncUsers(Role $role, array $userIds): Role;

    /**
     * Get all available permissions
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection;

    /**
     * Check if role name exists (excluding a specific role ID)
     *
     * @param string $name
     * @param string $guardName
     * @param int|null $excludeId
     * @return bool
     */
    public function roleNameExists(string $name, string $guardName = 'web', ?int $excludeId = null): bool;
}
