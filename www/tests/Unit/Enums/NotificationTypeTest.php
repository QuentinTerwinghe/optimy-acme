<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Notification\NotificationType;
use Tests\TestCase;

class NotificationTypeTest extends TestCase
{
    public function test_notification_type_has_forgot_password_case(): void
    {
        $this->assertEquals('forgot_password', NotificationType::FORGOT_PASSWORD->value);
    }

    public function test_notification_type_has_campaign_waiting_for_validation_case(): void
    {
        $this->assertEquals('campaign_waiting_for_validation', NotificationType::CAMPAIGN_WAITING_FOR_VALIDATION->value);
    }

    public function test_notification_type_has_campaign_validated_case(): void
    {
        $this->assertEquals('campaign_validated', NotificationType::CAMPAIGN_VALIDATED->value);
    }

    public function test_notification_type_has_campaign_rejected_case(): void
    {
        $this->assertEquals('campaign_rejected', NotificationType::CAMPAIGN_REJECTED->value);
    }

    public function test_notification_type_all_cases(): void
    {
        $cases = NotificationType::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(NotificationType::FORGOT_PASSWORD, $cases);
        $this->assertContains(NotificationType::CAMPAIGN_WAITING_FOR_VALIDATION, $cases);
        $this->assertContains(NotificationType::CAMPAIGN_VALIDATED, $cases);
        $this->assertContains(NotificationType::CAMPAIGN_REJECTED, $cases);
    }

    public function test_notification_type_can_be_compared(): void
    {
        $type1 = NotificationType::CAMPAIGN_VALIDATED;
        $type2 = NotificationType::CAMPAIGN_VALIDATED;
        $type3 = NotificationType::CAMPAIGN_REJECTED;

        $this->assertTrue($type1 === $type2);
        $this->assertFalse($type1 === $type3);
    }

    public function test_notification_type_can_be_created_from_value(): void
    {
        $type = NotificationType::from('campaign_validated');
        $this->assertEquals(NotificationType::CAMPAIGN_VALIDATED, $type);

        $type = NotificationType::from('campaign_rejected');
        $this->assertEquals(NotificationType::CAMPAIGN_REJECTED, $type);
    }

    public function test_notification_type_try_from_returns_null_for_invalid_value(): void
    {
        $type = NotificationType::tryFrom('invalid_type');
        $this->assertNull($type);
    }

    public function test_notification_type_from_throws_exception_for_invalid_value(): void
    {
        $this->expectException(\ValueError::class);
        NotificationType::from('invalid_type');
    }

    public function test_notification_type_values_are_strings(): void
    {
        foreach (NotificationType::cases() as $case) {
            $this->assertIsString($case->value);
        }
    }

    public function test_notification_type_values_use_snake_case(): void
    {
        foreach (NotificationType::cases() as $case) {
            // Check that values only contain lowercase letters and underscores
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $case->value);
        }
    }

    public function test_notification_type_campaign_related_cases(): void
    {
        $campaignTypes = [
            NotificationType::CAMPAIGN_WAITING_FOR_VALIDATION,
            NotificationType::CAMPAIGN_VALIDATED,
            NotificationType::CAMPAIGN_REJECTED,
        ];

        foreach ($campaignTypes as $type) {
            $this->assertStringStartsWith('campaign_', $type->value);
        }
    }
}
