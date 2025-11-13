<?php

declare(strict_types=1);

namespace App\Http\Controllers\Role;

use App\Contracts\Role\RoleServiceInterface;
use App\DTOs\Role\CreateRoleDTO;
use App\DTOs\Role\UpdateRoleDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\Role\PermissionResource;
use App\Http\Resources\Role\RoleResource;
use App\Http\Resources\Role\UserResource;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;
use RuntimeException;

/**
 * Role Controller
 *
 * Handles HTTP requests for role management operations.
 * This controller follows the Single Responsibility Principle (only handles HTTP).
 * Business logic is delegated to the RoleService (Dependency Inversion Principle).
 *
 * All routes are protected by the 'admin' middleware to ensure only users
 * with the wildcard (*) permission can access role management.
 */
class RoleController extends Controller
{
    public function __construct(
        private readonly RoleServiceInterface $roleService
    ) {
    }

    /**
     * Display a listing of all roles with their relationships.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $roles = $this->roleService->getAllRoles();

        return RoleResource::collection($roles);
    }

    /**
     * Display the specified role.
     *
     * @param int $id
     * @return RoleResource|JsonResponse
     */
    public function show(int $id): RoleResource|JsonResponse
    {
        try {
            $role = $this->roleService->getRoleById($id);

            return new RoleResource($role);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Role not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Store a newly created role.
     *
     * @param StoreRoleRequest $request
     * @return RoleResource|JsonResponse
     */
    public function store(StoreRoleRequest $request): RoleResource|JsonResponse
    {
        try {
            $dto = CreateRoleDTO::fromArray($request->validated());
            $role = $this->roleService->createRole($dto);

            return new RoleResource($role);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'error' => $e->getMessage(),
            ], 422);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Failed to create role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified role.
     *
     * @param UpdateRoleRequest $request
     * @param int $id
     * @return RoleResource|JsonResponse
     */
    public function update(UpdateRoleRequest $request, int $id): RoleResource|JsonResponse
    {
        try {
            $dto = UpdateRoleDTO::fromArray($request->validated());
            $role = $this->roleService->updateRole($id, $dto);

            return new RoleResource($role);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Role not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'error' => $e->getMessage(),
            ], 422);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Failed to update role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified role.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->roleService->deleteRole($id);

            return response()->json([
                'message' => 'Role deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Role not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => 'Failed to delete role',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get all available permissions.
     *
     * @return AnonymousResourceCollection
     */
    public function permissions(): AnonymousResourceCollection
    {
        $permissions = $this->roleService->getAllPermissions();

        return PermissionResource::collection($permissions);
    }

    /**
     * Get all users (for role assignment).
     *
     * @return AnonymousResourceCollection
     */
    public function users(): AnonymousResourceCollection
    {
        $users = User::orderBy('name')->get();

        return UserResource::collection($users);
    }

    /**
     * Get users assigned to a specific role.
     *
     * @param int $id
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function roleUsers(int $id): AnonymousResourceCollection|JsonResponse
    {
        try {
            $users = $this->roleService->getUsersByRole($id);

            return UserResource::collection($users);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Role not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
