<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\NotificationType;

/**
 * Data Transfer Object for notification payloads.
 * Useful for serialization/deserialization when working with queues like RabbitMQ.
 */
final readonly class NotificationPayload
{
    /**
     * @param int $userId User ID who will receive the notification
     * @param NotificationType $type Type of notification to send
     * @param array<string, mixed> $parameters Parameters for the notification
     */
    public function __construct(
        public int $userId,
        public NotificationType $type,
        public array $parameters
    ) {
    }

    /**
     * Create from array (useful for deserialization from queue).
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            type: NotificationType::from($data['type']),
            parameters: $data['parameters'] ?? []
        );
    }

    /**
     * Convert to array (useful for serialization to queue).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'type' => $this->type->value,
            'parameters' => $this->parameters,
        ];
    }

    /**
     * Convert to JSON string.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Create from JSON string.
     *
     * @param string $json
     * @return self
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($data);
    }
}
