<?php

declare(strict_types=1);

namespace App\DTOs\Campaign;

use Illuminate\Http\Request;

/**
 * Campaign Filter DTO
 *
 * Data Transfer Object for campaign filtering parameters
 * Follows Single Responsibility Principle - only handles filter data structure
 */
readonly class CampaignFilterDTO
{
    /**
     * Create a new CampaignFilterDTO instance
     *
     * @param string|null $search Search term for campaign title
     * @param int|null $categoryId Category ID to filter by
     * @param array<int, int> $tagIds Tag IDs to filter by
     */
    public function __construct(
        public ?string $search = null,
        public ?int $categoryId = null,
        public array $tagIds = []
    ) {}

    /**
     * Create DTO from HTTP request
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        $search = $request->has('search') && !empty($request->input('search'))
            ? (string) $request->input('search')
            : null;

        $categoryId = $request->has('category_id') && !empty($request->input('category_id'))
            ? (int) $request->input('category_id')
            : null;

        $tagIds = [];
        if ($request->has('tag_ids') && !empty($request->input('tag_ids'))) {
            $rawTagIds = $request->input('tag_ids');

            // Handle both array and comma-separated string
            if (is_string($rawTagIds)) {
                $rawTagIds = explode(',', $rawTagIds);
            }

            if (is_array($rawTagIds)) {
                $tagIds = array_map('intval', array_filter($rawTagIds));
            }
        }

        return new self(
            search: $search,
            categoryId: $categoryId,
            tagIds: $tagIds
        );
    }

    /**
     * Convert to array format expected by service layer
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $filters = [];

        if ($this->search !== null) {
            $filters['search'] = $this->search;
        }

        if ($this->categoryId !== null) {
            $filters['category_id'] = $this->categoryId;
        }

        if (!empty($this->tagIds)) {
            $filters['tag_ids'] = $this->tagIds;
        }

        return $filters;
    }

    /**
     * Check if any filters are set
     *
     * @return bool
     */
    public function hasFilters(): bool
    {
        return $this->search !== null
            || $this->categoryId !== null
            || !empty($this->tagIds);
    }
}
