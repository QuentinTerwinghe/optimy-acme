# Payment System Documentation

**SOLID Score: 95% (A+)** - Highest scoring domain in the project ⭐

The Payment system is the most sophisticated and well-architected domain in the ACME Corp application, demonstrating exemplary use of SOLID principles and design patterns.

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Payment Flow](#payment-flow)
- [Components](#components)
- [Usage Examples](#usage-examples)
- [Adding New Payment Gateways](#adding-new-payment-gateways)
- [Testing](#testing)
- [Security](#security)

---

## Overview

The Payment system handles all payment processing for campaign donations, including initialization, processing, callbacks, and refunds. It uses a pluggable architecture that makes it easy to add new payment methods without modifying existing code.

### Key Features

- **Multiple Payment Methods**: Fake (test), PayPal (future), Credit Card (future)
- **Strategy Pattern**: Easy to add new payment gateways
- **Async Processing**: RabbitMQ integration for notifications and campaign updates
- **Comprehensive State Management**: Payment and donation status tracking
- **Refund Support**: Full and partial refunds
- **Security**: Policy-based authorization, callback validation
- **Error Handling**: Custom exceptions with detailed error information

### Architecture Highlights

- ✅ **7 Focused Interfaces** - Perfect interface segregation
- ✅ **Strategy + Registry Patterns** - Extensible gateway system
- ✅ **Service Layer Separation** - 4 specialized services
- ✅ **Custom Exceptions** - 7 domain-specific exceptions
- ✅ **State Machine** - Payment status with validated transitions
- ✅ **Full Type Safety** - All DTOs, enums, and type hints

---

## Architecture

### Design Patterns

#### 1. Strategy Pattern (Payment Gateways)

Different payment methods are handled by interchangeable gateway implementations:

```
PaymentService → PaymentGatewayRegistry → PaymentGatewayInterface
                                           ├── FakePaymentGateway
                                           ├── PayPalGateway (future)
                                           └── StripeGateway (future)
```

**Benefits**:
- Add new payment methods without changing existing code
- Each gateway is independently testable
- Easy to switch between test and production gateways

#### 2. Registry Pattern (Gateway Registry)

Centralized registration and retrieval of payment gateways:

```php
// Registration (in PaymentServiceProvider)
$registry->register($fakeGateway);

// Retrieval (in PaymentService)
$gateway = $this->gatewayRegistry->getGateway($paymentMethod);
```

#### 3. Template Method Pattern (Refunds)

Base validation logic in `AbstractPaymentGateway`, specific implementation in concrete gateways:

```php
// Template method
public function refundPayment(Payment $payment, RefundPaymentDTO $dto): Payment
{
    $this->validateRefund($payment, $dto); // Common validation
    return $this->performRefund($payment, $dto); // Gateway-specific
}
```

#### 4. Service Layer Pattern

Clear separation of concerns across 4 services:

| Service | Responsibility | Interface |
|---------|---------------|-----------|
| `PaymentProcessService` | Initialize donations & payments | `PaymentProcessServiceInterface` |
| `PaymentPreparationService` | Generate payloads & redirect URLs | `PaymentPreparationServiceInterface` |
| `PaymentCallbackService` | Handle gateway callbacks | `PaymentCallbackServiceInterface` |
| `PaymentService` | High-level operations (process, refund, verify) | `PaymentServiceInterface` |

### Domain Structure

```
app/
├── Services/Payment/
│   ├── PaymentService.php                     # Main service
│   ├── PaymentProcessService.php              # Initialization
│   ├── PaymentPreparationService.php          # Preparation
│   ├── PaymentCallbackService.php             # Callbacks
│   ├── PaymentGatewayRegistry.php             # Registry
│   ├── AbstractPaymentGateway.php             # Base class
│   ├── Gateways/
│   │   └── FakePaymentGateway.php             # Test gateway
│   └── CallbackHandlers/
│       └── FakePaymentCallbackHandler.php     # Callback handler
│
├── Contracts/Payment/
│   ├── PaymentServiceInterface.php            # Main service contract
│   ├── PaymentProcessServiceInterface.php     # Initialization contract
│   ├── PaymentPreparationServiceInterface.php # Preparation contract
│   ├── PaymentCallbackServiceInterface.php    # Callback contract
│   ├── PaymentGatewayInterface.php            # Gateway contract
│   ├── PaymentCallbackHandlerInterface.php    # Handler contract
│   ├── PaymentMethodHandlerInterface.php      # Method handler contract
│   └── ProcessPaymentDTOInterface.php         # DTO marker interface
│
├── Http/Controllers/Payment/
│   ├── ProcessPaymentController.php           # Initialize payments (API)
│   ├── PaymentCallbackController.php          # Handle callbacks
│   ├── FakePaymentController.php              # Fake payment UI
│   ├── PaymentResultController.php            # Show result page
│   └── PaymentMethodController.php            # List payment methods
│
├── Models/Payment/
│   └── Payment.php                            # Payment model
│
├── Enums/Payment/
│   ├── PaymentStatusEnum.php                  # Status with transitions
│   ├── PaymentMethodEnum.php                  # Available methods
│   └── FailureReasonEnum.php                  # Failure reasons
│
├── DTOs/Payment/
│   ├── FakeProcessPaymentDTO.php              # Fake gateway DTO
│   ├── PaymentCallbackResultDTO.php           # Standardized result
│   ├── PaymentPreparationResultDTO.php        # Preparation result
│   └── RefundPaymentDTO.php                   # Refund data
│
├── Exceptions/Payment/
│   ├── PaymentException.php                   # Base exception
│   ├── PaymentProcessingException.php         # Processing errors
│   ├── PaymentRefundException.php             # Refund errors
│   ├── PaymentVerificationException.php       # Verification errors
│   ├── InvalidPaymentDataException.php        # Data validation errors
│   ├── UnsupportedPaymentMethodException.php  # Unsupported method
│   └── PaymentCallbackException.php           # Callback errors
│
├── Policies/Payment/
│   └── PaymentPolicy.php                      # Authorization
│
└── Http/Requests/Payment/
    └── InitializePaymentRequest.php           # Validation
```

---

## Payment Flow

### Complete Payment Lifecycle

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │ POST /api/payments/initialize
       │ {campaign_id, amount, payment_method}
       ▼
┌──────────────────────┐
│ ProcessPayment       │
│ Controller           │
└──────┬───────────────┘
       │ Validate request
       ▼
┌──────────────────────┐
│ PaymentProcess       │  Creates Donation (PENDING)
│ Service              │  Creates Payment (PENDING)
└──────┬───────────────┘
       │ Returns payment
       ▼
┌──────────────────────┐
│ PaymentPreparation   │  Generates payload
│ Service              │  Generates redirect URL
└──────┬───────────────┘
       │ Returns redirect_url
       ▼
┌──────────────────────┐
│ Response to Client   │
│ {donation, payment,  │
│  redirect_url}       │
└──────┬───────────────┘
       │
       │ User clicks "Pay Now"
       ▼
┌──────────────────────┐
│ Payment Gateway Page │  e.g., /payment/fake/{id}
│ (Fake/PayPal/Stripe) │  User completes payment
└──────┬───────────────┘
       │ Gateway calls callback URL
       ▼
┌──────────────────────┐
│ PaymentCallback      │  Validates callback
│ Controller           │  Authorizes request
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ PaymentCallback      │  Resolves handler
│ Service              │  Processes callback
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Callback Handler     │  Validates data
│ (Fake/PayPal/Stripe) │  Returns result DTO
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Update Models        │  Payment: COMPLETED/FAILED
│                      │  Donation: SUCCESS/FAILED
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Dispatch Jobs        │  UpdateCampaignAmount
│ (RabbitMQ)           │  SendPaymentNotification
│                      │  SendDonationNotification
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Redirect to Result   │  /payment/{id}
│                      │  Show success/error message
└──────────────────────┘
```

### State Transitions

```
Payment Status State Machine:

PENDING ──┐
          ├─→ PROCESSING ──┐
          │                 ├─→ COMPLETED ──→ REFUNDED
          │                 └─→ FAILED
          └─→ FAILED

Donation Status:
PENDING ──→ SUCCESS (if payment completed)
       └──→ FAILED (if payment failed/refunded)
```

---

## Components

### Services

#### PaymentService

**Location**: [app/Services/Payment/PaymentService.php](../www/app/Services/Payment/PaymentService.php)

**Purpose**: Main service for high-level payment operations

**Methods**:
- `createPayment()` - Create payment record
- `processPayment()` - Process payment via gateway
- `refundPayment()` - Refund payment (full or partial)
- `verifyPaymentStatus()` - Verify status with gateway
- `getAvailablePaymentMethods()` - Get enabled payment methods
- `getPaymentStatistics()` - Get statistics for a donation

**Usage**:
```php
$paymentService = app(PaymentServiceInterface::class);
$refundedPayment = $paymentService->refundPayment($payment, amount: 50.00);
```

#### PaymentProcessService

**Location**: [app/Services/Payment/PaymentProcessService.php](../www/app/Services/Payment/PaymentProcessService.php)

**Purpose**: Initialize payment process (create donation + payment)

**Methods**:
- `initializePayment()` - Creates donation and payment in PENDING status

**Usage**:
```php
$processService = app(PaymentProcessServiceInterface::class);
$result = $processService->initializePayment(
    campaignId: $campaign->id,
    userId: $user->id,
    amount: 100.00,
    paymentMethod: PaymentMethodEnum::FAKE,
    metadata: []
);
// Returns: ['donation' => Donation, 'payment' => Payment]
```

#### PaymentPreparationService

**Location**: [app/Services/Payment/PaymentPreparationService.php](../www/app/Services/Payment/PaymentPreparationService.php)

**Purpose**: Prepare payments (generate payload and redirect URL)

**Methods**:
- `prepare()` - Generates redirect URL for payment
- `registerHandler()` - Register payment method handler

**Usage**:
```php
$preparationService = app(PaymentPreparationServiceInterface::class);
$redirectUrl = $preparationService->prepare($payment);
return redirect($redirectUrl);
```

#### PaymentCallbackService

**Location**: [app/Services/Payment/PaymentCallbackService.php](../www/app/Services/Payment/PaymentCallbackService.php)

**Purpose**: Process callbacks from payment gateways

**Methods**:
- `registerHandler()` - Register callback handler
- `processCallback()` - Process gateway callback
- `getHandlers()` - Get all registered handlers

**Workflow**:
1. Validates callback authenticity
2. Updates payment status
3. Updates donation status
4. Dispatches notification jobs
5. Dispatches campaign amount update job
6. Returns redirect route

---

### Payment Gateways

#### FakePaymentGateway

**Location**: [app/Services/Payment/Gateways/FakePaymentGateway.php](../www/app/Services/Payment/Gateways/FakePaymentGateway.php)

**Purpose**: Test/development gateway that simulates payment processing

**Features**:
- Simulates success/failure
- Configurable processing delays
- Generates fake transaction IDs
- Supports card details simulation

**Configuration**:
```php
// Simulate success (default)
$dto = new FakeProcessPaymentDTO(
    metadata: ['user_agent' => 'test'],
    simulateFailure: false
);

// Simulate failure
$dto = new FakeProcessPaymentDTO(
    metadata: [],
    simulateFailure: true,
    errorMessage: 'Card declined',
    errorCode: 'CARD_DECLINED',
    processingDelay: 2 // seconds
);
```

---

### Models

#### Payment Model

**Location**: [app/Models/Payment/Payment.php](../www/app/Models/Payment/Payment.php)

**Key Fields**:
- `id` - UUID primary key
- `donation_id` - Foreign key to donation
- `payment_method` - Enum: FAKE, PAYPAL, CREDIT_CARD
- `status` - Enum: PENDING, PROCESSING, COMPLETED, FAILED, REFUNDED
- `amount` - Decimal (10,2)
- `currency` - String (default: USD)
- `transaction_id` - External provider transaction ID
- `gateway_response` - Raw response from gateway
- `error_message` / `error_code` - Error details
- `metadata` - Additional data (JSON)
- `payload` - Data sent to gateway (JSON)
- `redirect_url` - Where user completes payment
- Timestamps: `prepared_at`, `initiated_at`, `completed_at`, `failed_at`, `refunded_at`

**Helper Methods**:
- `isCompleted()`, `isFailed()`, `isPending()`, `isProcessing()`, `isRefunded()`
- `markAsCompleted()`, `markAsFailed()`, `markAsProcessing()`, `markAsPrepared()`, `markAsRefunded()`

**Relationships**:
- `belongsTo(Donation::class)`

---

### Enums

#### PaymentStatusEnum

**Location**: [app/Enums/Payment/PaymentStatusEnum.php](../www/app/Enums/Payment/PaymentStatusEnum.php)

**Values**: `PENDING`, `PROCESSING`, `COMPLETED`, `FAILED`, `REFUNDED`

**Methods**:
- `label()` - Human-readable label
- `color()` - UI color (success, danger, warning, info)
- `isTerminal()` - Check if status is final (COMPLETED, FAILED, REFUNDED)
- `canTransitionTo()` - Validate state transition
- `validTransitions()` - Get allowed transitions

**State Transition Rules**:
```php
PENDING → [PROCESSING, FAILED]
PROCESSING → [COMPLETED, FAILED]
COMPLETED → [REFUNDED]
FAILED → [] (terminal)
REFUNDED → [] (terminal)
```

#### PaymentMethodEnum

**Location**: [app/Enums/Payment/PaymentMethodEnum.php](../www/app/Enums/Payment/PaymentMethodEnum.php)

**Values**: `FAKE`, `PAYPAL`, `CREDIT_CARD`

**Methods**:
- `label()` - Display label
- `isTest()` - Check if test method
- `isEnabled()` - Check if enabled (currently only FAKE is enabled)

---

### DTOs

#### PaymentCallbackResultDTO

**Location**: [app/DTOs/Payment/PaymentCallbackResultDTO.php](../www/app/DTOs/Payment/PaymentCallbackResultDTO.php)

Standardized callback result across all gateways

**Fields**:
- `status` - PaymentStatusEnum (COMPLETED or FAILED)
- `transactionId` - External transaction ID
- `gatewayResponse` - Raw response data
- `errorMessage` / `errorCode` - Error details (for failures)
- `redirectRoute` / `redirectParams` - Where to redirect user

**Static Factories**:
```php
PaymentCallbackResultDTO::success(
    transactionId: 'ABC123',
    gatewayResponse: ['...'],
    redirectRoute: 'payment.result',
    redirectParams: ['payment' => $payment->id]
);

PaymentCallbackResultDTO::failed(
    errorMessage: 'Card declined',
    errorCode: 'CARD_DECLINED',
    gatewayResponse: ['...'],
    redirectRoute: 'payment.result',
    redirectParams: ['payment' => $payment->id]
);
```

---

## Usage Examples

### 1. Initialize Payment (API)

```bash
POST /api/payments/initialize
Content-Type: application/json
Authorization: Bearer {token}

{
  "campaign_id": "uuid-of-campaign",
  "amount": 100.00,
  "payment_method": "fake",
  "metadata": {
    "source": "web",
    "referrer": "campaign_page"
  }
}
```

**Response**:
```json
{
  "success": true,
  "message": "Payment initialized and prepared successfully",
  "data": {
    "donation": {
      "id": "uuid-donation",
      "campaign_id": "uuid-campaign",
      "amount": 100.00,
      "status": "pending"
    },
    "payment": {
      "id": "uuid-payment",
      "donation_id": "uuid-donation",
      "amount": 100.00,
      "currency": "USD",
      "payment_method": "fake",
      "status": "pending"
    },
    "redirect_url": "http://app/payment/fake/uuid-payment?session=..."
  }
}
```

### 2. Process Payment (Fake Gateway)

After receiving the `redirect_url`, redirect the user to that URL. For the fake gateway:

1. User lands on `/payment/fake/{payment}`
2. User sees UI to simulate success or failure
3. User clicks button to complete payment
4. Callback is sent to `/payment/callback/{payment}`
5. Payment and donation statuses are updated
6. User is redirected to `/payment/{payment}` (result page)

### 3. Refund Payment

```php
use App\Contracts\Payment\PaymentServiceInterface;
use App\Models\Payment\Payment;

$paymentService = app(PaymentServiceInterface::class);
$payment = Payment::findOrFail($paymentId);

// Full refund
$refundedPayment = $paymentService->refundPayment($payment);

// Partial refund
$refundedPayment = $paymentService->refundPayment($payment, amount: 50.00);
```

### 4. Get Available Payment Methods

```php
$paymentService = app(PaymentServiceInterface::class);
$methods = $paymentService->getAvailablePaymentMethods();

// Returns: [PaymentMethodEnum::FAKE]
```

### 5. Get Payment Statistics

```php
$donation = Donation::find($donationId);
$stats = $paymentService->getPaymentStatistics($donation);

// Returns:
[
  'total_payments' => 3,
  'successful_payments' => 1,
  'failed_payments' => 2,
  'pending_payments' => 0,
  'refunded_payments' => 0,
  'total_amount_paid' => 100.00,
  'total_amount_refunded' => 0.00
]
```

---

## Adding New Payment Gateways

### Example: Adding Stripe

#### Step 1: Create Gateway Class

Create `app/Services/Payment/Gateways/StripeGateway.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Services\Payment\AbstractPaymentGateway;
use App\Contracts\Payment\PaymentMethodHandlerInterface;
use App\Contracts\Payment\ProcessPaymentDTOInterface;
use App\DTOs\Payment\PaymentPreparationResultDTO;
use App\DTOs\Payment\RefundPaymentDTO;
use App\Enums\Payment\PaymentMethodEnum;
use App\Models\Payment\Payment;

class StripeGateway extends AbstractPaymentGateway implements PaymentMethodHandlerInterface
{
    public function processPayment(Payment $payment, ProcessPaymentDTOInterface $dto): Payment
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        $charge = $stripe->charges->create([
            'amount' => $payment->amount * 100, // cents
            'currency' => strtolower($payment->currency),
            'source' => $dto->stripeToken,
            'description' => "Donation for campaign {$payment->donation->campaign_id}",
        ]);

        if ($charge->status === 'succeeded') {
            $payment->markAsCompleted($charge->id, (array) $charge);
        } else {
            $payment->markAsFailed('Stripe charge failed', $charge->failure_code);
        }

        return $payment->fresh();
    }

    public function prepare(Payment $payment): PaymentPreparationResultDTO
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($payment->currency),
                    'product_data' => ['name' => 'Campaign Donation'],
                    'unit_amount' => $payment->amount * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('payment.callback', ['payment' => $payment->id]),
            'cancel_url' => route('payment.result', ['payment' => $payment->id]),
        ]);

        $payment->update([
            'payload' => ['session_id' => $session->id],
            'redirect_url' => $session->url,
            'prepared_at' => now(),
        ]);

        return new PaymentPreparationResultDTO(
            payload: ['session_id' => $session->id],
            redirectUrl: $session->url
        );
    }

    protected function performRefund(Payment $payment, RefundPaymentDTO $dto): Payment
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        $refund = $stripe->refunds->create([
            'charge' => $payment->transaction_id,
            'amount' => $this->getRefundAmount($payment, $dto) * 100,
        ]);

        $payment->markAsRefunded($refund->id);
        return $payment->fresh();
    }

    public function verifyPaymentStatus(Payment $payment): Payment
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        $charge = $stripe->charges->retrieve($payment->transaction_id);

        // Update payment based on Stripe's current status
        if ($charge->status === 'succeeded' && !$payment->isCompleted()) {
            $payment->markAsCompleted($charge->id, (array) $charge);
        }

        return $payment->fresh();
    }

    public function getPaymentMethod(): PaymentMethodEnum
    {
        return PaymentMethodEnum::CREDIT_CARD;
    }

    public function supports(string $paymentMethod): bool
    {
        return $paymentMethod === PaymentMethodEnum::CREDIT_CARD->value;
    }

    public function getName(): string
    {
        return 'stripe';
    }
}
```

#### Step 2: Create Callback Handler

Create `app/Services/Payment/CallbackHandlers/StripeCallbackHandler.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Payment\CallbackHandlers;

