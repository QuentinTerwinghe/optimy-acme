# Docker Setup for Optimy

This document explains how to set up and run the Optimy Laravel application using Docker.

## Prerequisites

- Docker Desktop installed (includes Docker and Docker Compose)
- Git (for cloning the repository)

## Services Included

The Docker setup includes the following services:

1. **App** - Laravel 12.x application with Apache and PHP 8.3
2. **MySQL 9.1** - Database server (with persistent volume)
3. **RabbitMQ 4.0** - Message broker for queues
4. **MeiliSearch 1.11** - Search engine (with persistent volume)
5. **MailCatcher** - Email testing tool

## Ports Configuration

The following ports are mapped from your local machine to Docker containers:

- **9481** - Laravel Application (Apache)
- **34928** - MySQL Database
- **1786** - MailCatcher Web Interface
- **17980** - MailCatcher SMTP
- **5672** - RabbitMQ AMQP
- **15672** - RabbitMQ Management Interface
- **7700** - MeiliSearch

## Quick Start

### 1. Configure Environment Variables

Copy the `.env.docker.example` file and adjust values if needed:

```bash
cp .env.docker.example .env
```

**Important:** Update the following values in `.env` before running in production:
- `MYSQL_ROOT_PASSWORD`
- `MYSQL_PASSWORD`
- `RABBITMQ_DEFAULT_PASS`
- `MEILISEARCH_MASTER_KEY`

### 2. Build and Start Containers

```bash
# Build the images
docker-compose build

# Start all services
docker-compose up -d
```

### 3. Verify Services

Check that all services are running:

```bash
docker-compose ps
```

You should see all services with status "Up" or "healthy".

### 4. Access the Application

- **Laravel Application**: http://localhost:9481
- **MailCatcher**: http://localhost:1786
- **RabbitMQ Management**: http://localhost:15672 (login: optimy / optimy_password_change_in_production)
- **MeiliSearch**: http://localhost:7700

## Common Commands

### Start/Stop Services

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Stop and remove volumes (WARNING: deletes all data)
docker-compose down -v
```

### View Logs

```bash
# View all logs
docker-compose logs

# View specific service logs
docker-compose logs app
docker-compose logs mysql
docker-compose logs rabbitmq
docker-compose logs meilisearch
docker-compose logs mailcatcher

# Follow logs in real-time
docker-compose logs -f app
```

### Laravel Artisan Commands

```bash
# Run artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan tinker
docker-compose exec app php artisan make:model Post -m
docker-compose exec app php artisan queue:work

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Composer Commands

```bash
# Install dependencies
docker-compose exec app composer install

# Update dependencies
docker-compose exec app composer update

# Require new package
docker-compose exec app composer require vendor/package
```

### Database Access

```bash
# Access MySQL CLI
docker-compose exec mysql mysql -u optimy -p optimy

# Run MySQL commands from host
mysql -h 127.0.0.1 -P 34928 -u optimy -p optimy

# Backup database
docker-compose exec mysql mysqldump -u optimy -p optimy > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u optimy -p optimy < backup.sql
```

### Shell Access

```bash
# Access app container shell
docker-compose exec app bash

# Access MySQL container shell
docker-compose exec mysql bash

# Access RabbitMQ container shell
docker-compose exec rabbitmq sh
```

## Development Workflow

### 1. Code Changes

All code in the `www/` directory is mounted as a volume, so changes are reflected immediately without rebuilding.

### 2. Running Migrations

```bash
docker-compose exec app php artisan migrate
```

### 3. Running Tests

```bash
docker-compose exec app php artisan test
```

### 4. Queue Workers

To process queued jobs with RabbitMQ:

```bash
docker-compose exec app php artisan queue:work rabbitmq
```

### 5. Email Testing

All emails sent by the application are caught by MailCatcher. View them at http://localhost:1786.

## Persistent Data

The following data is persisted in Docker volumes:

- **mysql_data** - MySQL database files
- **meilisearch_data** - MeiliSearch indexes
- **rabbitmq_data** - RabbitMQ messages and configuration

To completely reset the application:

```bash
# WARNING: This deletes all data
docker-compose down -v
docker-compose up -d
```

## Directory Structure

```
.
├── .docker/                    # Docker configuration files
│   ├── apache/                 # Apache configuration
│   │   ├── vhost.conf          # Virtual host configuration
│   │   ├── apache2.conf        # Main Apache config
│   │   └── entrypoint.sh       # Container startup script
│   ├── mysql/                  # MySQL configuration
│   │   ├── my.cnf              # MySQL configuration
│   │   └── init.sql            # Database initialization
│   └── php/                    # PHP configuration
│       ├── php.ini             # PHP settings
│       └── opcache.ini         # OPcache settings
├── www/                        # Laravel application
├── .env.docker.example         # Docker environment variables
├── docker-compose.yml          # Docker services definition
├── Dockerfile                  # Application container image
└── DOCKER.md                   # This file
```

## Troubleshooting

### Services Won't Start

```bash
# Check logs for errors
docker-compose logs

# Rebuild from scratch
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### Permission Issues

```bash
# Fix Laravel storage permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 755 /var/www/html/storage
```

### MySQL Connection Issues

```bash
# Verify MySQL is healthy
docker-compose ps mysql

# Check MySQL logs
docker-compose logs mysql

# Test connection
docker-compose exec app php artisan migrate:status
```

### Port Already in Use

If a port is already in use, edit `.env` to change the port mapping:

```
APP_PORT=9482  # Change from 9481 to 9482
```

Then restart services:

```bash
docker-compose down
docker-compose up -d
```

## Production Considerations

Before deploying to production:

1. **Security**
   - Change all default passwords in `.env`
   - Use strong passwords for database and services
   - Disable debug mode in `www/.env`
   - Review Apache and PHP security settings

2. **Performance**
   - Adjust PHP memory limits in `.docker/php/php.ini`
   - Configure MySQL buffer sizes in `.docker/mysql/my.cnf`
   - Enable OPcache in production
   - Use Redis for cache and sessions (add to docker-compose.yml)

3. **Backups**
   - Set up automated database backups
   - Back up persistent volumes regularly
   - Test restore procedures

4. **Monitoring**
   - Add logging aggregation (ELK stack, etc.)
   - Monitor container health
   - Set up alerts for service failures

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Documentation](https://laravel.com/docs/12.x)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [RabbitMQ Documentation](https://www.rabbitmq.com/docs)
- [MeiliSearch Documentation](https://www.meilisearch.com/docs)
