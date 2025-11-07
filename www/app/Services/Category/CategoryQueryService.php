<?php

declare(strict_types=1);

namespace App\Services\Category;

use App\Contracts\Category\CategoryQueryServiceInterface;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Category Query Service
 *
 * Handles all read operations for categories
 * Follows Single Responsibility Principle - only queries/reads
 */
class CategoryQueryService implements CategoryQueryServiceInterface
{
    /**
     * Get all categories
     *
     * @return Collection<int, Category>
     */
    public function getAllCategories(): Collection
    {
        try {
            return Category::query()
                ->orderBy('name', 'asc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to fetch all categories', [
                'error' => $e->getMessage(),
            ]);

            return new Collection();
        }
    }

    /**
     * Get all active categories
     *
     * @return Collection<int, Category>
     */
    public function getActiveCategories(): Collection
    {
        try {
            return Category::query()
                ->where('is_active', true)
                ->orderBy('name', 'asc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to fetch active categories', [
                'error' => $e->getMessage(),
            ]);

            return new Collection();
        }
    }

    /**
     * Find a category by ID
     *
     * @param int $id
     * @return Category|null
     */
    public function findById(int $id): ?Category
    {
        try {
            return Category::find($id);
        } catch (\Exception $e) {
            Log::error('Failed to find category by ID', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find a category by slug
     *
     * @param string $slug
     * @return Category|null
     */
    public function findBySlug(string $slug): ?Category
    {
        try {
            return Category::query()
                ->where('slug', $slug)
                ->first();
        } catch (\Exception $e) {
            Log::error('Failed to find category by slug', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
