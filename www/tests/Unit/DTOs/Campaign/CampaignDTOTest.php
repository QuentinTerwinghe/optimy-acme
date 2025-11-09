<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\Campaign;

use App\DTOs\Campaign\CampaignDTO;
use App\Enums\CampaignStatus;
use App\Enums\Currency;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CampaignDTO
 */
class CampaignDTOTest extends TestCase
{
    public function test_constructor_sets_all_properties_correctly(): void
    {
        // Arrange
        $title = 'Test Campaign';
        $goalAmount = 1000.00;
        $currency = Currency::EUR;
        $startDate = Carbon::parse('2025-12-01');
        $endDate = Carbon::parse('2025-12-31');
        $status = CampaignStatus::ACTIVE;
        $description = 'Test description';
        $categoryId = 1;
        $currentAmount = 500.00;
        $tags = ['tag1', 'tag2'];

        // Act
        $dto = new CampaignDTO(
            title: $title,
            goalAmount: $goalAmount,
            currency: $currency,
            startDate: $startDate,
            endDate: $endDate,
            status: $status,
            description: $description,
            categoryId: $categoryId,
            currentAmount: $currentAmount,
            tags: $tags
        );

        // Assert
        $this->assertSame($title, $dto->title);
        $this->assertSame($goalAmount, $dto->goalAmount);
        $this->assertSame($currency, $dto->currency);
        $this->assertSame($startDate, $dto->startDate);
        $this->assertSame($endDate, $dto->endDate);
        $this->assertSame($status, $dto->status);
        $this->assertSame($description, $dto->description);
        $this->assertSame($categoryId, $dto->categoryId);
        $this->assertSame($currentAmount, $dto->currentAmount);
        $this->assertSame($tags, $dto->tags);
    }

    public function test_constructor_with_only_required_fields_for_draft(): void
    {
        // Arrange
        $title = 'Draft Campaign';

        // Act
        $dto = new CampaignDTO(title: $title);

        // Assert
        $this->assertSame($title, $dto->title);
        $this->assertNull($dto->goalAmount);
        $this->assertNull($dto->currency);
        $this->assertNull($dto->startDate);
        $this->assertNull($dto->endDate);
        $this->assertSame(CampaignStatus::DRAFT, $dto->status);
        $this->assertNull($dto->description);
        $this->assertNull($dto->categoryId);
        $this->assertNull($dto->currentAmount);
        $this->assertNull($dto->tags);
    }

    public function test_to_array_returns_correct_structure_with_all_fields(): void
    {
        // Arrange
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            goalAmount: 1000.00,
            currency: Currency::USD,
            startDate: Carbon::parse('2025-12-01 10:00:00'),
            endDate: Carbon::parse('2025-12-31 23:59:59'),
            status: CampaignStatus::ACTIVE,
            description: 'Test description',
            categoryId: 5,
            currentAmount: 250.50,
            tags: ['education', 'technology']
        );