use App\Contracts\Payment\PaymentCallbackHandlerInterface;
use App\DTOs\Payment\PaymentCallbackResultDTO;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Payment\Payment;
use Illuminate\Http\Request;

class StripeCallbackHandler implements PaymentCallbackHandlerInterface
{
    public function handleCallback(Payment $payment, Request $request): PaymentCallbackResultDTO
    {
        $sessionId = $request->input('session_id');

        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        $session = $stripe->checkout->sessions->retrieve($sessionId);

        if ($session->payment_status === 'paid') {
            return PaymentCallbackResultDTO::success(
                transactionId: $session->payment_intent,
                gatewayResponse: (array) $session,
                redirectRoute: 'payment.result',
                redirectParams: ['payment' => $payment->id]
            );
        }

        return PaymentCallbackResultDTO::failed(
            errorMessage: 'Payment not completed',
            errorCode: $session->payment_status,
            gatewayResponse: (array) $session,
            redirectRoute: 'payment.result',
            redirectParams: ['payment' => $payment->id]
        );
    }

    public function getPaymentMethod(): PaymentMethodEnum
    {
        return PaymentMethodEnum::CREDIT_CARD;
    }

    public function validateCallback(Payment $payment, Request $request): bool
    {
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $signature,
                $webhookSecret
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
```

#### Step 3: Register Gateway and Handler

In `app/Providers/PaymentServiceProvider.php`:

```php
public function boot(): void
{
    /** @var PaymentGatewayRegistry $registry */
    $registry = $this->app->make(PaymentGatewayRegistry::class);

    /** @var PaymentPreparationServiceInterface $preparationService */
    $preparationService = $this->app->make(PaymentPreparationServiceInterface::class);

    // Register gateways
    $fakeGateway = $this->app->make(FakePaymentGateway::class);
    $registry->register($fakeGateway);
    $preparationService->registerHandler(PaymentMethodEnum::FAKE->value, $fakeGateway);

    // Add Stripe
    $stripeGateway = $this->app->make(StripeGateway::class);
    $registry->register($stripeGateway);
    $preparationService->registerHandler(PaymentMethodEnum::CREDIT_CARD->value, $stripeGateway);
}
```

In `app/Providers/AppServiceProvider.php`:

```php
public function register(): void
{
    // ... existing bindings

    // Register callback handlers
    $this->app->afterResolving(PaymentCallbackServiceInterface::class, function ($service) {
        $service->registerHandler(new FakePaymentCallbackHandler());
        $service->registerHandler(new StripeCallbackHandler()); // Add this
    });
}
```

#### Step 4: Enable Payment Method

In `app/Enums/Payment/PaymentMethodEnum.php`:

```php
public function isEnabled(): bool
{
    return match ($this) {
        self::FAKE => true,
        self::CREDIT_CARD => true, // Enable Stripe
        self::PAYPAL => false,
    };
}
```

#### Step 5: Add Configuration

In `config/services.php`:

```php
'stripe' => [
    'secret' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

In `.env`:

```env
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

---

## Testing

### Unit Tests

Test individual components in isolation:

```php
use Tests\TestCase;
use App\Services\Payment\PaymentProcessService;
use App\Models\Campaign\Campaign;
use App\Models\Auth\User;
use App\Enums\Payment\PaymentMethodEnum;

test('initializes payment correctly', function () {
    $campaign = Campaign::factory()->create();
    $user = User::factory()->create();

    $service = app(PaymentProcessService::class);
    $result = $service->initializePayment(
        campaignId: $campaign->id,
        userId: $user->id,
        amount: 100.00,
        paymentMethod: PaymentMethodEnum::FAKE,
        metadata: []
    );

    expect($result)->toHaveKeys(['donation', 'payment'])
        ->and($result['donation']->amount)->toBe(100.00)
        ->and($result['payment']->status)->toBe(PaymentStatusEnum::PENDING);
});
```

### Feature Tests

Test complete payment flows:

```php
use Tests\TestCase;
use App\Models\Campaign\Campaign;
use App\Models\Auth\User;

test('can initialize payment via API', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->active()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/payments/initialize', [
            'campaign_id' => $campaign->id,
            'amount' => 100.00,
            'payment_method' => 'fake',
        ]);

    $response->assertCreated()
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'data' => ['donation', 'payment', 'redirect_url']
        ]);
});
```

---

## Security

### Authorization

**Payment Policy** ([app/Policies/Payment/PaymentPolicy.php](../www/app/Policies/Payment/PaymentPolicy.php)):

- `access()` - Check if user can access fake payment page
  - Must be authenticated
  - Must own the payment (via donation)
  - Payment status must be PENDING
  - Payment method must be FAKE

- `view()` - Check if user can view payment result
  - Must be authenticated
  - Must own the payment
  - Payment status must be COMPLETED or FAILED

- `processCallback()` - Check if user can process callback
  - Must be authenticated
  - Must own the payment
  - Payment status must be PENDING (prevents duplicate processing)

### Callback Validation

All callback handlers must implement `validateCallback()`:

```php
public function validateCallback(Payment $payment, Request $request): bool
{
    // Verify signature/token from payment gateway
    // Return false if validation fails
    // PaymentCallbackService will throw exception if false
}
```

### Data Sanitization

- All user input validated via `InitializePaymentRequest`
- Amount validated: min 0.01, max 999,999.99
- Campaign existence validated
- Payment method validated against enum

### Database Transactions

All critical operations wrapped in database transactions:

- Payment initialization (donation + payment creation)
- Payment refund
- Callback processing (payment + donation updates)

---

## Best Practices

### When to Use Each Service

- **PaymentService**: High-level operations (refunds, verification)
- **PaymentProcessService**: Only for initialization
- **PaymentPreparationService**: Only for generating redirect URLs
- **PaymentCallbackService**: Only for processing callbacks

### Error Handling

Always wrap payment operations in try-catch:

```php
try {
    $payment = $paymentService->refundPayment($payment, 50.00);
} catch (PaymentRefundException $e) {
    Log::error('Refund failed', ['payment' => $payment->id, 'error' => $e->getMessage()]);
    return response()->json(['error' => 'Refund failed'], 422);
}
```

### Logging

The payment system logs all operations:

- Payment initialization
- Payment processing
- Refunds
- Callbacks (success and failure)
- Gateway errors

Check logs at `storage/logs/laravel.log`

### Async Operations

The following operations are dispatched as jobs:

- `UpdateCampaignAmountJob` - Updates campaign total after successful payment
- `SendPaymentNotificationJob` - Sends payment success/failure notification
- `SendNewDonationNotificationJob` - Sends donation notification to campaign owner

---

## Summary

The Payment system demonstrates **exemplary architecture** with a SOLID score of **95% (A+)**, the highest in the project. Key achievements:

- ✅ **Strategy Pattern** - Easy to add new payment gateways
- ✅ **Registry Pattern** - Centralized gateway management
- ✅ **Template Method Pattern** - Reusable refund validation
- ✅ **Interface Segregation** - 7 focused interfaces
- ✅ **Service Layer Separation** - 4 specialized services
- ✅ **Comprehensive State Management** - Payment status state machine
- ✅ **Full Type Safety** - DTOs, enums, type hints throughout
- ✅ **Security** - Policy-based authorization, callback validation
- ✅ **Extensibility** - Add new payment methods without modifying existing code

The architecture makes it straightforward to add new payment gateways (Stripe, PayPal, etc.) by implementing the gateway interface and registering it in the provider.

---

For more information, see:
- [SOLID Principles Assessment](SOLID.md) - Overall architecture analysis
- [Architecture Guide](ARCHITECTURE.md) - Project structure
- [Testing Guide](TESTING.md) - How to test the payment system
