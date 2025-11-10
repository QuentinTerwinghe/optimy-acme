<?php

declare(strict_types=1);

namespace App\Http\Requests\Campaign;

use App\Enums\Campaign\CampaignStatus;
use App\Enums\Common\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Campaign Request
 *
 * Validates data for creating a new campaign
 */
class StoreCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isDraft = $this->input('status') === CampaignStatus::DRAFT->value || $this->input('status') === null;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'goal_amount' => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:0.01'],
            'current_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => [$isDraft ? 'nullable' : 'required', 'string', Rule::enum(Currency::class)],
            'start_date' => [$isDraft ? 'nullable' : 'required', 'date', $isDraft ? '' : 'after_or_equal:today'],
            'end_date' => [$isDraft ? 'nullable' : 'required', 'date', 'after:start_date'],
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
            'start_date.after_or_equal' => 'Start date must be today or later',
            'end_date.after' => 'End date must be after start date',
            'category_id.exists' => 'Selected category does not exist',
            'tags.*.max' => 'Each tag must not exceed 50 characters',
        ];
    }
}
