# Notification Service

A flexible, SOLID-compliant notification service that uses Strategy and Registry patterns to handle different types of notifications.

## Architecture

The notification system follows these SOLID principles:

- **Single Responsibility**: Each handler is responsible for one notification type
- **Open/Closed**: New notification types can be added without modifying existing code
- **Liskov Substitution**: All handlers implement the same interface
- **Interface Segregation**: Focused interfaces for handlers, registry, and service
- **Dependency Inversion**: Depends on abstractions (interfaces) not concretions

## Directory Structure

```
app/
├── Contracts/Notifications/
│   ├── NotificationHandlerInterface.php       # Interface for notification handlers
│   ├── NotificationRegistryInterface.php      # Interface for handler registry
│   └── NotificationServiceInterface.php       # Interface for main service
├── Services/Notifications/
│   ├── AbstractNotificationHandler.php        # Base class for handlers
│   ├── NotificationRegistry.php               # Handler registry implementation
│   ├── NotificationService.php                # Main service implementation
│   └── Handlers/                              # Concrete handler implementations
└── Enums/
    └── NotificationType.php                   # Enum for notification types
```

## How It Works

### 1. Strategy Pattern

Each notification type has its own handler that implements `NotificationHandlerInterface`. This allows:
- Easy addition of new notification types
- Isolation of notification logic
- Independent testing of each notification type

### 2. Registry Pattern

The `NotificationRegistry` maps notification types to their handlers:
- Centralizes handler management
- Provides validation (throws exception if handler not found)
- Allows runtime handler registration

### 3. Service Facade

The `NotificationService` provides a simple API:
- Clients don't need to know about handlers or registry
- Handles error logging and exception handling
- Provides consistent interface for all notification types

## Usage

### Step 1: Define Notification Types

Add your notification types to the enum:

```php
// app/Enums/NotificationType.php
enum NotificationType: string
{
    case INCENTIVE_CONFIRMATION = 'incentive_confirmation';
    case WELCOME_EMAIL = 'welcome_email';
    case PASSWORD_RESET = 'password_reset';
}
```

### Step 2: Create a Notification Handler

Create a handler for your notification type:

```php
// app/Services/Notifications/Handlers/IncentiveConfirmationHandler.php
<?php

declare(strict_types=1);

namespace App\Services\Notifications\Handlers;

use App\Enums\NotificationType;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationHandler;
use Illuminate\Support\Facades\Mail;

class IncentiveConfirmationHandler extends AbstractNotificationHandler
{
    public function getNotificationType(): NotificationType
    {
        return NotificationType::INCENTIVE_CONFIRMATION;
    }

    protected function validate(User $receiver, array $parameters): void
    {
        parent::validate($receiver, $parameters);

        // Validate required parameters
        if (!isset($parameters['incentive_name'])) {
            throw new \InvalidArgumentException('Missing parameter: incentive_name');
        }

        if (!isset($parameters['participation_date'])) {
            throw new \InvalidArgumentException('Missing parameter: participation_date');
        }
    }

    protected function send(User $receiver, array $parameters): bool
    {
        try {
            // Send email using Laravel Mail
            Mail::to($receiver->email)->send(
                new IncentiveConfirmationMail(
                    $receiver,
                    $parameters['incentive_name'],
                    $parameters['participation_date']
                )
            );

            return true;
        } catch (\Exception $e) {
            // Log error (handled by parent class)
            return false;
        }
    }
}
```

### Step 3: Register the Handler

Register your handler in `NotificationServiceProvider`:

```php
// app/Providers/NotificationServiceProvider.php
public function boot(): void
{
    /** @var NotificationRegistryInterface $registry */
    $registry = $this->app->make(NotificationRegistryInterface::class);

    // Register handlers
    $registry->register($this->app->make(IncentiveConfirmationHandler::class));
    $registry->register($this->app->make(WelcomeEmailHandler::class));
    $registry->register($this->app->make(PasswordResetHandler::class));
}
```

### Step 4: Send Notifications

Use the service to send notifications:

```php
use App\Contracts\Notifications\NotificationServiceInterface;
use App\Enums\NotificationType;
use App\Models\User;

class IncentiveController extends Controller
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {}

    public function participate(Request $request)
    {
        $user = auth()->user();

        // Your business logic...

        // Send notification
        $this->notificationService->send(
            $user,
            NotificationType::INCENTIVE_CONFIRMATION,
            [
                'incentive_name' => 'Summer Campaign 2025',
                'participation_date' => now()->toDateString(),
                'reward_amount' => 100,
            ]
        );

        return response()->json(['message' => 'Participation confirmed']);
    }
}
```

## Integration with RabbitMQ

The service is designed to work seamlessly with RabbitMQ for asynchronous notification processing.

