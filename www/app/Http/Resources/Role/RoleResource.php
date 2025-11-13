<?php

declare(strict_types=1);

namespace App\Http\Resources\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

/**
 * Role API Resource
 *
 * Transforms Role model data for API responses.
 * This provides a consistent API response format.
 *
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'users_count' => $this->when(
                $this->relationLoaded('users') || isset($this->users_count),
                fn() => $this->users_count ?? $this->users->count()
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
