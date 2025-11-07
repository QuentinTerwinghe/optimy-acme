include .env

#silent by default
ifndef VERBOSE
.SILENT:
endif

ifeq (run,$(firstword $(MAKECMDGOALS)))
	RUN_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
	$(eval $(RUN_ARGS):;@:)
endif

default:
	$(call do_start)

key:
	$(call generate_app_key)

setup:
	$(call do_setup)

start:
	$(call do_start)

restart:
	$(call do_restart)

rebuild:
	$(call do_rebuild)

stop:
	$(call do_stop)

destroy:
	$(call do_destroy)

update:
	$(call do_update)

cc:
	$(call do_cc)

migrate:
	$(call do_db_migrate)

migrate-fresh:
	$(call do_db_migrate_fresh)

migrate-rollback:
	$(call do_db_migrate_rollback)

seed:
	$(call do_db_seed)

import-data:
	$(call do_import_data)

artisan:
	@printf "Executing Artisan command\n"
	@printf "\e[36mphp artisan %s\e[0m\n" "${C}"
	$(call exec, "php artisan ${C}")

front:
	$(call do_build_front)

yarn:
	@printf "Executing Yarn command\n"
	@printf "\e[36myarn %s\e[0m\n" "${C}"
	$(call exec, "yarn ${C}")

composer:
	@printf "Executing Composer command...\n"
	@printf "\e[36mcomposer %s\e[0m\n" "${C}"
	$(call exec, "composer ${C}")

ssh:
	docker exec -it ${APP_NAME}_app bash

phpstan:
	$(call do_phpstan)

phpstan-baseline:
	$(call do_phpstan_baseline)

test:
	$(call do_test)

test-unit:
	$(call do_test_unit)

test-feature:
	$(call do_test_feature)

test-coverage:
	$(call do_test_coverage)

test-filter:
	$(call do_test_filter)

pest:
	$(call do_pest)

help:
	$(call do_display_commands)

info:
	$(call do_display_app_info)

define do_docker
	@printf "Building docker for %s...\n" "${APP_NAME}"
	docker compose build
	docker compose up -d --force-recreate
endef

define generate_app_key
	@printf "Generating Laravel application key...\n"
	$(call exec, "php artisan key:generate")
endef

define do_setup
	@printf "Setting up %s...\n" "${APP_NAME}"
	$(call do_docker)
	$(call exec, "composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader")
	$(call exec, "php artisan key:generate")
	@printf "Setting up testing environment...\n"
	$(call exec, "cp -n .env.testing.example .env.testing || true")
	$(call exec, "php artisan key:generate --env=testing")
	$(call do_build_front)
	$(call do_db_migrate_fresh)
	$(call do_cc)
	$(call do_db_seed)

	@printf "\n"
	@printf "\e[42m%s setup completed\e[0m\n" "${APP_NAME}"
	$(call do_display_app_info)
endef

define do_import_data
	@printf "Import data from %s...\n" "${APP_NAME}"
	$(call exec, "php artisan db:seed")

	@printf "\n"
	@printf "\e[42m%s data import completed\e[0m\n" "${APP_NAME}"
endef

define do_start
	@printf "Starting %s...\n" "${APP_NAME}"
	docker compose up -d
	$(call do_cc)

	@printf "\n"
	@printf "\e[42m%s start completed\e[0m\n" "${APP_NAME}"
	$(call do_display_app_info)
endef

define do_restart
	@printf "Restarting %s...\n" "${APP_NAME}"
	docker compose down
	docker compose up -d
	$(call do_cc)
	@printf "\n"
	@printf "\e[42m%s restarted\e[0m\n" "${APP_NAME}"
	$(call do_display_app_info)
endef

define do_rebuild
	@printf "Rebuilding %s...\n" "${APP_NAME}"
	$(call do_stop)
	$(call do_docker)
	$(call do_cc)

	@printf "\n"
	@printf "\e[42m%s start completed\e[0m\n" "${APP_NAME}"
	$(call do_display_app_info)
endef

define do_update
	@printf "Updating %s...\n" "${APP_NAME}"
	$(call exec, "composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader")
	$(call do_build_front)
	$(call do_db_migrate)
	$(call do_cc)

	@printf "\n"
	@printf "\e[42m%s update completed\e[0m\n" "${APP_NAME}"
	$(call do_display_app_info)
endef

define do_stop
	@printf "Stopping %s...\n" "${APP_NAME}"
	docker compose down
	@printf "\n"
	@printf "\e[42m%s stopped\e[0m\n" "${APP_NAME}"
endef

define do_destroy
	@printf "Destroying %s...\n" "${APP_NAME}"
	docker compose down --volumes
	@printf "\n"
	@printf "\e[42m%s stopped and data deleted\e[0m\n" "${APP_NAME}"
endef

define do_build_front
	@printf "Building front for %s...\n" "${APP_NAME}"
	cd www && npm install && npm run build
endef

define do_cc
	@printf "Clearing %s caches...\n" "${APP_NAME}"
	$(call exec, "php artisan optimize:clear")
	$(call exec, "php artisan config:cache")
	$(call exec, "php artisan route:cache")
	$(call exec, "php artisan view:cache")
endef

define do_db_migrate
	@printf "Running %s migrations...\n" "${APP_NAME}"
	$(call exec, "php artisan migrate --force")
endef

define do_db_migrate_fresh
	@printf "Fresh migration %s DB...\n" "${APP_NAME}"
	$(call exec, "php artisan migrate:fresh --force")
