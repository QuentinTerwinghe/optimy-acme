<?php

declare(strict_types=1);

namespace App\Contracts\Tag;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tag Write Service Interface
 *
 * Defines the contract for tag write operations
 * Follows Single Responsibility Principle - handles only writes
 */
interface TagWriteServiceInterface
{
    /**
     * Find existing tags or create new ones from array of tag names
     *
     * This method will:
     * - Find existing tags by name (case-insensitive)
     * - Create new tags for names that don't exist
     * - Return collection of all tags (existing + newly created)
     *
     * @param array<int, string> $tagNames
     * @return Collection<int, Tag>
     */
    public function findOrCreateTags(array $tagNames): Collection;

    /**
     * Create a new tag
     *
     * @param array<string, mixed> $data
     * @return Tag
     * @throws \Exception
     */
    public function createTag(array $data): Tag;

    /**
     * Update an existing tag
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return Tag
     * @throws \Exception
     */
    public function updateTag(int $id, array $data): Tag;

    /**
     * Delete a tag
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteTag(int $id): bool;
}
