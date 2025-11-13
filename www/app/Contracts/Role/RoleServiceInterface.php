<?php

declare(strict_types=1);

namespace App\Contracts\Role;

use App\DTOs\Role\CreateRoleDTO;
use App\DTOs\Role\UpdateRoleDTO;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

/**
 * Role Service Interface
 *
 * Defines the contract for role business logic operations.
 * This interface follows the Single Responsibility Principle and Dependency Inversion Principle.
 */
interface RoleServiceInterface
{
    /**
     * Get all roles with their relationships
     *
     * @return Collection<int, Role>
     */
    public function getAllRoles(): Collection;

    /**
     * Get a specific role by ID
     *
     * @param int $id
     * @return Role
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getRoleById(int $id): Role;

    /**
     * Create a new role with permissions and users
     *
     * @param CreateRoleDTO $dto
     * @return Role
     * @throws \InvalidArgumentException if role name already exists
     */
    public function createRole(CreateRoleDTO $dto): Role;

    /**
     * Update an existing role
     *
     * @param int $id
     * @param UpdateRoleDTO $dto
     * @return Role
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException if new name already exists
     */
    public function updateRole(int $id, UpdateRoleDTO $dto): Role;

    /**
     * Delete a role
     *
     * @param int $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \RuntimeException if trying to delete a protected role
     */
    public function deleteRole(int $id): bool;

    /**
     * Get all available permissions
     *
     * @return Collection<int, \Spatie\Permission\Models\Permission>
     */
    public function getAllPermissions(): Collection;

    /**
     * Get users assigned to a specific role
     *
     * @param int $roleId
     * @return Collection<int, \App\Models\Auth\User>
     */
    public function getUsersByRole(int $roleId): Collection;
}
