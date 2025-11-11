<?php

declare(strict_types=1);

namespace App\Contracts\Campaign;

use App\Models\Campaign\Campaign;

/**
 * Campaign Finder Interface
 *
 * Defines the contract for finding individual campaigns
 * Follows Interface Segregation Principle - focused on finding operations
 */
interface CampaignFinderInterface
{
    /**
     * Find a campaign by ID
     *
     * @param string $id
     * @return Campaign|null
     */
    public function findById(string $id): ?Campaign;

    /**
     * Find a campaign by ID with relationships loaded
     *
     * @param string $id
     * @param array<int, string> $relations
     * @return Campaign|null
     */
    public function findByIdWithRelations(string $id, array $relations = ['category', 'tags', 'creator']): ?Campaign;
}
