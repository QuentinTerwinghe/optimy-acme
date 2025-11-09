<?php

declare(strict_types=1);

namespace App\Contracts\Tag;

use App\Models\Campaign\Tag;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tag Query Service Interface
 *
 * Defines the contract for tag read operations
 * Follows Single Responsibility Principle - handles only queries/reads
 */
interface TagQueryServiceInterface
{
    /**
     * Get all tags
     *
     * @return Collection<int, Tag>
     */
    public function getAllTags(): Collection;

    /**
     * Find a tag by ID
     *
     * @param int $id
     * @return Tag|null
     */
    public function findById(int $id): ?Tag;

    /**
     * Find a tag by slug
     *
     * @param string $slug
     * @return Tag|null
     */
    public function findBySlug(string $slug): ?Tag;

    /**
     * Find tags by names (case-insensitive)
     *
     * @param array<int, string> $names
     * @return Collection<int, Tag>
     */
    public function findByNames(array $names): Collection;

    /**
     * Search tags by name (partial match)
     *
     * @param string $search
     * @return Collection<int, Tag>
     */
    public function searchByName(string $search): Collection;
}
