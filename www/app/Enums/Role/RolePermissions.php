<?php

declare(strict_types=1);

namespace App\Enums\Role;

/**
 * Role Management Permissions Enum
 *
 * Defines all permissions related to role management.
 * This provides type safety and prevents typos when checking permissions.
 *
 * Usage:
 * - In code: $user->can(RolePermissions::MANAGE_ROLES->value)
 * - In seeders: Permission::create(['name' => RolePermissions::MANAGE_ROLES->value])
 * - In middleware: Gate::allows(RolePermissions::MANAGE_ROLES->value)
 */
enum RolePermissions: string
{
    /**
     * Can view all roles
     * Typically assigned to: admin (via *)
     */
    case VIEW_ROLES = 'viewRoles';

    /**
     * Can create new roles
     * Typically assigned to: admin (via *)
     */
    case CREATE_ROLES = 'createRoles';

    /**
     * Can edit existing roles
     * Typically assigned to: admin (via *)
     */
    case EDIT_ROLES = 'editRoles';

    /**
     * Can delete roles
     * Typically assigned to: admin (via *)
     */
    case DELETE_ROLES = 'deleteRoles';

    /**
     * Can assign roles to users
     * Typically assigned to: admin (via *)
     */
    case ASSIGN_ROLES = 'assignRoles';

    /**
     * Get human-readable description of the permission
     */
    public function description(): string
    {
        return match ($this) {
            self::VIEW_ROLES => 'Can view all roles',
            self::CREATE_ROLES => 'Can create new roles',
            self::EDIT_ROLES => 'Can edit existing roles',
            self::DELETE_ROLES => 'Can delete roles',
            self::ASSIGN_ROLES => 'Can assign roles to users',
        };
    }

    /**
     * Get all permission values as an array
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn(self $permission) => $permission->value, self::cases());
    }

    /**
     * Get all role management permissions for admin
     *
     * @return array<int, string>
     */
    public static function adminPermissions(): array
    {
        return self::values();
    }
}
