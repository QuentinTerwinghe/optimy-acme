<?php

namespace App\Models\Auth;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Model
 *
 * @property string $id UUID stored as char(36) following Laravel standards
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasUuids, Notifiable, HasRoles {
        HasRoles::hasPermissionTo as protected hasPermissionToTrait;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\UserFactory
    {
        return \Database\Factories\UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Override hasPermissionTo to support wildcard permissions
     *
     * If user has the wildcard '*' permission, they have all permissions
     *
     * @param string|\Spatie\Permission\Contracts\Permission $permission
     * @param string|null $guardName
     * @return bool
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // Check if wildcard permission exists in the system
        // This prevents errors when the permission doesn't exist (e.g., in tests)
        try {
            if ($this->hasPermissionToTrait('*', $guardName)) {
                return true;
            }
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            // Wildcard permission doesn't exist, continue to normal permission check
        }

        // Fall back to default Spatie permission checking
        return $this->hasPermissionToTrait($permission, $guardName);
    }
}
