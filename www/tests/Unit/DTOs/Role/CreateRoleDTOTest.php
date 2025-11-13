<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\Role;

use App\DTOs\Role\CreateRoleDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateRoleDTO
 *
 * Tests the data transfer object for creating roles.
 */
class CreateRoleDTOTest extends TestCase
{
    public function test_can_create_dto_with_all_fields(): void
    {
        $dto = new CreateRoleDTO(
            name: 'editor',
            guardName: 'web',
            permissions: ['edit_posts', 'view_posts'],
            userIds: ['user-uuid-1', 'user-uuid-2']
        );

        $this->assertSame('editor', $dto->name);
        $this->assertSame('web', $dto->guardName);
        $this->assertSame(['edit_posts', 'view_posts'], $dto->permissions);
        $this->assertSame(['user-uuid-1', 'user-uuid-2'], $dto->userIds);
    }

    public function test_can_create_dto_with_default_values(): void
    {
        $dto = new CreateRoleDTO(name: 'moderator');

        $this->assertSame('moderator', $dto->name);
        $this->assertSame('web', $dto->guardName);
        $this->assertSame([], $dto->permissions);
        $this->assertSame([], $dto->userIds);
    }

    public function test_can_create_dto_from_array(): void
    {
        $data = [
            'name' => 'contributor',
            'guard_name' => 'api',
            'permissions' => ['create_posts'],
            'user_ids' => ['user-uuid-1'],
        ];

        $dto = CreateRoleDTO::fromArray($data);

        $this->assertSame('contributor', $dto->name);
        $this->assertSame('api', $dto->guardName);
        $this->assertSame(['create_posts'], $dto->permissions);
        $this->assertSame(['user-uuid-1'], $dto->userIds);
    }

    public function test_from_array_uses_default_guard_name_when_not_provided(): void
    {
        $data = ['name' => 'viewer'];

        $dto = CreateRoleDTO::fromArray($data);

        $this->assertSame('viewer', $dto->name);
        $this->assertSame('web', $dto->guardName);
        $this->assertSame([], $dto->permissions);
        $this->assertSame([], $dto->userIds);
    }

    public function test_can_convert_dto_to_array(): void
    {
        $dto = new CreateRoleDTO(
            name: 'manager',
            guardName: 'web',
            permissions: ['manage_all'],
            userIds: ['user-1', 'user-2']
        );

        $array = $dto->toArray();

        $this->assertSame([
            'name' => 'manager',
            'guard_name' => 'web',
            'permissions' => ['manage_all'],
            'user_ids' => ['user-1', 'user-2'],
        ], $array);
    }

    public function test_dto_is_readonly(): void
    {
        $dto = new CreateRoleDTO(name: 'test');

        $this->expectException(\Error::class);
        $dto->name = 'changed';
    }
}
