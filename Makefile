# By default, Makefiles are executed with /bin/sh, which may not support certain
# features like `$(shell ...)` or `$(if ...)`. To ensure compatibility, we
# explicitly set the shell to bash.
SHELL := /bin/bash

# Enable the `.ONESHELL` feature, which allows all commands in a recipe to be
# executed in the same shell instance. This is useful for maintaining state
# across commands, such as variable assignments or conditional checks.
.ONESHELL:

# Set bash shell flags for strict error handling
# -e: Exit immediately if a command exits with a non-zero status.
# -u: Treat unset variables as an error and exit immediately.
# -o pipefail: Make pipelines (e.g. `printenv | sort` ) fail if any command in the pipeline fails.
# -c: read the command from the following string (required).
.SHELLFLAGS := -euo pipefail -c

.DEFAULT_GOAL := build/.install
.WAIT:

# Detect operating system
OS := $(shell uname -s)

_WARN := "\033[33m%s\033[0m %s\n"  # Yellow text template for "printf"
_INFO := "\033[32m%s\033[0m %s\n" # Green text template for "printf"
_ERROR := "\033[31m%s\033[0m %s\n" # Red text template for "printf"

##------------------------------------------------------------------------------
# Command Aliases & Function/Variable Definitions
##------------------------------------------------------------------------------

docker-php = docker compose run --rm --user=$$(id -u):$$(id -g) php
docker-run = docker run --rm --env-file "$${PWD}/.env" --user=$$(id -u):$$(id -g)
aws-cli := $(docker-run) --volume $${PWD}:/aws amazon/aws-cli

# Define behavior to safely source file (1) to dist file (2), without overwriting
# if the dist file already exists. This is more portable than using `cp --no-clobber`.
define copy-safe
	if [ ! -f "$(2)" ]; then \
		echo "Copying $(1) to $(2)"; \
		cp "$(1)" "$(2)"; \
	else \
		echo "$(2) already exists, not overwriting."; \
	fi
endef

# Define behavior to check if a token (1) is set in .env, and prompt user to set it if not.
# If the token is already set, inform the user. If the token name is not found in .env,
# it will be appended, otherwise, the existing value will be updated.
define check-token
	if grep -q "^$(1)=" ".env"; then \
		TOKEN_VALUE=$$(grep "^$(1)=" ".env" | cut -d'=' -f2); \
		if [ -z "$$TOKEN_VALUE" ]; then \
			read -p "Please enter your $(1): " NEW_TOKEN; \
			sed -i "s/^$(1)=.*/$(1)=$$NEW_TOKEN/" ".env"; \
			echo "$(1) updated successfully!"; \
		else \
			echo "$(1) is already set."; \
		fi; \
	else \
		read -p "$(1) not found. Please enter your $(1): " NEW_TOKEN; \
		echo -e "\n$(1)=$$NEW_TOKEN" >> ".env"; \
		echo "$(1) added successfully!"; \
	fi
endef

# Define behavior to generate a new 256-bit key for the application, if it is
# not already set in the .env file.
define generate-key
	if grep -q "^$(1)=" ".env"; then \
		KEY_VALUE=$$(grep "^$(1)=" ".env" | cut -d'=' -f2); \
		if [ -z "$$KEY_VALUE" ]; then \
			NEW_KEY=$$(head -c 32 /dev/urandom | base64); \
			sed -i "s;^$(1)=.*;$(1)=$$NEW_KEY;" ".env"; \
			echo "New $(1) generated successfully!"; \
		else \
			echo "$(1) is already set."; \
		fi; \
	else \
		NEW_KEY=$$(head -c 32 /dev/urandom | base64); \
		echo -e "$(1)=$$NEW_KEY" >> ".env"; \
		echo "New $(1) generated successfully!"; \
	fi
endef

define confirm
	printf -v PROMPT $(_WARN) $(1)  [y/N];
	read -p "$$PROMPT" CONFIRMATION;
	if [[ ! "$$CONFIRMATION" =~ ^[Yy] ]]; then \
		echo "Exiting..."; \
		exit 1; \
	fi
