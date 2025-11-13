<?php

declare(strict_types=1);

namespace App\DTOs\Role;

/**
 * Create Role Data Transfer Object
 *
 * Encapsulates data required to create a new role.
 * This DTO ensures type safety and data validation at the service layer.
 *
 * @property string $name The name of the role
 * @property string|null $guardName The guard name (defaults to 'web')
 * @property array<int, string> $permissions Array of permission names to assign to the role
 * @property array<int, string> $userIds Array of user UUIDs to assign this role to
 */
final readonly class CreateRoleDTO
{
    /**
     * @param string $name The name of the role (e.g., 'editor', 'moderator')
     * @param string $guardName The guard name (defaults to 'web')
     * @param array<int, string> $permissions Array of permission names
     * @param array<int, string> $userIds Array of user UUIDs
     */
    public function __construct(
        public string $name,
        public string $guardName = 'web',
        public array $permissions = [],
        public array $userIds = [],
    ) {
    }

    /**
     * Create DTO from validated request data
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            guardName: $data['guard_name'] ?? 'web',
            permissions: $data['permissions'] ?? [],
            userIds: $data['user_ids'] ?? [],
        );
    }

    /**
     * Convert DTO to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'guard_name' => $this->guardName,
            'permissions' => $this->permissions,
            'user_ids' => $this->userIds,
        ];
    }
}
