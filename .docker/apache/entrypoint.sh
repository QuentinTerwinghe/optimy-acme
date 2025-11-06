#!/bin/bash
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
until mysql -h"${DB_HOST}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --skip-ssl -e "SELECT 1" &> /dev/null; do
    echo "MySQL is unavailable - sleeping"
    sleep 2
done

echo "MySQL is up - executing command"

# Note: Migrations are handled by Makefile commands (make setup, make migrate, etc.)
# Do not run migrations automatically here to avoid race conditions

# Execute the main container command
exec "$@"