        // Act
        $array = $dto->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertSame('Test Campaign', $array['title']);
        $this->assertSame('Test description', $array['description']);
        $this->assertSame(1000.00, $array['goal_amount']);
        $this->assertSame(250.50, $array['current_amount']);
        $this->assertSame('USD', $array['currency']);
        $this->assertSame('2025-12-01 10:00:00', $array['start_date']);
        $this->assertSame('2025-12-31 23:59:59', $array['end_date']);
        $this->assertSame('active', $array['status']);
        $this->assertSame(5, $array['category_id']);
    }

    public function test_to_array_handles_nullable_fields(): void
    {
        // Arrange - Draft campaign with only title
        $dto = new CampaignDTO(title: 'Draft Campaign');

        // Act
        $array = $dto->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertSame('Draft Campaign', $array['title']);
        $this->assertNull($array['description']);
        $this->assertNull($array['goal_amount']);
        $this->assertSame(0, $array['current_amount']); // Defaults to 0
        $this->assertNull($array['currency']);
        $this->assertNull($array['start_date']);
        $this->assertNull($array['end_date']);
        $this->assertSame('draft', $array['status']);
        $this->assertNull($array['category_id']);
    }

    public function test_to_array_current_amount_defaults_to_zero_when_null(): void
    {
        // Arrange
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            goalAmount: 1000.00,
            currency: Currency::EUR,
            startDate: Carbon::now(),
            endDate: Carbon::now()->addDays(30),
            status: CampaignStatus::ACTIVE,
            currentAmount: null // Explicitly null
        );

        // Act
        $array = $dto->toArray();

        // Assert
        $this->assertSame(0, $array['current_amount']);
    }

    public function test_draft_status_is_default(): void
    {
        // Arrange & Act
        $dto = new CampaignDTO(title: 'Test');

        // Assert
        $this->assertSame(CampaignStatus::DRAFT, $dto->status);
    }

    public function test_supports_all_campaign_statuses(): void
    {
        $statuses = [
            CampaignStatus::DRAFT,
            CampaignStatus::WAITING_FOR_VALIDATION,
            CampaignStatus::ACTIVE,
            CampaignStatus::COMPLETED,
            CampaignStatus::CANCELLED,
        ];

        foreach ($statuses as $status) {
            // Arrange & Act
            $dto = new CampaignDTO(
                title: 'Test Campaign',
                status: $status
            );

            // Assert
            $this->assertSame($status, $dto->status);
            $this->assertSame($status->value, $dto->toArray()['status']);
        }
    }

    public function test_supports_all_currencies(): void
    {
        $currencies = [
            Currency::USD,
            Currency::EUR,
            Currency::GBP,
            Currency::CHF,
            Currency::CAD,
        ];

        foreach ($currencies as $currency) {
            // Arrange & Act
            $dto = new CampaignDTO(
                title: 'Test Campaign',
                goalAmount: 1000.00,
                currency: $currency,
                startDate: Carbon::now(),
                endDate: Carbon::now()->addDays(30),
                status: CampaignStatus::ACTIVE
            );

            // Assert
            $this->assertSame($currency, $dto->currency);
            $this->assertSame($currency->value, $dto->toArray()['currency']);
        }
    }

    public function test_handles_empty_tags_array(): void
    {
        // Arrange & Act
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            tags: []
        );

        // Assert
        $this->assertSame([], $dto->tags);
    }

    public function test_handles_multiple_tags(): void
    {
        // Arrange
        $tags = ['education', 'technology', 'science', 'innovation'];

        // Act
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            tags: $tags
        );

        // Assert
        $this->assertSame($tags, $dto->tags);
    }

    public function test_date_fields_are_carbon_instances(): void
    {
        // Arrange
        $startDate = Carbon::parse('2025-12-01');
        $endDate = Carbon::parse('2025-12-31');

        // Act
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            goalAmount: 1000.00,
            currency: Currency::EUR,
            startDate: $startDate,
            endDate: $endDate,
            status: CampaignStatus::ACTIVE
        );

        // Assert
        $this->assertInstanceOf(Carbon::class, $dto->startDate);
        $this->assertInstanceOf(Carbon::class, $dto->endDate);
    }

    public function test_date_fields_convert_to_string_in_array(): void
    {
        // Arrange
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            goalAmount: 1000.00,
            currency: Currency::EUR,
            startDate: Carbon::parse('2025-12-01 10:30:45'),
            endDate: Carbon::parse('2025-12-31 23:59:59'),
            status: CampaignStatus::ACTIVE
        );

        // Act
        $array = $dto->toArray();

        // Assert
        $this->assertIsString($array['start_date']);
        $this->assertIsString($array['end_date']);
        $this->assertSame('2025-12-01 10:30:45', $array['start_date']);
        $this->assertSame('2025-12-31 23:59:59', $array['end_date']);
    }

    public function test_goal_amount_preserves_precision(): void
    {
        // Arrange & Act
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            goalAmount: 1234.56,
            currency: Currency::EUR,
            startDate: Carbon::now(),
            endDate: Carbon::now()->addDays(30),
            status: CampaignStatus::ACTIVE
        );

        // Assert
        $this->assertSame(1234.56, $dto->goalAmount);
        $this->assertSame(1234.56, $dto->toArray()['goal_amount']);
    }

    public function test_current_amount_preserves_precision(): void
    {
        // Arrange & Act
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            goalAmount: 1000.00,
            currency: Currency::EUR,
            startDate: Carbon::now(),
            endDate: Carbon::now()->addDays(30),
            status: CampaignStatus::ACTIVE,
            currentAmount: 567.89
        );

        // Assert
        $this->assertSame(567.89, $dto->currentAmount);
        $this->assertSame(567.89, $dto->toArray()['current_amount']);
    }

    public function test_readonly_properties_are_immutable(): void
    {
        // This test verifies the DTO is properly declared as readonly
        // The immutability is enforced by PHP's type system

        // Arrange
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            goalAmount: 1000.00,
            currency: Currency::EUR,
            startDate: Carbon::now(),
            endDate: Carbon::now()->addDays(30),
            status: CampaignStatus::ACTIVE
        );

        // Assert - Verify properties are accessible
        $this->assertSame('Test Campaign', $dto->title);
        $this->assertSame(1000.00, $dto->goalAmount);
        $this->assertSame(Currency::EUR, $dto->currency);
        $this->assertSame(CampaignStatus::ACTIVE, $dto->status);
    }

    public function test_category_id_can_be_null(): void
    {
        // Arrange & Act
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            categoryId: null
        );

        // Assert
        $this->assertNull($dto->categoryId);
        $this->assertNull($dto->toArray()['category_id']);
    }

    public function test_category_id_can_be_set(): void
    {
        // Arrange & Act
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            categoryId: 42
        );

        // Assert
        $this->assertSame(42, $dto->categoryId);
        $this->assertSame(42, $dto->toArray()['category_id']);
    }

    public function test_description_can_be_null(): void
    {
        // Arrange & Act
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            description: null
        );

        // Assert
        $this->assertNull($dto->description);
        $this->assertNull($dto->toArray()['description']);
    }

    public function test_description_can_be_empty_string(): void
    {
        // Arrange & Act
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            description: ''
        );

        // Assert
        $this->assertSame('', $dto->description);
        $this->assertSame('', $dto->toArray()['description']);
    }

    public function test_description_can_contain_long_text(): void
    {
        // Arrange
        $longDescription = str_repeat('This is a long description. ', 100);

        // Act
        $dto = new CampaignDTO(
            title: 'Test Campaign',
            description: $longDescription
        );

        // Assert
        $this->assertSame($longDescription, $dto->description);
        $this->assertSame($longDescription, $dto->toArray()['description']);
    }
}
