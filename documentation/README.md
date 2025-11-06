# Project Documentation

This directory contains detailed documentation for various features and services implemented in the ACME Corp application.

## Available Documentation

### Testing & Quality Assurance

- **[Testing Guide](TESTING.md)** - Comprehensive guide to testing with Pest PHP:
  - Running tests with Make commands
  - Writing unit and feature tests
  - Using factories for test data
  - Code coverage and best practices
  - Pest PHP syntax and expectations
  - CI/CD integration

### Notification System

- **[Notification Service](NOTIFICATION.md)** - Complete guide to the notification system architecture, including:
  - SOLID-compliant design using Strategy and Registry patterns
  - How to create and register new notification handlers
  - Integration with RabbitMQ for asynchronous processing
  - Testing and best practices
  - Error handling and troubleshooting

### Authentication & Password Management

- **[Forgot Password Flow](FORGOT_PASSWORD_USAGE.md)** - Implementation guide for the password reset feature:
  - Using the ForgotPasswordHandler notification
  - Synchronous and asynchronous (RabbitMQ) implementations
  - Token generation and security
  - Email templates and configuration
  - Testing examples

## Quick Links

### Notification System Overview

The notification system is designed to be:
- **Extensible**: Easy to add new notification types
- **Maintainable**: Each notification type has its own handler
- **Testable**: Built with SOLID principles and dependency injection
- **Scalable**: Integrates with RabbitMQ for async processing

### Getting Started

1. **To run tests**: See [TESTING.md](TESTING.md#running-tests)
2. **To write new tests**: See [TESTING.md](TESTING.md#writing-tests)
3. **To send a notification**: See [NOTIFICATION.md](NOTIFICATION.md#usage)
4. **To create a new notification type**: See [NOTIFICATION.md](NOTIFICATION.md#step-2-create-a-notification-handler)
5. **To implement forgot password**: See [FORGOT_PASSWORD_USAGE.md](FORGOT_PASSWORD_USAGE.md#usage-example)

## Architecture Principles

All features in this project follow SOLID principles:

- **Single Responsibility**: Each class has one reason to change
- **Open/Closed**: Open for extension, closed for modification
- **Liskov Substitution**: Implementations are substitutable for their interfaces
- **Interface Segregation**: Specific interfaces over general-purpose ones
- **Dependency Inversion**: Depend on abstractions, not concretions
