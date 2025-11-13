<?php

declare(strict_types=1);

namespace App\Http\Resources\Role;

use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User API Resource (for Role management)
 *
 * Transforms User model data for API responses in role management context.
 * This is a simplified version focused on role assignment needs.
 *
 * @mixin User
 */
class UserResource extends JsonResource
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
            'email' => $this->email,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