### Example Job Implementation

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Notifications\NotificationServiceInterface;
use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param int $userId
     * @param NotificationType $type
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        private readonly int $userId,
        private readonly NotificationType $type,
        private readonly array $parameters
    ) {}

    public function handle(NotificationServiceInterface $notificationService): void
    {
        try {
            $user = User::findOrFail($this->userId);

            $notificationService->send(
                $user,
                $this->type,
                $this->parameters
            );
        } catch (\Exception $e) {
            Log::error('Failed to process notification job', [
                'user_id' => $this->userId,
                'type' => $this->type->value,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger job failure
        }
    }
}
```

### Dispatching the Job

```php
use App\Jobs\SendNotificationJob;
use App\Enums\NotificationType;

// Dispatch to RabbitMQ
SendNotificationJob::dispatch(
    $user->id,
    NotificationType::INCENTIVE_CONFIRMATION,
    [
        'incentive_name' => 'Summer Campaign 2025',
        'participation_date' => now()->toDateString(),
    ]
);
```

### RabbitMQ Configuration

Configure your queue connection in `.env`:

```env
QUEUE_CONNECTION=rabbitmq

RABBITMQ_HOST=localhost
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_QUEUE=notifications
```

## Error Handling

The service includes comprehensive error handling:

1. **Missing Handler**: Throws `InvalidArgumentException` if no handler registered for type
2. **Validation Errors**: Handlers can throw `InvalidArgumentException` for invalid parameters
3. **Send Failures**: Exceptions during sending are caught, logged, and re-thrown
4. **Logging**: All successes, failures, and errors are logged automatically

## Testing

### Unit Test Example

```php
<?php

namespace Tests\Unit\Services\Notifications;

use App\Contracts\Notifications\NotificationRegistryInterface;
use App\Enums\NotificationType;
use App\Models\User;
use App\Services\Notifications\Handlers\IncentiveConfirmationHandler;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_notification_successfully(): void
    {
        // Arrange
        $user = User::factory()->create();
        $registry = app(NotificationRegistryInterface::class);
        $registry->register(app(IncentiveConfirmationHandler::class));
        $service = new NotificationService($registry);

        // Act
        $result = $service->send(
            $user,
            NotificationType::INCENTIVE_CONFIRMATION,
            ['incentive_name' => 'Test', 'participation_date' => '2025-01-01']
        );

        // Assert
        $this->assertTrue($result);
    }

    public function test_throws_exception_for_unregistered_type(): void
    {
        // Arrange
        $user = User::factory()->create();
        $registry = app(NotificationRegistryInterface::class);
        $service = new NotificationService($registry);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $service->send($user, NotificationType::INCENTIVE_CONFIRMATION, []);
    }
}
```

## Best Practices

1. **Always validate parameters** in your handler's `validate()` method
2. **Use type hints** for all parameters and return types
3. **Log appropriately** using the built-in logging methods
4. **Handle exceptions** gracefully in the `send()` method
5. **Keep handlers focused** - one handler per notification type
6. **Use dependency injection** for any services your handler needs
7. **Write tests** for each handler implementation

## Extending the System

### Adding Custom Validation

Override the `validate()` method:

```php
protected function validate(User $receiver, array $parameters): void
{
    parent::validate($receiver, $parameters);

    if (!isset($parameters['custom_field'])) {
        throw new \InvalidArgumentException('Missing parameter: custom_field');
    }

    if ($parameters['custom_field'] < 0) {
        throw new \InvalidArgumentException('custom_field must be positive');
    }
}
```

### Custom Logging

Override logging methods for custom behavior:

```php
protected function logSuccess(User $receiver, array $parameters): void
{
    parent::logSuccess($receiver, $parameters);

    // Additional custom logging
    event(new NotificationSentEvent($receiver, $this->getNotificationType()));
}
```

### Using Constructor Injection

Handlers support dependency injection:

```php
class MyHandler extends AbstractNotificationHandler
{
    public function __construct(
        private readonly SomeService $someService,
        private readonly AnotherService $anotherService
    ) {}

    protected function send(User $receiver, array $parameters): bool
    {
        // Use injected services
        $this->someService->doSomething();
        return true;
    }
}
```

## Troubleshooting

### Handler Not Found

If you get "No handler registered for notification type" error:
1. Check that you added the handler to `NotificationServiceProvider::boot()`
2. Ensure the handler's `getNotificationType()` returns the correct enum value
3. Clear the application cache: `make cc`

### Validation Errors

If you get validation errors:
1. Check that all required parameters are passed
2. Verify parameter names match what the handler expects
3. Check the handler's `validate()` method for requirements

### Notifications Not Sending

If notifications aren't being sent:
1. Check logs in `storage/logs/laravel.log`
2. Verify email/SMS configuration
3. Test the underlying service (Mail, SMS gateway, etc.) independently
4. Check that the handler's `send()` method returns true on success

## Performance Considerations

1. **Use queues** for all notifications to avoid blocking requests
2. **Batch notifications** when sending to multiple users
3. **Implement retries** for failed notifications using Laravel's job retry mechanism
4. **Monitor queue depth** to ensure RabbitMQ is processing efficiently
5. **Cache handlers** (already done via singleton registry)

## Security Considerations

1. **Validate all user input** before passing to handlers
2. **Sanitize parameters** to prevent injection attacks
3. **Use rate limiting** to prevent notification spam
4. **Implement authorization** to ensure users can only trigger notifications they're allowed to
5. **Log all notification attempts** for audit purposes (already implemented)
