<?php

declare(strict_types=1);

namespace App\Http\Requests\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for initializing a payment.
 */
class InitializePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated to initialize a payment
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'campaign_id' => ['required', 'string', 'exists:campaigns,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'payment_method' => [
                'required',
                'string',
                Rule::in(PaymentMethodEnum::values()),
            ],
            'metadata' => ['nullable', 'array'],
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
            'campaign_id.required' => 'Campaign ID is required',
            'campaign_id.exists' => 'The selected campaign does not exist',
            'amount.required' => 'Donation amount is required',
            'amount.min' => 'Donation amount must be at least $0.01',
            'amount.max' => 'Donation amount cannot exceed $999,999.99',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Invalid payment method selected',
        ];
    }

    /**
     * Get the validated payment method as an enum.
     */
    public function getPaymentMethod(): PaymentMethodEnum
    {
        return PaymentMethodEnum::from($this->validated('payment_method'));
    }

    /**
     * Get the validated metadata or empty array.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->validated('metadata', []);
    }
}
