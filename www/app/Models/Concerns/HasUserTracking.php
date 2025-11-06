<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * HasUserTracking Trait
 *
 * Provides automatic tracking of which user created and last updated a model.
 * Uses custom field names: created_by and updated_by.
 *
 * This trait automatically populates:
 * - created_by: ID of the user who created the record
 * - updated_by: ID of the user who last updated the record
 *
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property-read User|null $creator
 * @property-read User|null $updater
 */
trait HasUserTracking
{
    /**
     * Boot the trait.
     */
    protected static function bootHasUserTracking(): void
    {
        static::creating(function ($model) {
            if (! $model->isDirty('created_by') && Auth::check()) {
                $model->created_by = Auth::id();
            }

            if (! $model->isDirty('updated_by') && Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (! $model->isDirty('updated_by') && Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    /**
     * Get the user who created this record.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     *
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
