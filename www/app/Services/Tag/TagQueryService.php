<?php

declare(strict_types=1);

namespace App\Services\Tag;

use App\Contracts\Tag\TagQueryServiceInterface;
use App\Models\Campaign\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Tag Query Service
 *
 * Handles all read operations for tags
 * Follows Single Responsibility Principle - only queries/reads
 */
class TagQueryService implements TagQueryServiceInterface
{
    /**
     * Get all tags
     *
     * @return Collection<int, Tag>
     */
    public function getAllTags(): Collection
    {
        try {
            return Tag::query()
                ->orderBy('name', 'asc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to fetch all tags', [
                'error' => $e->getMessage(),
            ]);

            return new Collection();
        }
    }

    /**
     * Find a tag by ID
     *
     * @param int $id
     * @return Tag|null
     */
    public function findById(int $id): ?Tag
    {
        try {
            return Tag::find($id);
        } catch (\Exception $e) {
            Log::error('Failed to find tag by ID', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find a tag by slug
     *
     * @param string $slug
     * @return Tag|null
     */
    public function findBySlug(string $slug): ?Tag
    {
        try {
            return Tag::query()
                ->where('slug', $slug)
                ->first();
        } catch (\Exception $e) {
            Log::error('Failed to find tag by slug', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find tags by names (case-insensitive)
     *
     * @param array<int, string> $names
     * @return Collection<int, Tag>
     */
    public function findByNames(array $names): Collection
    {
        try {
            return Tag::query()
                ->whereIn('name', array_map('strtolower', $names))
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to find tags by names', [
                'names' => $names,
                'error' => $e->getMessage(),
            ]);

            return new Collection();
        }
    }

    /**
     * Search tags by name (partial match)
     *
     * @param string $search
     * @return Collection<int, Tag>
     */
    public function searchByName(string $search): Collection
    {
        try {
            return Tag::query()
                ->where('name', 'like', "%{$search}%")
                ->orderBy('name', 'asc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to search tags by name', [
                'search' => $search,
                'error' => $e->getMessage(),
            ]);

            return new Collection();
        }
    }
}
