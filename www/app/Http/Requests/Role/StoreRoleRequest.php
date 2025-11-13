<?php

declare(strict_types=1);

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Role Request
 *
 * Validates data for creating a new role.
 * This follows the Single Responsibility Principle by separating validation logic.
 */
class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled by middleware, so this always returns true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('roles', 'name')->where('guard_name', $this->input('guard_name', 'web')),
            ],
            'guard_name' => [
                'sometimes',
                'string',
                'in:web,api',
            ],
            'permissions' => [
                'sometimes',
                'array',
            ],
            'permissions.*' => [
                'string',
                'exists:permissions,name',
            ],
            'user_ids' => [
                'sometimes',
                'array',
            ],
            'user_ids.*' => [
                'string',
                'uuid',
                'exists:users,id',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'role name',
            'guard_name' => 'guard name',
            'permissions' => 'permissions',
            'permissions.*' => 'permission',
            'user_ids' => 'user IDs',
            'user_ids.*' => 'user ID',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The role name is required.',
            'name.unique' => 'A role with this name already exists.',
            'name.alpha_dash' => 'The role name may only contain letters, numbers, dashes, and underscores.',
            'permissions.*.exists' => 'One or more selected permissions do not exist.',
            'user_ids.*.uuid' => 'One or more user IDs are not valid UUIDs.',
            'user_ids.*.exists' => 'One or more selected users do not exist.',
        ];
    }
}
