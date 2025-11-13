<?php

declare(strict_types=1);

namespace App\DTOs\Role;

/**
 * Update Role Data Transfer Object
 *
 * Encapsulates data required to update an existing role.
 * This DTO ensures type safety and data validation at the service layer.
 *
 * @property string|null $name The new name of the role (if changing)
 * @property array<int, string>|null $permissions Array of permission names to sync with the role
 * @property array<int, string>|null $userIds Array of user UUIDs to sync with this role
 */
final readonly class UpdateRoleDTO
{
    /**
     * @param string|null $name The new name of the role (null if not changing)
     * @param array<int, string>|null $permissions Array of permission names (null if not changing)
     * @param array<int, string>|null $userIds Array of user UUIDs (null if not changing)
     */
    public function __construct(
        public ?string $name = null,
        public ?array $permissions = null,
        public ?array $userIds = null,
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
            name: $data['name'] ?? null,
            permissions: isset($data['permissions']) ? $data['permissions'] : null,
            userIds: isset($data['user_ids']) ? $data['user_ids'] : null,
        );
    }

    /**
     * Convert DTO to array (only non-null values)
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->permissions !== null) {
            $data['permissions'] = $this->permissions;
        }

        if ($this->userIds !== null) {
            $data['user_ids'] = $this->userIds;
        }

        return $data;
    }

    /**
     * Check if there are any changes to apply
     *
     * @return bool
     */
    public function hasChanges(): bool
    {
        return $this->name !== null
            || $this->permissions !== null
            || $this->userIds !== null;
    }
}
