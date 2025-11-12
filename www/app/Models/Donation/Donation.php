<?php

declare(strict_types=1);

namespace App\Models\Donation;

use App\Enums\Donation\DonationStatus;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Models\Concerns\HasTimestamps;
use App\Models\Concerns\HasUserTracking;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Donation Model
 *
 * @property string $id UUID stored as char(36) following Laravel standards
 * @property string $campaign_id
 * @property string $user_id
 * @property string $amount
 * @property DonationStatus $status
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property-read User|null $creator
 * @property-read User|null $updater
 * @property-read Campaign $campaign
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment\Payment> $payments
 *
 * @use HasFactory<\Database\Factories\Donation\DonationFactory>
 */
class Donation extends Model
{
    /** @use HasFactory<\Database\Factories\Donation\DonationFactory> */
    use HasFactory;
    use HasUuids;
    use HasTimestamps;
    use HasUserTracking;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\Donation\DonationFactory
    {
        return \Database\Factories\Donation\DonationFactory::new();
    }

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'campaign_id',
        'user_id',
        'amount',
        'status',
        'error_message',
    ];

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'amount' => 'decimal:2',
            'status' => DonationStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Get the status label attribute.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        // Add status label
        $array['status_label'] = $this->status_label;

        return $array;
    }

    /**
     * Get the campaign that this donation belongs to
     *
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the user who made this donation
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the donation is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    /**
     * Check if the donation has failed
     */
    public function hasFailed(): bool
    {
        return $this->status->hasFailed();
    }

    /**
     * Check if the donation is still pending
     */
    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    /**
     * Get the payments associated with this donation
     *
     * @return HasMany<\App\Models\Payment\Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\Payment\Payment::class);
    }
}
