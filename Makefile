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
	echo -e 'Executing Artisan command'
	echo -e '\e[36mphp artisan ${C}\e[0m'
	$(call exec, "php artisan ${C}")

front:
	$(call do_build_front)

yarn:
	echo -e 'Executing Yarn command'
	echo -e '\e[36myarn ${C}\e[0m'
	$(call exec, "yarn ${C}")

composer:
	echo -e 'Executing Composer command...'
	echo -e '\e[36mcomposer ${C}\e[0m'
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
	echo -e 'Building docker for ${APP_NAME}...'
	docker compose build
	docker compose up -d --force-recreate
endef

define generate_app_key
	echo -e 'Generating Laravel application key...'
	$(call exec, "php artisan key:generate")
endef

define do_setup
	echo -e 'Setting up ${APP_NAME}...'
	$(call do_docker)
	$(call exec, "composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader")
	$(call exec, "php artisan key:generate")
	$(call do_build_front)
	$(call do_db_migrate_fresh)
	$(call do_db_seed)
	$(call do_cc)

	echo -e '\n'
	echo -e '\e[42m${APP_NAME} setup completed\e[0m'
	$(call do_display_app_info)
	$(call do_display_commands)
endef

define do_import_data
	echo -e 'Import data from ${APP_NAME}...'
	$(call exec, "php artisan db:seed")

	echo -e '\n'
	echo -e '\e[42m${APP_NAME} data import completed\e[0m'
endef

define do_start
	echo -e 'Starting ${APP_NAME}...'
	docker compose up -d
	$(call do_cc)

	echo -e '\n'
	echo -e '\e[42m${APP_NAME} start completed\e[0m'
	$(call do_display_app_info)
	$(call do_display_commands)
endef

