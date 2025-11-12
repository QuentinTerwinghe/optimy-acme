<?php

namespace App\Models\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Donation\Donation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @use HasFactory<\Database\Factories\Payment\PaymentFactory>
 */
class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\Payment\PaymentFactory> */
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\Payment\PaymentFactory
    {
        return \Database\Factories\Payment\PaymentFactory::new();
    }

    protected $fillable = [
        'donation_id',
        'payment_method',
        'status',
        'amount',
        'currency',
        'transaction_id',
        'gateway_response',
        'error_message',
        'error_code',
        'metadata',
        'initiated_at',
        'completed_at',
        'failed_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'payment_method' => PaymentMethodEnum::class,
        'status' => PaymentStatusEnum::class,
    ];

    /**
     * Get the donation that this payment belongs to.
     *
     * @return BelongsTo<Donation, $this>
     */
    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    /**
     * Check if the payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === PaymentStatusEnum::COMPLETED;
    }

    /**
     * Check if the payment has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentStatusEnum::FAILED;
    }

    /**
     * Check if the payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatusEnum::PENDING;
    }

    /**
     * Check if the payment is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === PaymentStatusEnum::PROCESSING;
    }

    /**
     * Check if the payment is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === PaymentStatusEnum::REFUNDED;
    }

    /**
     * Mark the payment as completed.
     *
     * @param array<string, mixed>|null $gatewayResponse
     */
    public function markAsCompleted(string $transactionId, ?array $gatewayResponse = null): void
    {
        $this->update([
            'status' => PaymentStatusEnum::COMPLETED,
            'transaction_id' => $transactionId,
            'gateway_response' => json_encode($gatewayResponse),
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the payment as failed.
     *
     * @param array<string, mixed>|null $gatewayResponse
     */
    public function markAsFailed(string $errorMessage, ?string $errorCode = null, ?array $gatewayResponse = null): void
    {
        $this->update([
            'status' => PaymentStatusEnum::FAILED,
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
            'gateway_response' => json_encode($gatewayResponse),
            'failed_at' => now(),
        ]);
    }

    /**
     * Mark the payment as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => PaymentStatusEnum::PROCESSING,
        ]);
    }

    /**
     * Mark the payment as refunded.
     */
    public function markAsRefunded(?string $refundTransactionId = null): void
    {
        $this->update([
            'status' => PaymentStatusEnum::REFUNDED,
            'transaction_id' => $refundTransactionId ?? $this->transaction_id,
            'refunded_at' => now(),
        ]);
    }
}
