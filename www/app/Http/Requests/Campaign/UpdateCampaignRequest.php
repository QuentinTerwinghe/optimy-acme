<?php

declare(strict_types=1);

namespace App\Http\Requests\Campaign;

use App\Enums\Campaign\CampaignStatus;
use App\Enums\Common\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Campaign Request
 *
 * Validates data for updating an existing campaign
 */
class UpdateCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Implement authorization logic (e.g., check user permissions)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isDraft = $this->input('status') === CampaignStatus::DRAFT->value;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'goal_amount' => ['sometimes', $isDraft ? 'nullable' : 'required', 'numeric', 'min:0.01'],
            'current_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', $isDraft ? 'nullable' : 'required', 'string', Rule::enum(Currency::class)],
            'start_date' => ['sometimes', $isDraft ? 'nullable' : 'required', 'date'],
            'end_date' => ['sometimes', $isDraft ? 'nullable' : 'required', 'date', 'after:start_date'],
            'status' => ['nullable', 'string', Rule::enum(CampaignStatus::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['required', 'string', 'max:50'],
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
            'title.required' => 'Campaign title is required',
            'goal_amount.required' => 'Goal amount is required',
            'goal_amount.min' => 'Goal amount must be at least 0.01',
            'end_date.after' => 'End date must be after start date',
            'category_id.exists' => 'Selected category does not exist',
            'tags.*.max' => 'Each tag must not exceed 50 characters',
        ];
    }
}
