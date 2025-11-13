<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Role;

use App\Contracts\Role\RoleRepositoryInterface;
use App\DTOs\Role\CreateRoleDTO;
use App\DTOs\Role\UpdateRoleDTO;
use App\Services\Role\RoleService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Unit tests for RoleService
 *
 * Tests the business logic of role management.
 */
class RoleServiceTest extends TestCase
{
    private RoleRepositoryInterface $mockRepository;
    private RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(RoleRepositoryInterface::class);
        $this->service = new RoleService($this->mockRepository);
    }

    public function test_get_all_roles_returns_collection(): void
    {
        $expectedRoles = new Collection([]);
        $this->mockRepository
            ->shouldReceive('getAllWithRelations')
            ->once()
            ->andReturn($expectedRoles);

        $result = $this->service->getAllRoles();

        $this->assertSame($expectedRoles, $result);
    }

    public function test_get_role_by_id_returns_role_when_found(): void
    {
        $role = Mockery::mock(Role::class)->makePartial();
        $role->id = 1;

        $this->mockRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($role);

        $result = $this->service->getRoleById(1);

        $this->assertSame($role, $result);
    }

    public function test_get_role_by_id_throws_exception_when_not_found(): void
    {
        $this->mockRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Role with ID 999 not found');

        $this->service->getRoleById(999);
    }

    public function test_create_role_throws_exception_when_name_exists(): void
    {
        $dto = new CreateRoleDTO(name: 'existing_role');

        $this->mockRepository
            ->shouldReceive('roleNameExists')
            ->with('existing_role', 'web')
            ->once()
            ->andReturn(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Role with name 'existing_role' already exists");

        $this->service->createRole($dto);
    }

    public function test_update_role_throws_exception_when_role_not_found(): void
    {
        $dto = new UpdateRoleDTO(name: 'new_name');

        $this->mockRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(ModelNotFoundException::class);

        $this->service->updateRole(999, $dto);
    }

    public function test_update_role_throws_exception_when_renaming_protected_role(): void
    {
        $role = Mockery::mock(Role::class)->makePartial();
        $role->id = 1;
        $role->name = 'admin';
        $role->guard_name = 'web';

        $dto = new UpdateRoleDTO(name: 'new_admin');

        $this->mockRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($role);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot rename protected role 'admin'");

        $this->service->updateRole(1, $dto);
    }

    public function test_delete_role_throws_exception_when_role_not_found(): void
    {
        $this->mockRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(ModelNotFoundException::class);

        $this->service->deleteRole(999);
    }

    public function test_delete_role_throws_exception_when_deleting_protected_role(): void
    {
        $role = Mockery::mock(Role::class)->makePartial();
        $role->id = 1;
        $role->name = 'admin';

        $this->mockRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($role);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Cannot delete protected role 'admin'");

        $this->service->deleteRole(1);
    }

    public function test_get_all_permissions_delegates_to_repository(): void
    {
        $expectedPermissions = new Collection([]);

        $this->mockRepository
            ->shouldReceive('getAllPermissions')
            ->once()
            ->andReturn($expectedPermissions);

        $result = $this->service->getAllPermissions();

        $this->assertSame($expectedPermissions, $result);
    }

    public function test_get_users_by_role_returns_users_collection(): void
    {
        $role = Mockery::mock(Role::class)->makePartial();
        $role->users = new Collection([]);

        $this->mockRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($role);

        $result = $this->service->getUsersByRole(1);

        $this->assertInstanceOf(Collection::class, $result);
    }
}