define do_restart
	echo -e 'Restarting ${APP_NAME}...'
	docker compose down
	docker compose up -d
	$(call do_cc)
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} restarted\e[0m'
	$(call do_display_app_info)
	$(call do_display_commands)
endef

define do_rebuild
	echo -e 'Rebuilding ${APP_NAME}...'
	$(call do_stop)
	$(call do_docker)
	$(call do_cc)

	echo -e '\n'
	echo -e '\e[42m${APP_NAME} start completed\e[0m'
	$(call do_display_app_info)
	$(call do_display_commands)
endef

define do_update
	echo -e 'Updating ${APP_NAME}...'
	$(call exec, "composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader")
	$(call do_build_front)
	$(call do_db_migrate)
	$(call do_cc)

	echo -e '\n'
	echo -e '\e[42m${APP_NAME} update completed\e[0m'
	$(call do_display_app_info)
	$(call do_display_commands)
endef

define do_stop
	echo -e 'Stopping ${APP_NAME}...'
	docker compose down
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} stopped\e[0m'
endef

define do_destroy
	echo -e 'Destroying ${APP_NAME}...'
	docker compose down --volumes
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} stopped and data deleted\e[0m'
endef

define do_build_front
	echo -e 'Building front for ${APP_NAME}...'
	cd www && npm install && npm run build
endef

define do_cc
	echo -e 'Clearing ${APP_NAME} caches...'
	$(call exec, "php artisan optimize:clear")
	$(call exec, "php artisan config:cache")
	$(call exec, "php artisan route:cache")
	$(call exec, "php artisan view:cache")
endef

define do_db_migrate
	echo -e 'Running ${APP_NAME} migrations...'
	$(call exec, "php artisan migrate --force")
endef

define do_db_migrate_fresh
	echo -e 'Fresh migration ${APP_NAME} DB...'
	$(call exec, "php artisan migrate:fresh --force")
endef

define do_db_migrate_rollback
	echo -e 'Rolling back ${APP_NAME} DB migrations...'
	$(call exec, "php artisan migrate:rollback")
endef

define do_db_seed
	echo -e 'Seeding ${APP_NAME} DB...'
	$(call exec, "php artisan db:seed --force")
endef

define do_phpstan
	echo -e 'Running PHPStan analysis...'
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "./vendor/bin/phpstan analyse"
endef

define do_phpstan_baseline
	echo -e 'Generating PHPStan baseline...'
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "./vendor/bin/phpstan analyse --generate-baseline --allow-empty-baseline"
endef

define do_test
	echo -e 'Running all tests...'
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "php artisan test"
endef

define do_test_unit
	echo -e 'Running unit tests...'
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "php artisan test --testsuite=Unit"
endef

define do_test_feature
	echo -e 'Running feature tests...'
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "php artisan test --testsuite=Feature"
endef

define do_test_coverage
	echo -e 'Running tests with coverage...'
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "./vendor/bin/pest --coverage --min=80"
endef

define do_test_filter
	echo -e 'Running filtered tests: \e[36m${F}\e[0m'
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "php artisan test --filter='${F}'"
endef

define do_pest
	echo -e 'Running Pest tests...'
	docker exec -w /var/www/html ${APP_NAME}_app bash -c "./vendor/bin/pest ${ARGS}"
endef

define exec
	docker exec -w /var/www/html -it ${APP_NAME}_app bash -c ${1}
endef

define do_display_app_info
	echo -e '\n'
	echo -e '\e[1m--- ${APP_NAME} APP INFO ---\e[0m'
	echo -e '\n'
	echo -e 'APP URL: \e[36mhttp://localhost:${APP_PORT}\e[0m'
	echo -e 'Database port: \e[36m${DATABASE_PORT}\e[0m'
	echo -e 'MailCatcher: \e[36mhttp://localhost:${MAILCATCHER_PORT}\e[0m'
	echo -e 'RabbitMQ Management: \e[36mhttp://localhost:15672\e[0m'
	echo -e 'MeiliSearch: \e[36mhttp://localhost:${MEILISEARCH_PORT}\e[0m'
endef

define do_display_commands
	echo -e '\n'
	echo -e '--- AVAILABLE COMMANDS ---'
	echo -e '\n'
	echo -e 'Setup the local development environment for ${APP_NAME}: \e[36mmake \e[0m\e[1msetup\e[0m'
	echo -e 'Start an app that has already been setup: \e[36mmake \e[0m\e[1mstart\e[0m'
	echo -e 'Restart an app that has already been setup: \e[36mmake \e[0m\e[1mrestart\e[0m'
	echo -e 'Rebuild an app that has already been setup: \e[36mmake \e[0m\e[1mrebuild\e[0m'
	echo -e 'Stop the running app: \e[36mmake \e[0m\e[1mstop\e[0m'
	echo -e 'Stop the running app and delete the data: \e[36mmake \e[0m\e[1mdestroy\e[0m'
	echo -e 'Update the Laravel installation: \e[36mmake \e[0m\e[1mupdate\e[0m'
	echo -e 'Build the front assets: \e[36mmake \e[0m\e[1mfront\e[0m'
	echo -e 'Run database migrations: \e[36mmake \e[0m\e[1mmigrate\e[0m'
	echo -e 'Fresh database migration: \e[36mmake \e[0m\e[1mmigrate-fresh\e[0m'
	echo -e 'Rollback last migration: \e[36mmake \e[0m\e[1mmigrate-rollback\e[0m'
	echo -e 'Seed the database: \e[36mmake \e[0m\e[1mseed\e[0m'
	echo -e 'Clear and rebuild caches: \e[36mmake \e[0m\e[1mcc\e[0m'
	echo -e 'Start a shell session: \e[36mmake \e[0m\e[1mssh\e[0m'
	echo -e 'Execute Artisan command: \e[36mmake \e[0m\e[1martisan C="COMMAND"\e[0m'
	echo -e 'Execute a Composer command: \e[36mmake \e[0m\e[1mcomposer C="COMMAND"\e[0m'
	echo -e 'Execute a Yarn command: \e[36mmake \e[0m\e[1myarn C="COMMAND"\e[0m'
	echo -e 'Generate application key: \e[36mmake \e[0m\e[1mkey\e[0m'
	echo -e 'Run PHPStan static analysis: \e[36mmake \e[0m\e[1mphpstan\e[0m'
	echo -e 'Generate PHPStan baseline: \e[36mmake \e[0m\e[1mphpstan-baseline\e[0m'
	echo -e 'Run all tests: \e[36mmake \e[0m\e[1mtest\e[0m'
	echo -e 'Run unit tests only: \e[36mmake \e[0m\e[1mtest-unit\e[0m'
	echo -e 'Run feature tests only: \e[36mmake \e[0m\e[1mtest-feature\e[0m'
	echo -e 'Run tests with coverage: \e[36mmake \e[0m\e[1mtest-coverage\e[0m'
	echo -e 'Run filtered tests: \e[36mmake \e[0m\e[1mtest-filter F="TestName"\e[0m'
	echo -e 'Run Pest directly: \e[36mmake \e[0m\e[1mpest\e[0m or \e[36mmake \e[0m\e[1mpest ARGS="--parallel"\e[0m'
endef
