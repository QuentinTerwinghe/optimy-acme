<?php

declare(strict_types=1);

namespace App\Models\Campaign;

use App\Enums\Campaign\CampaignStatus;
use App\Enums\Common\Currency;
use App\Models\Auth\User;
use App\Models\Concerns\HasTimestamps;
use App\Models\Concerns\HasUserTracking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Campaign Model
 *
 * @property string $id UUID stored as char(36) following Laravel standards
 * @property string $title
 * @property string|null $description
 * @property string|null $goal_amount
 * @property string $current_amount
 * @property Currency|null $currency
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property CampaignStatus $status
 * @property int|null $category_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property-read User|null $creator
 * @property-read User|null $updater
 * @property-read Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tag> $tags
 *
 * @use HasFactory<\Database\Factories\Campaign\CampaignFactory>
 */
class Campaign extends Model
{
    /** @use HasFactory<\Database\Factories\Campaign\CampaignFactory> */
    use HasFactory;
    use HasUuids;
    use HasTimestamps;
    use HasUserTracking;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\Campaign\CampaignFactory
    {
        return \Database\Factories\Campaign\CampaignFactory::new();
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
        'title',
        'description',
        'goal_amount',
        'current_amount',
        'currency',
        'start_date',
        'end_date',
        'status',
        'category_id',
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
     * Get the category that the campaign belongs to
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all tags for this campaign
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'campaign_tag');
    }

    /**
     * Get all donations for this campaign
     *
     * @return HasMany<\App\Models\Donation\Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(\App\Models\Donation\Donation::class);
    }
}