endef

BUILD_DIRS = build \
	build/.phpunit.cache \
	build/composer \
	build/docker \
	build/phpstan \
	build/phpunit \
	build/psysh/config \
	build/psysh/data \
	build/psysh/tmp \
	build/rector \
	build/xdebug

BUILD_STAMP := build/.install
MIGRATIONS_STAMP := build/.migrations
COMPOSER_STAMP := build/.composer
DOCKER_STAMP := build/.docker

##------------------------------------------------------------------------------
# Docker Targets
##------------------------------------------------------------------------------
DOCKER_PLATFORM ?= linux/amd64
DOCKER_COMPOSE_FILE := compose.yaml
DOCKER_BAKE_OPTIONS ?=
ifeq ($(shell uname -s),Darwin)
  DOCKER_UID ?= 1000
  DOCKER_GID ?= 1000
else
  DOCKER_UID ?= $(shell id -u)
  DOCKER_GID ?= $(shell id -g)
endif

$(DOCKER_STAMP): $(DOCKER_COMPOSE_FILE) Dockerfile | .env $(BUILD_DIRS)
	docker pull ghcr.io/phoneburner/pinch-prettier
	docker pull redocly/cli
	docker compose pull --quiet --ignore-buildable
	docker buildx bake --pull --load \
		--file $(DOCKER_COMPOSE_FILE) \
		--metadata-file $(DOCKER_STAMP) \
		--set php.platform=$(DOCKER_PLATFORM) \
		--set php.args.USER_UID=$(DOCKER_UID) \
		--set php.args.USER_GID=$(DOCKER_GID) \
		$(DOCKER_BAKE_OPTIONS) php

build/docker/%.json: | $(BUILD_DIRS)
	@image=$(patsubst %/,%,$(dir $*)):$(notdir $*)
	mkdir -p $(dir $@)
	docker pull --quiet "$$image"
	docker image inspect "$$image" > "$@"

##------------------------------------------------------------------------------
# Build/Setup/Teardown Targets
##------------------------------------------------------------------------------

COMPOSER_OPTIONS ?=
ifeq ($(GITHUB_ACTIONS),true)
  COMPOSER_OPTIONS := --no-interaction --no-progress --prefer-dist --optimize-autoloader
endif

$(BUILD_DIRS):
	mkdir -p "$@"

.env:
	@$(call copy-safe,.env.dist,.env)
	@$(call generate-key,PINCH_APP_KEY)

phpstan.neon:
	@$(call copy-safe,phpstan.dist.neon,phpstan.neon)

phpunit.xml:
	@$(call copy-safe,phpunit.dist.xml,phpunit.xml)

$(BUILD_STAMP): $(DOCKER_STAMP) $(COMPOSER_STAMP) $(MIGRATIONS_STAMP) |  phpunit.xml phpstan.neon resources/views/openapi.json resources/views/openapi.html
	@$(call generate-key,PINCH_APP_KEY)
	@echo "Application Build Complete."
	touch "$@"

