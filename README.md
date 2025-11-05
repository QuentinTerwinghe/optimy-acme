# Optimy - Laravel 12.x LTS Project

This is a Laravel 12.x LTS project running on PHP 8.3.

## Project Structure

```
optimy/
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
└── README.md            # This file
```

## Requirements

- PHP 8.2 or higher (recommended: PHP 8.3)
- Composer
- SQLite (default) or other database
- Node.js & NPM (for frontend assets)

## Installation

The Laravel application is already set up in the `www` directory. If you need to reinstall dependencies:

```bash
cd www
composer install
```

## Configuration

1. The `.env` file has been created with default settings
2. Application key has been generated
3. Default database is SQLite (created at `www/database/database.sqlite`)
4. Database migrations have been run

## Development

### Start Development Server

```bash
cd www
php artisan serve
```

The application will be available at `http://localhost:8000`

### Run Tests

```bash
cd www
php artisan test
```

### Code Quality

```bash
cd www
./vendor/bin/pint    # Code style fixer
```

### Database Operations

```bash
cd www
php artisan migrate              # Run migrations
php artisan migrate:fresh        # Fresh migration
php artisan migrate:fresh --seed # Fresh migration with seeders
php artisan tinker               # REPL console
```

## Docker Setup

Docker configuration will be added later. For now, use PHP's built-in server or configure your local environment.

## Laravel Version

This project uses Laravel 12.x LTS (Long Term Support), which provides:
- Extended support and bug fixes
- Security updates
- Stability for production applications

## Documentation

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Laravel API Documentation](https://laravel.com/api/12.x)

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
