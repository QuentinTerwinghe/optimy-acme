<?php

declare(strict_types=1);

namespace App\Services\Role;

use App\Contracts\Role\RoleRepositoryInterface;
use App\Contracts\Role\RoleServiceInterface;
use App\DTOs\Role\CreateRoleDTO;
use App\DTOs\Role\UpdateRoleDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Role Service
 *
 * Handles all business logic for role management operations.
 * This class follows the Single Responsibility Principle and depends on abstractions (DIP).
 */
final class RoleService implements RoleServiceInterface
{
    /**
     * Protected roles that cannot be deleted
     *
     * @var array<int, string>
     */
    private const PROTECTED_ROLES = ['admin', 'user'];

    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository
    ) {
    }

    /**
     * Get all roles with their relationships
     *
     * @return Collection<int, Role>
     */
    public function getAllRoles(): Collection
    {
        return $this->roleRepository->getAllWithRelations();
    }

    /**
     * Get a specific role by ID
     *
     * @param int $id
     * @return Role
     * @throws ModelNotFoundException
     */
    public function getRoleById(int $id): Role
    {
        $role = $this->roleRepository->findById($id);

        if ($role === null) {
            throw new ModelNotFoundException("Role with ID {$id} not found");
        }

        return $role;
    }

    /**
     * Create a new role with permissions and users
     *
     * @param CreateRoleDTO $dto
     * @return Role
     * @throws InvalidArgumentException if role name already exists
     */
    public function createRole(CreateRoleDTO $dto): Role
    {
        // Check if role name already exists
        if ($this->roleRepository->roleNameExists($dto->name, $dto->guardName)) {
            throw new InvalidArgumentException(
                "Role with name '{$dto->name}' already exists for guard '{$dto->guardName}'"
            );
        }

        try {
            DB::beginTransaction();

            // Create the role
            $role = $this->roleRepository->create([
                'name' => $dto->name,
                'guard_name' => $dto->guardName,
            ]);

            // Sync permissions if provided
            if (!empty($dto->permissions)) {
                $this->roleRepository->syncPermissions($role, $dto->permissions);
            }

            // Assign role to users if provided
            if (!empty($dto->userIds)) {
                $this->roleRepository->syncUsers($role, $dto->userIds);
            }

            DB::commit();

            Log::info('Role created successfully', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions_count' => count($dto->permissions),
                'users_count' => count($dto->userIds),
            ]);

            /** @var Role $freshRole */
            $freshRole = $role->fresh(['permissions', 'users']);

            return $freshRole;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Failed to create role', [
                'role_name' => $dto->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException('Failed to create role: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update an existing role
     *
     * @param int $id
     * @param UpdateRoleDTO $dto
     * @return Role
     * @throws ModelNotFoundException
     * @throws InvalidArgumentException if new name already exists
     */
    public function updateRole(int $id, UpdateRoleDTO $dto): Role
    {
        $role = $this->getRoleById($id);

        // Check if trying to rename a protected role
        if ($dto->name !== null && in_array($role->name, self::PROTECTED_ROLES, true)) {
            throw new InvalidArgumentException(
                "Cannot rename protected role '{$role->name}'"
            );
        }

        // Check if new name already exists
        if ($dto->name !== null && $this->roleRepository->roleNameExists($dto->name, (string) $role->guard_name, $id)) {
            throw new InvalidArgumentException(
                "Role with name '{$dto->name}' already exists"
            );
        }

        try {
            DB::beginTransaction();

            // Update role name if provided
            if ($dto->name !== null) {
                $role = $this->roleRepository->update($role, ['name' => $dto->name]);
            }

            // Sync permissions if provided
            if ($dto->permissions !== null) {
                $role = $this->roleRepository->syncPermissions($role, $dto->permissions);
            }

            // Sync users if provided
            if ($dto->userIds !== null) {
                $role = $this->roleRepository->syncUsers($role, $dto->userIds);
            }

            DB::commit();

            Log::info('Role updated successfully', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'changes' => $dto->toArray(),
            ]);

            /** @var Role $freshRole */
            $freshRole = $role->fresh(['permissions', 'users']);

            return $freshRole;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Failed to update role', [
                'role_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException('Failed to update role: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a role
     *
     * @param int $id
     * @return bool
     * @throws ModelNotFoundException
     * @throws RuntimeException if trying to delete a protected role
     */
    public function deleteRole(int $id): bool
    {
        $role = $this->getRoleById($id);

        // Prevent deletion of protected roles
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            throw new RuntimeException(
                "Cannot delete protected role '{$role->name}'"
            );
        }

        try {
            DB::beginTransaction();

            $deleted = $this->roleRepository->delete($role);

            DB::commit();

            Log::info('Role deleted successfully', [
                'role_id' => $id,
                'role_name' => $role->name,
            ]);

            return $deleted;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Failed to delete role', [
                'role_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new RuntimeException('Failed to delete role: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get all available permissions
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        return $this->roleRepository->getAllPermissions();
    }

    /**
     * Get users assigned to a specific role
     *
     * @param int $roleId
     * @return Collection
     */
    public function getUsersByRole(int $roleId): Collection
    {
        $role = $this->getRoleById($roleId);

        return $role->users;
    }
}
