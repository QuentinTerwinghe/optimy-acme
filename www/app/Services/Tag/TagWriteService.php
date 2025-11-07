<?php

declare(strict_types=1);

namespace App\Services\Tag;

use App\Contracts\Tag\TagWriteServiceInterface;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Tag Write Service
 *
 * Handles all write operations for tags (create, update, delete)
 * Follows Single Responsibility Principle - only writes
 */
class TagWriteService implements TagWriteServiceInterface
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
    public function findOrCreateTags(array $tagNames): Collection
    {
        try {
            DB::beginTransaction();

            $tags = new Collection();

            // Normalize tag names (trim and lowercase for comparison)
            $normalizedNames = array_map(fn($name) => trim($name), $tagNames);
            $normalizedNames = array_filter($normalizedNames); // Remove empty strings
            $normalizedNames = array_unique($normalizedNames); // Remove duplicates

            foreach ($normalizedNames as $tagName) {
                // Try to find existing tag (case-insensitive)
                $tag = Tag::query()
                    ->whereRaw('LOWER(name) = ?', [strtolower($tagName)])
                    ->first();

                // If tag doesn't exist, create it
                if (!$tag) {
                    $tag = Tag::create([
                        'name' => $tagName,
                        'slug' => $this->generateUniqueSlug($tagName),
                    ]);

                    Log::info('Tag created automatically', [
                        'tag_id' => $tag->id,
                        'name' => $tag->name,
                    ]);
                }

                $tags->push($tag);
            }

            DB::commit();

            return $tags;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to find or create tags', [
                'error' => $e->getMessage(),
                'tag_names' => $tagNames,
            ]);

            throw $e;
        }
    }

    /**
     * Create a new tag
     *
     * @param array<string, mixed> $data
     * @return Tag
     * @throws \Exception
     */
    public function createTag(array $data): Tag
    {
        try {
            DB::beginTransaction();

            // Generate slug if not provided
            if (!isset($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name']);
            }

            $tag = Tag::create($data);

            DB::commit();

            Log::info('Tag created successfully', [
                'tag_id' => $tag->id,
                'name' => $tag->name,
            ]);

            return $tag;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create tag', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e;
        }
    }

    /**
     * Update an existing tag
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return Tag
     * @throws \Exception
     */
    public function updateTag(int $id, array $data): Tag
    {
        try {
            DB::beginTransaction();

            $tag = Tag::findOrFail($id);
            $tag->update($data);

            DB::commit();

            Log::info('Tag updated successfully', [
                'tag_id' => $tag->id,
                'name' => $tag->name,
            ]);

            return $tag;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update tag', [
                'id' => $id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e;
        }
    }

    /**
     * Delete a tag
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteTag(int $id): bool
    {
        try {
            DB::beginTransaction();

            $tag = Tag::findOrFail($id);
            $deleted = (bool) $tag->delete();

            DB::commit();

            Log::info('Tag deleted successfully', [
                'tag_id' => $id,
            ]);

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete tag', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate a unique slug for a tag
     *
     * @param string $name
     * @return string
     */
    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        // Keep trying until we find a unique slug
        while (Tag::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
