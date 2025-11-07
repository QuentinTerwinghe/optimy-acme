<?php

declare(strict_types=1);

namespace App\Contracts\Category;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

/**
 * Category Query Service Interface
 *
 * Defines the contract for category read operations
 * Follows Single Responsibility Principle - handles only queries/reads
 */
interface CategoryQueryServiceInterface
{
    /**
     * Get all categories
     *
     * @return Collection<int, Category>
     */
    public function getAllCategories(): Collection;

    /**
     * Get all active categories
     *
     * @return Collection<int, Category>
     */
    public function getActiveCategories(): Collection;

    /**
     * Find a category by ID
     *
     * @param int $id
     * @return Category|null
     */
    public function findById(int $id): ?Category;

    /**
     * Find a category by slug
     *
     * @param string $slug
     * @return Category|null
     */
    public function findBySlug(string $slug): ?Category;
}
