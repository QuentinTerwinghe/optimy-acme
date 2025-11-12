<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Campaign\UpdateCampaignDTO;
use App\Enums\Campaign\CampaignStatus;
use App\Enums\Common\Currency;
use App\Models\Campaign\Campaign;
use App\Services\Campaign\CampaignStatusValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Campaign Status Validator Unit Tests
 *
 * Tests the status validation logic for campaigns
 */
class CampaignStatusValidatorTest extends TestCase
{
    use RefreshDatabase;

    private CampaignStatusValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new CampaignStatusValidator();
    }

    #[Test]
    public function it_allows_valid_transition_from_draft_to_waiting_with_all_fields(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);

        $dto = new UpdateCampaignDTO(
            title: 'Test',
            description: 'Test',
            status: CampaignStatus::WAITING_FOR_VALIDATION,
            goalAmount: 1000.0,
            currency: Currency::USD,
            startDate: now(),
            endDate: now()->addDays(30),
            categoryId: 1,
            tags: null
        );

        $this->validator->validateStatusTransition($campaign, CampaignStatus::WAITING_FOR_VALIDATION, $dto);

        // No exception thrown means validation passed
        $this->assertTrue(true);
    }

    #[Test]
    public function it_rejects_transition_from_draft_to_waiting_without_required_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot change status to waiting_for_validation without required fields');

        $campaign = Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);

        $dto = new UpdateCampaignDTO(
            title: 'Test',
            description: 'Test',
            status: CampaignStatus::WAITING_FOR_VALIDATION,
            goalAmount: null, // Missing required field
            currency: null,   // Missing required field
            startDate: null,  // Missing required field
            endDate: null,    // Missing required field
            categoryId: 1,
            tags: null
        );

        $this->validator->validateStatusTransition($campaign, CampaignStatus::WAITING_FOR_VALIDATION, $dto);
    }

    #[Test]
    public function it_allows_transition_to_active_when_fields_exist_in_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'goal_amount' => 1000.0,
            'currency' => 'USD',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $dto = new UpdateCampaignDTO(
            title: null,
            description: null,
            status: CampaignStatus::ACTIVE,
            goalAmount: null, // Can be null because already in campaign
            currency: null,
            startDate: null,
            endDate: null,
            categoryId: null,
            tags: null
        );

        $this->validator->validateStatusTransition($campaign, CampaignStatus::ACTIVE, $dto);

        // No exception thrown means validation passed
        $this->assertTrue(true);
    }

    #[Test]
    public function it_rejects_transition_to_active_when_fields_missing_in_both_dto_and_campaign(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot change status to active without required fields');

        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'goal_amount' => null, // Missing in campaign
            'currency' => null,
            'start_date' => null,
            'end_date' => null,
        ]);

        $dto = new UpdateCampaignDTO(
            title: null,
            description: null,
            status: CampaignStatus::ACTIVE,
            goalAmount: null, // Missing in DTO too
            currency: null,
            startDate: null,
            endDate: null,
            categoryId: null,
            tags: null
        );

        $this->validator->validateStatusTransition($campaign, CampaignStatus::ACTIVE, $dto);
    }

    #[Test]
    public function it_does_not_validate_when_status_is_not_changing(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);

        $dto = new UpdateCampaignDTO(
            title: 'Test',
            description: 'Test',
            status: CampaignStatus::DRAFT, // Same status
            goalAmount: null,
            currency: null,
            startDate: null,
            endDate: null,
            categoryId: 1,
            tags: null
        );

        $this->validator->validateStatusTransition($campaign, CampaignStatus::DRAFT, $dto);

        // No exception thrown means validation passed (skipped)
        $this->assertTrue(true);
    }

    #[Test]
    public function it_does_not_validate_transitions_to_other_statuses(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::ACTIVE]);

        $dto = new UpdateCampaignDTO(
            title: 'Test',
            description: 'Test',
            status: CampaignStatus::COMPLETED, // Not a validated status
            goalAmount: null,
            currency: null,
            startDate: null,
            endDate: null,
            categoryId: 1,
            tags: null
        );

        $this->validator->validateStatusTransition($campaign, CampaignStatus::COMPLETED, $dto);

        // No exception thrown means validation passed (skipped)
        $this->assertTrue(true);
    }

    #[Test]
    public function it_returns_required_fields_for_validated_statuses(): void
    {
        $fields = $this->validator->getRequiredFieldsForStatus(CampaignStatus::ACTIVE);

        $this->assertArrayHasKey('goal_amount', $fields);
        $this->assertArrayHasKey('currency', $fields);
        $this->assertArrayHasKey('start_date', $fields);
        $this->assertArrayHasKey('end_date', $fields);
    }

    #[Test]
    public function it_returns_empty_array_for_non_validated_statuses(): void
    {
        $fields = $this->validator->getRequiredFieldsForStatus(CampaignStatus::DRAFT);

        $this->assertEmpty($fields);
    }

    #[Test]
    public function it_correctly_identifies_statuses_requiring_validation(): void
    {
        $this->assertTrue($this->validator->requiresValidation(CampaignStatus::WAITING_FOR_VALIDATION));
        $this->assertTrue($this->validator->requiresValidation(CampaignStatus::ACTIVE));
        $this->assertFalse($this->validator->requiresValidation(CampaignStatus::DRAFT));
        $this->assertFalse($this->validator->requiresValidation(CampaignStatus::COMPLETED));
        $this->assertFalse($this->validator->requiresValidation(null));
    }
}
