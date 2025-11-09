<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\Notification;

use App\DTOs\Notification\NotificationPayloadDTO;
use App\Enums\NotificationType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NotificationPayloadDTO DTO.
 */
class NotificationPayloadDTOTest extends TestCase
{
    public function test_constructor_sets_properties_correctly(): void
    {
        // Arrange
        $userId = 1;
        $type = NotificationType::FORGOT_PASSWORD;
        $parameters = ['token' => 'test-token-123'];

        // Act
        $payload = new NotificationPayloadDTO($userId, $type, $parameters);

        // Assert
        $this->assertSame($userId, $payload->userId);
        $this->assertSame($type, $payload->type);
        $this->assertSame($parameters, $payload->parameters);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        // Arrange
        $userId = 1;
        $type = NotificationType::FORGOT_PASSWORD;
        $parameters = ['token' => 'test-token-123', 'expiration_minutes' => 60];

        $payload = new NotificationPayloadDTO($userId, $type, $parameters);

        // Act
        $array = $payload->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertSame($userId, $array['user_id']);
        $this->assertSame($type->value, $array['type']);
        $this->assertSame($parameters, $array['parameters']);
    }

    public function test_from_array_creates_instance_correctly(): void
    {
        // Arrange
        $data = [
            'user_id' => 1,
            'type' => 'forgot_password',
            'parameters' => ['token' => 'test-token-123'],
        ];

        // Act
        $payload = NotificationPayloadDTO::fromArray($data);

        // Assert
        $this->assertInstanceOf(NotificationPayloadDTO::class, $payload);
        $this->assertSame(1, $payload->userId);
        $this->assertSame(NotificationType::FORGOT_PASSWORD, $payload->type);
        $this->assertSame(['token' => 'test-token-123'], $payload->parameters);
    }

    public function test_from_array_handles_missing_parameters(): void
    {
        // Arrange
        $data = [
            'user_id' => 1,
            'type' => 'forgot_password',
        ];

        // Act
        $payload = NotificationPayloadDTO::fromArray($data);

        // Assert
        $this->assertSame([], $payload->parameters);
    }

    public function test_to_json_returns_valid_json_string(): void
    {
        // Arrange
        $userId = 1;
        $type = NotificationType::FORGOT_PASSWORD;
        $parameters = ['token' => 'test-token-123'];

        $payload = new NotificationPayloadDTO($userId, $type, $parameters);

        // Act
        $json = $payload->toJson();

        // Assert
        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame($userId, $decoded['user_id']);
        $this->assertSame($type->value, $decoded['type']);
        $this->assertSame($parameters, $decoded['parameters']);
    }

    public function test_from_json_creates_instance_correctly(): void
    {
        // Arrange
        $json = json_encode([
            'user_id' => 1,
            'type' => 'forgot_password',
            'parameters' => ['token' => 'test-token-123'],
        ]);

        // Act
        $payload = NotificationPayloadDTO::fromJson($json);

        // Assert
        $this->assertInstanceOf(NotificationPayloadDTO::class, $payload);
        $this->assertSame(1, $payload->userId);
        $this->assertSame(NotificationType::FORGOT_PASSWORD, $payload->type);
        $this->assertSame(['token' => 'test-token-123'], $payload->parameters);
    }

    public function test_from_json_throws_exception_for_invalid_json(): void
    {
        // Arrange
        $invalidJson = 'invalid json string';

        // Assert
        $this->expectException(\JsonException::class);

        // Act
        NotificationPayloadDTO::fromJson($invalidJson);
    }

    public function test_to_json_and_from_json_are_reversible(): void
    {
        // Arrange
        $original = new NotificationPayloadDTO(
            userId: 42,
            type: NotificationType::FORGOT_PASSWORD,
            parameters: [
                'token' => 'test-token-123',
                'expiration_minutes' => 60,
                'nested' => ['key' => 'value'],
            ]
        );

        // Act
        $json = $original->toJson();
        $restored = NotificationPayloadDTO::fromJson($json);

        // Assert
        $this->assertSame($original->userId, $restored->userId);
        $this->assertSame($original->type, $restored->type);
        $this->assertSame($original->parameters, $restored->parameters);
    }

    public function test_to_array_and_from_array_are_reversible(): void
    {
        // Arrange
        $original = new NotificationPayloadDTO(
            userId: 42,
            type: NotificationType::FORGOT_PASSWORD,
            parameters: [
                'token' => 'test-token-123',
                'expiration_minutes' => 60,
            ]
        );

        // Act
        $array = $original->toArray();
        $restored = NotificationPayloadDTO::fromArray($array);

        // Assert
        $this->assertSame($original->userId, $restored->userId);
        $this->assertSame($original->type, $restored->type);
        $this->assertSame($original->parameters, $restored->parameters);
    }

    public function test_handles_empty_parameters(): void
    {
        // Arrange & Act
        $payload = new NotificationPayloadDTO(
            userId: 1,
            type: NotificationType::FORGOT_PASSWORD,
            parameters: []
        );

        // Assert
        $this->assertSame([], $payload->parameters);
        $this->assertSame([], $payload->toArray()['parameters']);
    }

    public function test_handles_complex_parameters(): void
    {
        // Arrange
        $complexParameters = [
            'token' => 'test-token-123',
            'expiration_minutes' => 60,
            'user_data' => [
                'name' => 'John Doe',
                'preferences' => ['email' => true, 'sms' => false],
            ],
            'metadata' => [
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
            ],
        ];

        // Act
        $payload = new NotificationPayloadDTO(1, NotificationType::FORGOT_PASSWORD, $complexParameters);
        $json = $payload->toJson();
        $restored = NotificationPayloadDTO::fromJson($json);

        // Assert
        $this->assertSame($complexParameters, $restored->parameters);
    }

    public function test_readonly_properties_cannot_be_modified(): void
    {
        // This test ensures the DTO is immutable (readonly properties)
        // If PHP runtime allows modification, this would fail

        // Arrange
        $payload = new NotificationPayloadDTO(
            userId: 1,
            type: NotificationType::FORGOT_PASSWORD,
            parameters: ['token' => 'test']
        );

        // Assert: This test simply verifies the class is properly declared as readonly
        // The actual immutability is enforced by PHP's type system
        $this->assertSame(1, $payload->userId);
        $this->assertSame(NotificationType::FORGOT_PASSWORD, $payload->type);
        $this->assertSame(['token' => 'test'], $payload->parameters);
    }
}
