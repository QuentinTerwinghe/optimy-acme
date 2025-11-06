<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * HasTimestamps Trait
 *
 * Provides automatic tracking of creation and update timestamps.
 * Uses custom field names: creation_date and update_date instead of created_at/updated_at.
 *
 * This trait automatically populates:
 * - creation_date: Set when the model is first created
 * - update_date: Updated every time the model is saved
 *
 * @property Carbon|null $creation_date
 * @property Carbon|null $update_date
 */
trait HasTimestamps
{
    /**
     * Boot the trait.
     */
    protected static function bootHasTimestamps(): void
    {
        static::creating(function ($model) {
            if (! $model->isDirty('creation_date')) {
                $model->creation_date = Carbon::now();
            }

            if (! $model->isDirty('update_date')) {
                $model->update_date = Carbon::now();
            }
        });

        static::updating(function ($model) {
            if (! $model->isDirty('update_date')) {
                $model->update_date = Carbon::now();
            }
        });
    }

    /**
     * Get the name of the "created at" column.
     */
    public function getCreatedAtColumn(): string
    {
        return 'creation_date';
    }

    /**
     * Get the name of the "updated at" column.
     */
    public function getUpdatedAtColumn(): string
    {
        return 'update_date';
    }

    /**
     * Initialize the HasTimestamps trait for an instance.
     */
    public function initializeHasTimestamps(): void
    {
        // Merge the timestamp fields into the casts array
        $this->mergeCasts([
            'creation_date' => 'datetime',
            'update_date' => 'datetime',
        ]);
    }
}
