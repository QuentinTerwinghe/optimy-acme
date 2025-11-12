# Forgot Password Notification Handler

This handler sends password reset emails with token links to users who have requested a password reset.

## Implementation Details

- **Notification Type**: `NotificationType::FORGOT_PASSWORD`
- **Handler**: `ForgotPasswordHandler`
- **Mailable**: `ForgotPasswordMail` (queued)
- **Template**: `resources/views/emails/auth/forgot-password.blade.php`

## Required Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `token` | string | Yes | The password reset token |
| `expiration_minutes` | int | No | Token expiration in minutes (defaults to config value: 60) |

## Usage Example

### Direct Usage (Synchronous)

```php
use App\Contracts\Notifications\NotificationServiceInterface;
use App\Enums\Notification\NotificationType;
use App\Models\User;

class ForgotPasswordController
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {}

    public function sendResetLink(Request $request)
    {
        $user = User::where('email', $request->email)->firstOrFail();

        // Generate token (you'll implement this in your password reset service)
        $token = Str::random(64);

        // Store token in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Send notification
        $this->notificationService->send(
            $user,
            NotificationType::FORGOT_PASSWORD,
            [
                'token' => $token,
                'expiration_minutes' => 60, // Optional, defaults to config
            ]
        );

        return response()->json(['message' => 'Password reset link sent']);
    }
}
```

### With RabbitMQ (Asynchronous - Recommended)

#### 1. Create a Job

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Notifications\NotificationServiceInterface;
use App\Enums\Notification\NotificationType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendForgotPasswordNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly string $token,
        private readonly int $expirationMinutes = 60
    ) {}

    public function handle(NotificationServiceInterface $notificationService): void
    {
        $user = User::findOrFail($this->userId);

        $notificationService->send(
            $user,
            NotificationType::FORGOT_PASSWORD,
            [
                'token' => $this->token,
                'expiration_minutes' => $this->expirationMinutes,
            ]
        );
    }
}
```

#### 2. Dispatch the Job

```php
use App\Jobs\Notification\SendForgotPasswordNotificationJob;

public function sendResetLink(Request $request)
{
    $user = User::where('email', $request->email)->firstOrFail();

    // Generate and store token
    $token = Str::random(64);
    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $user->email],
        [
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]
    );

    // Dispatch to RabbitMQ
    SendForgotPasswordNotificationJob::dispatch(
        $user->id,
        $token,
        60
    );

    return response()->json(['message' => 'Password reset link will be sent shortly']);
}
```

## Password Reset URL

The handler generates a password reset URL in this format:

```
{APP_URL}/reset-password?token={TOKEN}&email={EMAIL}
```

**Note**: When you implement the password reset flow, you'll need to create:
1. A route: `GET /reset-password`
2. A controller to handle the reset form
3. A route: `POST /reset-password` to process the reset

Example route registration:

```php
// In routes/web.php
Route::get('/reset-password', [PasswordResetController::class, 'showResetForm'])
    ->name('password.reset')
    ->middleware('guest');

Route::post('/reset-password', [PasswordResetController::class, 'reset'])
    ->name('password.update')
    ->middleware('guest');
```

## Configuration

Token expiration is configured in [config/auth.php:97](../../../config/auth.php#L97):

```php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
        'expire' => 60, // minutes
        'throttle' => 60, // seconds between requests
    ],
],
```

## Email Template

The email template is located at:
- [resources/views/emails/auth/forgot-password.blade.php](../../../../resources/views/emails/auth/forgot-password.blade.php)

The template receives:
- `$user` - The User model instance
- `$resetUrl` - The complete password reset URL
- `$expirationMinutes` - How long the link is valid

## Testing

### Unit Test Example

```php
<?php

namespace Tests\Unit\Services\Notifications;

use App\Contracts\Notifications\NotificationRegistryInterface;
use App\Enums\Notification\NotificationType;
use App\Models\User;
use App\Services\Notifications\Handlers\ForgotPasswordHandler;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_forgot_password_notification_successfully(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $registry = app(NotificationRegistryInterface::class);
        $service = new NotificationService($registry);

        $result = $service->send(
            $user,
            NotificationType::FORGOT_PASSWORD,
            [
                'token' => 'test-token-123',
                'expiration_minutes' => 60,
            ]
        );

        $this->assertTrue($result);
        Mail::assertQueued(\App\Mail\Auth\ForgotPasswordMail::class);
    }

    public function test_throws_exception_when_token_is_missing(): void
    {
        $user = User::factory()->create();
        $registry = app(NotificationRegistryInterface::class);
        $service = new NotificationService($registry);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: token');

        $service->send(
            $user,
            NotificationType::FORGOT_PASSWORD,
            [] // Missing token
        );
    }
}
```

### Feature Test Example

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200);
        Mail::assertQueued(\App\Mail\Auth\ForgotPasswordMail::class);
    }
}
```

## Integration with Laravel's Password Broker

If you prefer to use Laravel's built-in password broker instead of manually managing tokens, you can modify the handler:

```php
use Illuminate\Support\Facades\Password;

// In your controller
public function sendResetLink(Request $request)
{
    $user = User::where('email', $request->email)->firstOrFail();

    // Generate token using Laravel's Password facade
    $token = Password::createToken($user);

    // Send notification via our service
    $this->notificationService->send(
        $user,
        NotificationType::FORGOT_PASSWORD,
        ['token' => $token]
    );
}
```

## Security Considerations

1. **Token Storage**: Tokens in the database are hashed using `Hash::make()`
2. **Token Expiration**: Tokens expire after 60 minutes (configurable)
3. **Rate Limiting**: Password reset requests are throttled (60 seconds between requests)
4. **Email Validation**: Handler validates that user has a valid email address
5. **HTTPS**: Ensure your application uses HTTPS in production for secure token transmission

## Troubleshooting

### Email Not Sending

1. Check your mail configuration in `.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=mailpit
   MAIL_PORT=1025
   MAIL_USERNAME=null
   MAIL_PASSWORD=null
   MAIL_ENCRYPTION=null
   MAIL_FROM_ADDRESS="noreply@example.com"
   MAIL_FROM_NAME="${APP_NAME}"
   ```

2. Check the logs: `storage/logs/laravel.log`

3. For local development, use MailCatcher (available at `http://localhost:1025`)

### Token Not Working

1. Verify token is stored in `password_reset_tokens` table
2. Check token hasn't expired (created_at + 60 minutes)
3. Ensure token is properly hashed when stored but plain when sent in email

### Queue Not Processing

1. Ensure queue worker is running:
   ```bash
   make artisan C="queue:work rabbitmq --queue=notifications"
   ```

2. Check RabbitMQ connection in `.env`:
   ```env
   QUEUE_CONNECTION=rabbitmq
   RABBITMQ_HOST=localhost
   RABBITMQ_PORT=5672
   ```

## Future Enhancements

When implementing the full password reset flow, consider:

1. Adding a `ForgotPasswordRequest` form request for validation
2. Creating a `PasswordResetService` to handle token generation and validation
3. Adding rate limiting to prevent abuse
4. Implementing password strength requirements
5. Adding audit logging for password reset attempts
6. Sending a confirmation email after successful password reset
7. Invalidating all user sessions after password change
