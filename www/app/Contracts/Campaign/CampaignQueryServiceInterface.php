<?php

declare(strict_types=1);

namespace App\Contracts\Campaign;

/**
 * Campaign Query Service Interface
 *
 * Defines the contract for campaign read operations
 * Follows Single Responsibility Principle - handles only queries/reads
 * Follows Interface Segregation Principle - extends focused interfaces
 *
 * This interface combines all query capabilities for backward compatibility
 * Clients can depend on specific interfaces (CampaignFinderInterface, etc.) instead
 */
interface CampaignQueryServiceInterface extends
    CampaignFinderInterface,
    CampaignFilterInterface,
    CampaignStatisticsInterface
{
    // This interface intentionally left empty
    // All methods are inherited from the focused interfaces above
    // This provides backward compatibility while allowing clients to depend on smaller interfaces
}
