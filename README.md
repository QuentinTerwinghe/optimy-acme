# ACME Corp - Laravel 12.x LTS Project

This is a Laravel 12.x LTS project running on PHP 8.3 with Docker support.

## Project Structure

```text
root/
├── .docker/              # Docker configuration files
├── www/                  # Laravel application root
│   ├── app/             # Application code
│   ├── bootstrap/       # Framework bootstrap
│   ├── config/          # Configuration files
│   ├── database/        # Migrations, seeders, factories
│   ├── public/          # Public web root
│   ├── resources/       # Views, assets
│   ├── routes/          # Route definitions
│   ├── storage/         # Logs, cache, uploads
│   ├── tests/           # Test files
│   └── vendor/          # Composer dependencies
├── docker-compose.yml   # Docker services configuration
├── Makefile            # Make commands for easy Docker management
└── README.md           # This file
```

## Requirements

### For Docker Setup (Recommended)

- Docker
- Docker Compose
- Make

### For Local Development (Without Docker)

- PHP 8.2 or higher (recommended: PHP 8.3)
- Composer
- MySQL or SQLite
- Node.js & NPM (for frontend assets)

## Installation & Setup

### Docker Setup (Recommended)

1. **Copy the environment file**

   ```bash
   cp .env.docker.example .env
   ```

2. **Run the setup command**

   ```bash
   make setup
   ```

   This single command will:
   - Build and start all Docker containers (Laravel app, MySQL, RabbitMQ, MeiliSearch, MailCatcher)
   - Install Composer dependencies
   - Generate Laravel application key
   - Run database migrations
   - Seed the database
   - Clear and rebuild caches
   - Display application info and available commands

3. **Access your application**

   After setup completes, you'll see the URLs for all services:

   - **Application**: <http://localhost:9481>
   - **MailCatcher**: <http://localhost:1786>
   - **RabbitMQ Management**: <http://localhost:15672>
   - **MeiliSearch**: <http://localhost:7855>

### Local Development Setup (Without Docker)

If you prefer to run without Docker:

```bash
cd www
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

The application will be available at <http://localhost:8000>

## Docker Services

The Docker setup includes:

- **app**: Laravel application with PHP 8.3 and Apache
- **mysql**: MySQL 9.1 database server
- **rabbitmq**: RabbitMQ 4.0 message broker with management UI
- **meilisearch**: MeiliSearch 1.11 search engine
- **mailcatcher**: Email testing tool

## Make Commands

All Docker operations are managed through convenient Make commands:

### Essential Commands

| Command | Description |
|---------|-------------|
| `make setup` | Initial setup - builds containers, installs dependencies, runs migrations |
| `make start` | Start all containers (for already setup projects) |
| `make stop` | Stop all running containers |
| `make restart` | Restart all containers |
| `make ssh` | Open a bash shell in the app container |

### Development Commands

| Command | Description |
|---------|-------------|
| `make artisan C="command"` | Run any Artisan command (e.g., `make artisan C="make:model Post"`) |
| `make composer C="command"` | Run Composer command (e.g., `make composer C="require package"`) |
| `make yarn C="command"` | Run Yarn command |
| `make cc` | Clear and rebuild all Laravel caches |
| `make front` | Build frontend assets |

### Database Commands

| Command | Description |
|---------|-------------|
| `make migrate` | Run database migrations |
| `make migrate-fresh` | Drop all tables and re-run migrations |
| `make migrate-rollback` | Rollback the last migration |
| `make seed` | Seed the database |
| `make import-data` | Import data (runs seeders) |

### Maintenance Commands

| Command | Description |
|---------|-------------|
| `make rebuild` | Rebuild containers from scratch |
| `make update` | Update dependencies, run migrations, rebuild caches |
| `make destroy` | Stop containers and delete all data (volumes) |
| `make key` | Generate Laravel application key |
| `make info` | Display application URLs and information |
| `make help` | Display all available commands |

### Command Examples

```bash
# Create a new controller
make artisan C="make:controller UserController"

# Run migrations
make migrate

# Install a package
make composer C="require laravel/sanctum"

# Open a shell in the container
make ssh

# Clear all caches
make cc

# Run tests
make artisan C="test"
```

## Development Workflow

### Using Docker (Recommended)

1. **Start your work session**

   ```bash
   make start
   ```

2. **Make your code changes** in the `www` directory

3. **Run Artisan commands**

   ```bash
   make artisan C="migrate"
   make artisan C="test"
   ```

4. **Access the container shell if needed**

   ```bash
   make ssh
   ```

5. **Stop when done**

   ```bash
   make stop
   ```

### Without Docker

```bash
cd www
php artisan serve     # Start server
php artisan test      # Run tests
php artisan migrate   # Run migrations
./vendor/bin/pint    # Code style fixer
```

## Laravel Version

This project uses Laravel 12.x LTS (Long Term Support), which provides:
- Extended support and bug fixes
- Security updates
- Stability for production applications

## Project Features

This project includes the following implemented features:

### Notification System

- Flexible, SOLID-compliant notification service using Strategy and Registry patterns
- Support for multiple notification types (email, SMS, etc.)
- RabbitMQ integration for asynchronous processing
- See [Notification Documentation](documentation/NOTIFICATION.md)

### Authentication Features

- Forgot password flow with email notifications
- Secure token generation and validation
- Customizable email templates
- See [Forgot Password Documentation](documentation/FORGOT_PASSWORD_USAGE.md)

### Infrastructure

- Docker-based development environment
- MySQL 9.1 database
- RabbitMQ 4.0 message broker
- MeiliSearch 1.11 search engine
- MailCatcher for email testing
- Queue worker for background job processing

For detailed implementation guides and examples, see the [documentation](documentation/) directory.

## Documentation

### Project Documentation

- [Project Features & Implementation Guides](documentation/) - Detailed guides for implemented features

### Laravel Documentation

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Laravel API Documentation](https://laravel.com/api/12.x)

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
