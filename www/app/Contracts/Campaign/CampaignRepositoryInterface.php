<?php

declare(strict_types=1);

namespace App\Contracts\Campaign;

/**
 * Campaign Repository Interface
 *
 * Defines the contract for campaign data access operations
 * Follows Dependency Inversion Principle - services depend on this abstraction
 * Follows Interface Segregation Principle - extends focused interfaces
 *
 * This interface combines all repository capabilities for backward compatibility
 * Clients can depend on specific interfaces (CampaignReadRepositoryInterface, etc.) instead
 */
interface CampaignRepositoryInterface extends
    CampaignReadRepositoryInterface,
    CampaignWriteRepositoryInterface,
    CampaignAggregateRepositoryInterface
{
    // This interface intentionally left empty
    // All methods are inherited from the focused interfaces above
    // This provides backward compatibility while allowing clients to depend on smaller interfaces
}
