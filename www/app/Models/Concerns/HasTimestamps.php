<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * HasTimestamps Trait
 *
 * Ensures models use Laravel's standard timestamp fields (created_at, updated_at).
 * Laravel automatically manages these fields when $timestamps = true is set on the model.
 *
 * This trait provides:
 * - Automatic casting of timestamp fields to Carbon instances
 * - Type-safe property annotations
 *
 * Laravel automatically handles:
 * - created_at: Set when the model is first created
 * - updated_at: Updated every time the model is saved
 *
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
trait HasTimestamps
{
    /**
     * Initialize the HasTimestamps trait for an instance.
     *
     * Ensures timestamp fields are cast to datetime instances.
     * Laravel handles the actual timestamp management automatically.
     */
    public function initializeHasTimestamps(): void
    {
        // Merge the timestamp fields into the casts array
        // Laravel will automatically populate these when $timestamps = true
        $this->mergeCasts([
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);
    }
}
