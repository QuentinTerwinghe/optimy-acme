<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\Role;

use App\DTOs\Role\UpdateRoleDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UpdateRoleDTO
 *
 * Tests the data transfer object for updating roles.
 */
class UpdateRoleDTOTest extends TestCase
{
    public function test_can_create_dto_with_all_fields(): void
    {
        $dto = new UpdateRoleDTO(
            name: 'new_name',
            permissions: ['permission1', 'permission2'],
            userIds: ['user-1', 'user-2']
        );

        $this->assertSame('new_name', $dto->name);
        $this->assertSame(['permission1', 'permission2'], $dto->permissions);
        $this->assertSame(['user-1', 'user-2'], $dto->userIds);
    }

    public function test_can_create_dto_with_null_values(): void
    {
        $dto = new UpdateRoleDTO();

        $this->assertNull($dto->name);
        $this->assertNull($dto->permissions);
        $this->assertNull($dto->userIds);
    }

    public function test_can_create_dto_from_array(): void
    {
        $data = [
            'name' => 'updated_role',
            'permissions' => ['perm1'],
            'user_ids' => ['user-uuid'],
        ];

        $dto = UpdateRoleDTO::fromArray($data);

        $this->assertSame('updated_role', $dto->name);
        $this->assertSame(['perm1'], $dto->permissions);
        $this->assertSame(['user-uuid'], $dto->userIds);
    }

    public function test_from_array_handles_partial_data(): void
    {
        $data = ['name' => 'only_name'];

        $dto = UpdateRoleDTO::fromArray($data);

        $this->assertSame('only_name', $dto->name);
        $this->assertNull($dto->permissions);
        $this->assertNull($dto->userIds);
    }

    public function test_from_array_handles_empty_array(): void
    {
        $dto = UpdateRoleDTO::fromArray([]);

        $this->assertNull($dto->name);
        $this->assertNull($dto->permissions);
        $this->assertNull($dto->userIds);
    }

    public function test_to_array_only_returns_non_null_values(): void
    {
        $dto = new UpdateRoleDTO(name: 'test_name');

        $array = $dto->toArray();

        $this->assertSame(['name' => 'test_name'], $array);
        $this->assertArrayNotHasKey('permissions', $array);
        $this->assertArrayNotHasKey('user_ids', $array);
    }

    public function test_to_array_with_all_values(): void
    {
        $dto = new UpdateRoleDTO(
            name: 'full',
            permissions: ['p1', 'p2'],
            userIds: ['u1']
        );

        $array = $dto->toArray();

        $this->assertSame([
            'name' => 'full',
            'permissions' => ['p1', 'p2'],
            'user_ids' => ['u1'],
        ], $array);
    }

    public function test_has_changes_returns_false_when_all_null(): void
    {
        $dto = new UpdateRoleDTO();

        $this->assertFalse($dto->hasChanges());
    }

    public function test_has_changes_returns_true_when_name_set(): void
    {
        $dto = new UpdateRoleDTO(name: 'changed');

        $this->assertTrue($dto->hasChanges());
    }

    public function test_has_changes_returns_true_when_permissions_set(): void
    {
        $dto = new UpdateRoleDTO(permissions: ['perm1']);

        $this->assertTrue($dto->hasChanges());
    }

    public function test_has_changes_returns_true_when_user_ids_set(): void
    {
        $dto = new UpdateRoleDTO(userIds: ['user1']);

        $this->assertTrue($dto->hasChanges());
    }

    public function test_dto_is_readonly(): void
    {
        $dto = new UpdateRoleDTO(name: 'test');

        $this->expectException(\Error::class);
        $dto->name = 'changed';
    }
}