$(MIGRATIONS_STAMP): database/migrations/* | .env $(BUILD_DIRS)
	docker compose up --detach --wait --wait-timeout=30
	$(docker-php) pinch migrations:migrate --no-interaction
	touch "$@"

$(COMPOSER_STAMP): vendor/autoload.php composer.lock | .env $(BUILD_DIRS)
	@$(call check-token,GITHUB_TOKEN)
	$(docker-php) composer install $(COMPOSER_OPTIONS)
	touch "$@"

vendor/autoload.php composer.lock &: | .env $(DOCKER_STAMP)
	@$(call check-token,GITHUB_TOKEN)
	if [ ! -f composer.lock ]; then \
		docker compose run --rm --user=$$(id -u):$$(id -g) -e XDEBUG_MODE=off php composer update --bump-after-update $(COMPOSER_OPTIONS); \
	else \
    	docker compose run --rm --user=$$(id -u):$$(id -g) -e XDEBUG_MODE=off php composer install $(COMPOSER_OPTIONS); \
	fi

.PHONY: clean
clean:
	$(docker-php) rm -rf ./build ./vendor/ ./public/phpunit resources/views/openapi.html resources/views/openapi.json
	$(docker-php) find /app/storage/ -type f -not -name .gitignore -delete

##------------------------------------------------------------------------------
# Code Quality, Testing & Utility Targets
##------------------------------------------------------------------------------

.PHONY: up
up: $(DOCKER_STAMP)
	docker compose up --detach

.PHONY: down
down:
	docker compose down --remove-orphans

.PHONY: app-key
app-key: | .env
	@$(call generate-key,PINCH_APP_KEY)

.PHONY: bash
bash: | $(DOCKER_STAMP)
	$(docker-php) bash

# Run the PsySH REPL shell
.PHONY: shell psysh
shell psysh: $(BUILD_STAMP)
	docker compose up --detach
	$(docker-php) pinch shell

CI_PREFIX := $(if $(filter true,$(CI)),ci:,)

.PHONY: lint phpcbf phpcs phpstan rector rector-dry-run
lint phpcbf phpcs phpstan rector rector-dry-run: $(BUILD_STAMP)
	docker compose run --rm --user=$$(id -u):$$(id -g) -e XDEBUG_MODE=off php composer run-script "$(CI_PREFIX)$@"

.PHONY: phpunit phpunit-coverage test behat paratest paratest-coverage
phpunit phpunit-coverage test behat paratest paratest-coverage: $(BUILD_STAMP)
	docker compose --progress=quiet up --detach
	docker compose run --rm --user=$$(id -u):$$(id -g) -e XDEBUG_MODE=off php composer run-script "$(CI_PREFIX)$@"

.NOTPARALLEL: ci
.PHONY: ci
ci: lint prettier-check phpcs phpstan rector-dry-run paratest behat openapi-lint

.NOTPARALLEL: pre-ci preci
.PHONY: pre-ci preci
pre-ci preci: prettier-write rector phpcbf ci

# Run the PHP development server to serve the HTML test coverage report on port 8000.
.PHONY: serve-coverage
serve-coverage: | build/phpunit
	@docker compose run --rm --publish 8000:80 ghcr.io/phoneburner/pinch-php php -S 0.0.0.0:80 -t /app/build/phpunit

##------------------------------------------------------------------------------
# Prettier Code Formatter for JSON, YAML, HTML, Markdown, and CSS Files
# Example Usage: `make prettier-check`, `makeprettier-write`
##------------------------------------------------------------------------------

.PHONY: prettier prettier-%
prettier-%: | $(DOCKER_STAMP)
	$(docker-run) --volume $${PWD}:/app --user=$$(id -u):$$(id -g) ghcr.io/phoneburner/pinch-prettier --$* .

##------------------------------------------------------------------------------
# Redocly OpenAPI Validation and Documentation Generation
##-----------------------------------------------------------------------------_

.PHONY: openapi-lint
openapi-lint: openapi.yaml
	$(docker-run) --volume $${PWD}:/spec redocly/cli lint --format="github-actions" openapi.yaml

.PHONY: openapi-docs
openapi-docs: resources/views/openapi.json resources/views/openapi.html

resources/views/openapi.%: openapi.yaml redocly.yaml
	$(docker-run) --volume $${PWD}:/spec redocly/cli $(if $(filter json,$*),bundle,build-docs) openapi.yaml --output="$@"

##------------------------------------------------------------------------------
# Enable Makefile Overrides
#
# If a "build/Makefile" exists, it can define additional targets/behavior and/or
# override the targets of this Makefile. Note that this declaration has to occur
# at the end of the file in order to effect the override behavior. We also
# support a local Makefile that can be used to override the build targets
##------------------------------------------------------------------------------

-include build/Makefile
-include ./local/Makefile
