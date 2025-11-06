<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\Currency;
use App\Models\Concerns\HasTimestamps;
use App\Models\Concerns\HasUserTracking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Campaign Model
 *
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property string $goal_amount
 * @property string $current_amount
 * @property Currency $currency
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property CampaignStatus $status
 * @property \Illuminate\Support\Carbon|null $creation_date
 * @property \Illuminate\Support\Carbon|null $update_date
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property-read User|null $creator
 * @property-read User|null $updater
 */
class Campaign extends Model
{
    use HasUuids;
    use HasTimestamps;
    use HasUserTracking;

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
        'title',
        'description',
        'goal_amount',
        'current_amount',
        'currency',
        'start_date',
        'end_date',
        'status',
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
            'goal_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'currency' => Currency::class,
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'status' => CampaignStatus::class,
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
     * Generate a new UUID for the model.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['id'];
    }

    /**
     * Convert UUID to binary for storage.
     */
    public function setAttribute($key, $value): mixed
    {
        if ($key === 'id' && is_string($value) && strlen($value) === 36) {
            // Convert UUID string (36 chars with dashes) to binary
            $value = hex2bin(str_replace('-', '', $value));
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Convert binary UUID back to string.
     */
    public function getAttribute($key): mixed
    {
        $value = parent::getAttribute($key);

        if ($key === 'id' && is_string($value) && strlen($value) === 16) {
            // Convert binary back to UUID string format
            $hex = bin2hex($value);
            return sprintf(
                '%s-%s-%s-%s-%s',
                substr($hex, 0, 8),
                substr($hex, 8, 4),
                substr($hex, 12, 4),
                substr($hex, 16, 4),
                substr($hex, 20, 12)
            );
        }

        return $value;
    }

    /**
     * Get the value of the model's primary key.
     */
    public function getKey(): mixed
    {
        $key = $this->getAttribute($this->getKeyName());

        if ($key && is_string($key) && strlen($key) === 16) {
            // Convert binary to UUID string
            return $this->getAttribute('id');
        }

        return $key;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    protected function getKeyForSaveQuery(): mixed
    {
        $key = $this->getKey();

        if (is_string($key) && strlen($key) === 36) {
            // Convert UUID string to binary for query
            return hex2bin(str_replace('-', '', $key));
        }

        return $key;
    }

    /**
     * Scope a query to find by UUID string
     *
     * @param Builder<Campaign> $query
     * @return Builder<Campaign>
     */
    public function scopeFindByUuid(Builder $query, string $uuid): Builder
    {
        $binary = hex2bin(str_replace('-', '', $uuid));
        return $query->where('id', $binary);
    }
}
