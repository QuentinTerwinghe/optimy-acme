# Project Documentation

This directory contains detailed documentation for the ACME Corp Laravel application, including architecture, features, testing, and implementation guides.

## Available Documentation

### Architecture & Development

- **[Architecture Guide](ARCHITECTURE.md)** - Project structure and design principles:
  - Pseudo-DDD organization
  - SOLID principles implementation
  - Domain organization patterns
  - Folder structure conventions
  - Code organization best practices

- **[SOLID Principles Assessment](SOLID.md)** - Comprehensive SOLID compliance analysis (87.6% A-):
  - Detailed principle-by-principle evaluation
  - Individual scores and grades
  - Code examples demonstrating best practices
  - Domain organization assessment
  - Architecture patterns used (Strategy, Registry, Repository, Service Layer)
  - Priority recommendations for improvement

### Testing & Quality Assurance

- **[Testing Guide](TESTING.md)** - Comprehensive testing documentation:
  - Running tests with Make commands (193 passing tests)
  - Writing unit and feature tests with Pest PHP
  - Using factories for test data
  - Code coverage and best practices
  - Test organization by domain
  - CI/CD integration

### Feature Documentation

#### Notification System

- **[Notification Service](NOTIFICATION.md)** - Notification system architecture:
  - SOLID-compliant design using Strategy and Registry patterns
  - Creating and registering new notification handlers
  - RabbitMQ integration for asynchronous processing
  - Testing notification handlers
  - Error handling and troubleshooting

#### Authentication & Password Management

- **[Forgot Password Flow](FORGOT_PASSWORD_USAGE.md)** - Password reset implementation:
  - Using the ForgotPasswordHandler
  - Synchronous and asynchronous implementations
  - Token generation and security
  - Email templates and configuration
  - Testing password reset flows

## Quick Links

### Getting Started

1. **Understanding the architecture**: See [ARCHITECTURE.md](ARCHITECTURE.md)
2. **SOLID principles analysis**: See [SOLID.md](SOLID.md) - 87.6% compliance score
3. **Running tests**: See [TESTING.md](TESTING.md#running-tests)
4. **Writing new tests**: See [TESTING.md](TESTING.md#writing-tests)
5. **Sending notifications**: See [NOTIFICATION.md](NOTIFICATION.md#usage)
6. **Creating notification types**: See [NOTIFICATION.md](NOTIFICATION.md#step-2-create-a-notification-handler)
7. **Password reset implementation**: See [FORGOT_PASSWORD_USAGE.md](FORGOT_PASSWORD_USAGE.md#usage-example)

### Common Tasks

#### Adding a New Feature Domain

1. Create domain folder structure within each technical layer
2. Follow the Pseudo-DDD pattern (see [ARCHITECTURE.md](ARCHITECTURE.md))
3. Write corresponding tests (unit + feature)
4. Ensure all tests pass before committing

#### Testing Your Code

```bash
# Run all tests (193 tests should pass)
make test

# Run specific test suite
make test-unit      # Unit tests only
make test-feature   # Feature tests only

# Run with coverage
make test-coverage

# Run static analysis
make phpstan
```

## Project Statistics

- **Total Tests**: 193 passing
- **Total Assertions**: 510
- **PHPStan Level**: 9
- **Code Coverage**: Comprehensive coverage of models, services, and features
- **Laravel Version**: 12.x LTS
- **PHP Version**: 8.3

## Architecture Principles

All features in this project follow **SOLID principles** and **Pseudo-DDD** organization:

### SOLID Principles

- **Single Responsibility**: Each class has one reason to change
- **Open/Closed**: Open for extension, closed for modification
- **Liskov Substitution**: Implementations are substitutable for their interfaces
- **Interface Segregation**: Specific interfaces over general-purpose ones
- **Dependency Inversion**: Depend on abstractions, not concretions

### Pseudo-DDD Organization

- Technical layers first (Controllers, Services, Models, etc.)
- Domain folders within each layer (Campaign, Auth, Notifications, etc.)
- Clear separation of concerns
- Scalable and maintainable structure

See [ARCHITECTURE.md](ARCHITECTURE.md) for detailed information.
