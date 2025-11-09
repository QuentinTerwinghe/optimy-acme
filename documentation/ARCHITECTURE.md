# Architecture Guide

This document describes the architecture, design principles, and organizational patterns used in the ACME Corp Laravel application.

## Table of Contents

- [Overview](#overview)
- [Pseudo-DDD Organization](#pseudo-ddd-organization)
- [SOLID Principles](#solid-principles)
- [Directory Structure](#directory-structure)
- [Domain Examples](#domain-examples)
- [Best Practices](#best-practices)
- [Conventions](#conventions)

## Overview

This project follows a **Pseudo-DDD (Domain-Driven Design)** architecture that combines:
- **Laravel's familiar structure** - Technical layers remain at the top level
- **Domain organization** - Within each layer, code is organized by domain
- **SOLID principles** - Clean, maintainable, and testable code
- **Clear separation of concerns** - Each layer has a specific responsibility

### Key Benefits

✅ **Familiar Structure**: Developers comfortable with Laravel will recognize the layout
✅ **Domain Clarity**: All Campaign-related code is grouped together
✅ **Scalability**: Easy to add new domains without affecting existing code
✅ **Maintainability**: Changes to a domain are localized to specific folders
✅ **Testability**: Clear boundaries make unit and integration testing straightforward

## Pseudo-DDD Organization

### What is Pseudo-DDD?

Unlike traditional DDD where domains are top-level folders, Pseudo-DDD maintains Laravel's technical layers at the root and organizes by domain within each layer.

**Traditional Laravel:**
```
app/Http/Controllers/
├── CampaignController.php
├── CategoryController.php
├── TagController.php
├── UserController.php
└── ...
```

**Pseudo-DDD Approach:**
```
app/Http/Controllers/
├── Campaign/
│   ├── CampaignController.php
│   ├── CategoryController.php
│   └── TagController.php
├── Auth/
│   ├── LoginController.php
│   └── RegisterController.php
└── Notifications/
    └── NotificationController.php
```

### When to Create a New Domain

Create a new domain folder when you have:
- **Cohesive functionality**: Related features that work together
- **Clear boundaries**: Can be understood and modified independently
- **Multiple files**: At least 2-3 related classes
- **Business concept**: Represents a distinct area of the business

**Examples of good domains:**
- `Campaign` - Campaign management, categories, tags
- `Auth` - Authentication, authorization, password reset
- `Notifications` - Notification system, handlers, delivery
- `Payment` - Payment processing, transactions, refunds
- `User` - User management, profiles, preferences

## SOLID Principles

All code in this project follows SOLID principles to ensure maintainability and testability.

### Single Responsibility Principle (SRP)

**Each class should have one reason to change.**

```php
// ✅ GOOD: Controller only handles HTTP
class CampaignController extends Controller
{
    public function __construct(
        private CampaignServiceInterface $campaignService
    ) {}

    public function store(StoreCampaignRequest $request)
    {
        $campaign = $this->campaignService->create($request->validated());
        return response()->json($campaign, 201);
    }
}

// ✅ GOOD: Service contains business logic
class CampaignService implements CampaignServiceInterface
{
    public function create(array $data): Campaign
    {
        // Business logic here
        return Campaign::create($data);
    }
}

// ❌ BAD: Controller doing business logic
class CampaignController extends Controller
{
    public function store(Request $request)
    {
        // Validation, business logic, all mixed together
        $campaign = new Campaign();
        $campaign->title = $request->title;
        // ... more logic
        $campaign->save();
        return response()->json($campaign);
    }
}
```

### Open/Closed Principle (OCP)

**Open for extension, closed for modification.**

```php
// ✅ GOOD: Using interfaces for extensibility
interface NotificationHandlerInterface
{
    public function handle(User $user, array $parameters): bool;
}

class EmailHandler implements NotificationHandlerInterface { }
class SmsHandler implements NotificationHandlerInterface { }

// Easy to add new handlers without modifying existing code
class PushHandler implements NotificationHandlerInterface { }
```

### Liskov Substitution Principle (LSP)

**Derived classes must be substitutable for their base classes.**

```php
// ✅ GOOD: All implementations work the same way
public function send(NotificationHandlerInterface $handler, User $user)
{
    // Any handler can be used here
    return $handler->handle($user, []);
}
```

### Interface Segregation Principle (ISP)

**Clients should not depend on interfaces they don't use.**

```php
// ✅ GOOD: Specific interfaces
interface CampaignQueryServiceInterface
{
    public function getActive(): Collection;
}

interface CampaignWriteServiceInterface
{
    public function create(array $data): Campaign;
}

// ❌ BAD: One large interface
interface CampaignServiceInterface
{
    public function getActive(): Collection;
    public function create(array $data): Campaign;
    public function update(Campaign $campaign, array $data): Campaign;
    public function delete(Campaign $campaign): bool;
    // ... many more methods
}
```

### Dependency Inversion Principle (DIP)

**Depend on abstractions, not concretions.**

```php
// ✅ GOOD: Depends on interface
class CampaignController extends Controller
{
    public function __construct(
        private CampaignServiceInterface $service
    ) {}
}

// ❌ BAD: Depends on concrete class
class CampaignController extends Controller
{
    public function __construct(
        private CampaignService $service
    ) {}
}
```

## Directory Structure

### Complete Application Structure

```
www/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Campaign/              # Campaign domain controllers
│   │   │   │   ├── CampaignController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   └── TagController.php
│   │   │   ├── Auth/                  # Authentication controllers
│   │   │   │   ├── LoginController.php
│   │   │   │   └── RegisterController.php
│   │   │   └── Notifications/         # Notification controllers
│   │   │       └── NotificationController.php
│   │   │
│   │   ├── Requests/
│   │   │   ├── Campaign/              # Campaign form requests
│   │   │   │   ├── StoreCampaignRequest.php
│   │   │   │   └── UpdateCampaignRequest.php
│   │   │   └── Auth/                  # Auth form requests
│   │   │       ├── LoginRequest.php
│   │   │       └── RegisterRequest.php
│   │   │
│   │   ├── Resources/
│   │   │   └── Campaign/              # Campaign API resources
│   │   │       └── CampaignResource.php
│   │   │
│   │   └── Middleware/                # Application-wide middleware
│   │
│   ├── Models/
│   │   ├── Campaign/                  # Campaign domain models
│   │   │   ├── Campaign.php
│   │   │   ├── Category.php
│   │   │   └── Tag.php
│   │   ├── Auth/                      # Auth models
│   │   │   └── User.php
│   │   └── Concerns/                  # Shared model traits
│   │       ├── HasTimestamps.php
│   │       └── HasUserTracking.php
│   │
│   ├── Services/
│   │   ├── Campaign/                  # Campaign business logic
│   │   │   ├── CampaignQueryService.php
│   │   │   └── CampaignWriteService.php
│   │   └── Notifications/             # Notification services
│   │       ├── NotificationService.php
│   │       ├── NotificationRegistry.php
│   │       └── Handlers/
│   │           └── ForgotPasswordHandler.php
│   │
│   ├── Repositories/                  # Optional: for complex queries
│   │   └── Campaign/
│   │       └── CampaignRepository.php
│   │
│   ├── Contracts/                     # Interfaces
│   │   ├── Campaign/
│   │   │   ├── CampaignServiceInterface.php
│   │   │   └── CampaignRepositoryInterface.php
│   │   └── Notifications/
│   │       ├── NotificationServiceInterface.php
│   │       └── NotificationHandlerInterface.php
│   │
│   ├── Enums/                         # Application enums
│   │   ├── CampaignStatus.php
│   │   ├── Currency.php
│   │   └── NotificationType.php
│   │
│   ├── Exceptions/                    # Custom exceptions
│   │   └── Campaign/
│   │       └── CampaignNotFoundException.php
│   │
│   ├── DataTransferObjects/           # DTOs (optional)
│   │   └── Notifications/
│   │       └── NotificationPayload.php
│   │
│   └── Providers/                     # Service providers
│       ├── AppServiceProvider.php
│       └── NotificationServiceProvider.php
│
├── database/
│   ├── factories/
│   │   ├── Campaign/                  # Campaign factories
│   │   │   ├── CampaignFactory.php
│   │   │   ├── CategoryFactory.php
│   │   │   └── TagFactory.php
│   │   └── UserFactory.php            # Shared factories
│   │
│   ├── migrations/                    # Database migrations
│   │   └── 2025_01_01_000000_create_campaigns_table.php
│   │
│   └── seeders/                       # Database seeders
│       └── DatabaseSeeder.php
│
├── resources/
│   ├── js/
│   │   ├── components/
│   │   │   ├── Campaign/              # Campaign Vue components
│   │   │   │   ├── CampaignList.vue
│   │   │   │   ├── CampaignForm.vue
│   │   │   │   └── CampaignCard.vue
│   │   │   └── Shared/                # Reusable components
│   │   │       ├── Button.vue
│   │   │       └── Modal.vue
│   │   ├── app.js                     # Main Vue entry point
│   │   └── bootstrap.js
│   │
│   └── views/                         # Blade templates
│       └── emails/
│           └── auth/
│               └── forgot-password.blade.php
│
└── tests/
    ├── Feature/
    │   ├── Api/                       # API tests
    │   │   └── CampaignControllerTest.php
    │   └── Campaign/                  # Campaign feature tests
    │       └── CampaignStoreTest.php
    │
    └── Unit/
        ├── Models/                    # Model tests
        │   ├── CampaignTest.php
        │   └── UserTest.php
        ├── Services/                  # Service tests
        │   ├── CampaignServiceTest.php
        │   └── Notifications/
        │       └── NotificationServiceTest.php
        ├── Enums/                     # Enum tests
        │   ├── CampaignStatusTest.php
        │   └── CurrencyTest.php
        └── Resources/                 # Resource tests
            └── CampaignResourceTest.php
```

## Domain Examples

### Campaign Domain

The Campaign domain includes everything related to campaigns, categories, and tags.

**Files in Campaign domain:**
- `app/Http/Controllers/Campaign/CampaignController.php`
- `app/Http/Requests/Campaign/StoreCampaignRequest.php`
- `app/Models/Campaign/Campaign.php`
- `app/Services/Campaign/CampaignService.php`
- `app/Contracts/Campaign/CampaignServiceInterface.php`
- `database/factories/Campaign/CampaignFactory.php`
- `resources/js/components/Campaign/CampaignForm.vue`
- `tests/Unit/Models/CampaignTest.php`
- `tests/Feature/Campaign/CampaignStoreTest.php`

### Notifications Domain

The Notifications domain handles all notification-related functionality.

**Files in Notifications domain:**
- `app/Services/Notifications/NotificationService.php`
- `app/Services/Notifications/NotificationRegistry.php`
- `app/Services/Notifications/Handlers/ForgotPasswordHandler.php`
- `app/Contracts/Notifications/NotificationServiceInterface.php`
- `app/Enums/NotificationType.php`
- `tests/Unit/Services/Notifications/NotificationServiceTest.php`

## Best Practices

### Naming Conventions

Follow these strict naming conventions:

#### Classes

- **Controllers**: `{Entity}Controller` (singular, e.g., `CampaignController`)
- **Models**: `{Entity}` (singular, e.g., `Campaign`)
- **Services**: `{Entity}Service` or `{Entity}{Action}Service` (e.g., `CampaignQueryService`)
- **Repositories**: `{Entity}Repository` (e.g., `CampaignRepository`)
- **Interfaces**: `{ClassName}Interface` (e.g., `CampaignServiceInterface`)
- **Form Requests**: `{Action}{Entity}Request` (e.g., `StoreCampaignRequest`)
- **API Resources**: `{Entity}Resource` (e.g., `CampaignResource`)
- **Factories**: `{Entity}Factory` (e.g., `CampaignFactory`)
- **Vue Components**: `{Entity}{Purpose}` (e.g., `CampaignForm.vue`)

#### Methods

- **Controllers**: Use RESTful names (`index`, `store`, `show`, `update`, `destroy`)
- **Services**: Use descriptive names (`createCampaign`, `getActiveCampaigns`)
- **Use camelCase** for all method names

#### Variables

- **Use camelCase**: `$campaignData`, `$activeCampaigns`
- **Collections are plural**: `$campaigns`, `$users`
- **Single items are singular**: `$campaign`, `$user`
- **Be descriptive**: Avoid `$data`, `$temp`, `$x`

### File Organization

1. **One class per file** - Each file should contain exactly one class
2. **Namespace matches directory** - `App\Http\Controllers\Campaign` = `app/Http/Controllers/Campaign/`
3. **Domain folders are plural** - Use `Campaign/`, not `Campaigns/`
4. **Group related files** - Keep domain files together

### Code Organization

#### Controller Pattern

```php
<?php

namespace App\Http\Controllers\Campaign;

use App\Contracts\Campaign\CampaignServiceInterface;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignServiceInterface $campaignService
    ) {}

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = $this->campaignService->create(
            $request->validated()
        );

        return response()->json($campaign, 201);
    }
}
```

#### Service Pattern

```php
<?php

namespace App\Services\Campaign;

use App\Contracts\Campaign\CampaignServiceInterface;
use App\Contracts\Campaign\CampaignRepositoryInterface;
use App\Models\Campaign\Campaign;

class CampaignService implements CampaignServiceInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $repository
    ) {}

    public function create(array $data): Campaign
    {
        // Business logic here
        return $this->repository->create($data);
    }
}
```

#### Model Pattern

```php
<?php

namespace App\Models\Campaign;

use Database\Factories\Campaign\CampaignFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'goal_amount',
    ];

    protected static function newFactory(): CampaignFactory
    {
        return CampaignFactory::new();
    }
}
```

## Conventions

### Interface Binding

Always bind interfaces to implementations in service providers:

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(
        \App\Contracts\Campaign\CampaignServiceInterface::class,
        \App\Services\Campaign\CampaignService::class
    );
}
```

### Dependency Injection

Always use constructor injection for dependencies:

```php
// ✅ GOOD
public function __construct(
    private readonly CampaignServiceInterface $service
) {}

// ❌ BAD
public function store()
{
    $service = app(CampaignService::class);
}
```

### Type Hints

Use type hints for all parameters and return types:

```php
// ✅ GOOD
public function create(array $data): Campaign
{
    return Campaign::create($data);
}

// ❌ BAD
public function create($data)
{
    return Campaign::create($data);
}
```

### Testing Organization

Tests follow the same domain organization:

```php
// Unit test
tests/Unit/Models/CampaignTest.php

// Feature test
tests/Feature/Campaign/CampaignStoreTest.php
```

## Summary

This architecture provides:

✅ **Clear organization** - Easy to find related code
✅ **Scalability** - Simple to add new domains
✅ **Maintainability** - Changes are localized
✅ **Testability** - Clear boundaries for testing
✅ **SOLID compliance** - Clean, maintainable code
✅ **Laravel familiarity** - Recognizable structure

For more information, see:
- [Testing Guide](TESTING.md) - How to test this architecture
- [Notification Documentation](NOTIFICATION.md) - Example of SOLID implementation
- [Main README](../README.md) - Project overview