endef

define do_db_migrate_rollback
	@printf "Rolling back %s DB migrations...\n" "${APP_NAME}"
	$(call exec, "php artisan migrate:rollback")
endef

define do_db_seed
	@printf "Seeding %s DB...\n" "${APP_NAME}"
	$(call exec, "php artisan db:seed --force")
endef

define do_phpstan
	@printf "Running PHPStan analysis...\n"
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "./vendor/bin/phpstan analyse"
endef

define do_phpstan_baseline
	@printf "Generating PHPStan baseline...\n"
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "./vendor/bin/phpstan analyse --generate-baseline --allow-empty-baseline"
endef

define do_test
	@printf "Running all tests...\n"
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "php artisan test"
endef

define do_test_unit
	@printf "Running unit tests...\n"
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "php artisan test --testsuite=Unit"
endef

define do_test_feature
	@printf "Running feature tests...\n"
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "php artisan test --testsuite=Feature"
endef

define do_test_coverage
	@printf "Running tests with coverage...\n"
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "./vendor/bin/pest --coverage --min=80"
endef

define do_test_filter
	@printf "Running filtered tests: \e[36m%s\e[0m\n" "${F}"
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "php artisan test --filter='${F}'"
endef

define do_pest
	@printf "Running Pest tests...\n"
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "./vendor/bin/pest ${ARGS}"
endef

define exec
	docker exec -w /var/www/html -it ${APP_NAME}_app bash -c ${1}
endef

define do_display_app_info
	@printf "\n"
	@printf "\e[1m%s\e[0m\n" "--- ${APP_NAME} APP INFO ---"
	@printf "\n"
	@printf "APP URL: \e[36mhttp://localhost:%s\e[0m\n" "${APP_PORT}"
	@printf "Database port: \e[36m%s\e[0m\n" "${DATABASE_PORT}"
	@printf "MailCatcher: \e[36mhttp://localhost:%s\e[0m\n" "${MAILCATCHER_PORT}"
	@printf "RabbitMQ Management: \e[36mhttp://localhost:%s\e[0m\n" "${RABBITMQ_MANAGEMENT_PORT}"
endef

define do_display_commands
	@printf "\n"
	@printf "%s\n" "--- AVAILABLE COMMANDS ---"
	@printf "\n"
	@printf "Setup the local development environment for %s: \e[36mmake \e[0m\e[1msetup\e[0m\n" "${APP_NAME}"
	@printf "Start an app that has already been setup: \e[36mmake \e[0m\e[1mstart\e[0m\n"
	@printf "Restart an app that has already been setup: \e[36mmake \e[0m\e[1mrestart\e[0m\n"
	@printf "Rebuild an app that has already been setup: \e[36mmake \e[0m\e[1mrebuild\e[0m\n"
	@printf "Stop the running app: \e[36mmake \e[0m\e[1mstop\e[0m\n"
	@printf "Stop the running app and delete the data: \e[36mmake \e[0m\e[1mdestroy\e[0m\n"
	@printf "Update the Laravel installation: \e[36mmake \e[0m\e[1mupdate\e[0m\n"
	@printf "Build the front assets: \e[36mmake \e[0m\e[1mfront\e[0m\n"
	@printf "Run database migrations: \e[36mmake \e[0m\e[1mmigrate\e[0m\n"
	@printf "Fresh database migration: \e[36mmake \e[0m\e[1mmigrate-fresh\e[0m\n"
	@printf "Rollback last migration: \e[36mmake \e[0m\e[1mmigrate-rollback\e[0m\n"
	@printf "Seed the database: \e[36mmake \e[0m\e[1mseed\e[0m\n"
	@printf "Clear and rebuild caches: \e[36mmake \e[0m\e[1mcc\e[0m\n"
	@printf "Start a shell session: \e[36mmake \e[0m\e[1mssh\e[0m\n"
	@printf "Execute Artisan command: \e[36mmake \e[0m\e[1martisan C=\"COMMAND\"\e[0m\n"
	@printf "Execute a Composer command: \e[36mmake \e[0m\e[1mcomposer C=\"COMMAND\"\e[0m\n"
	@printf "Execute a Yarn command: \e[36mmake \e[0m\e[1myarn C=\"COMMAND\"\e[0m\n"
	@printf "Generate application key: \e[36mmake \e[0m\e[1mkey\e[0m\n"
	@printf "Run PHPStan static analysis: \e[36mmake \e[0m\e[1mphpstan\e[0m\n"
	@printf "Generate PHPStan baseline: \e[36mmake \e[0m\e[1mphpstan-baseline\e[0m\n"
	@printf "Run all tests: \e[36mmake \e[0m\e[1mtest\e[0m\n"
	@printf "Run unit tests only: \e[36mmake \e[0m\e[1mtest-unit\e[0m\n"
	@printf "Run feature tests only: \e[36mmake \e[0m\e[1mtest-feature\e[0m\n"
	@printf "Run tests with coverage: \e[36mmake \e[0m\e[1mtest-coverage\e[0m\n"
	@printf "Run filtered tests: \e[36mmake \e[0m\e[1mtest-filter F=\"TestName\"\e[0m\n"
	@printf "Run Pest directly: \e[36mmake \e[0m\e[1mpest\e[0m or \e[36mmake \e[0m\e[1mpest ARGS=\"--parallel\"\e[0m\n"
endef
