SHELL := bash
.SHELLFLAGS = -ec
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
redocly-cli := $(docker-run) --volume $${PWD}:/spec redocly/cli

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

BUILD_DIRS = build/.phpunit.cache \
	build/composer \
	build/docker \
	build/phpstan \
	build/phpunit \
	build/psysh/config \
	build/psysh/data \
	build/psysh/tmp \
	build/rector \
	build/xdebug

##------------------------------------------------------------------------------
# Docker Targets
##------------------------------------------------------------------------------

build/docker/docker-compose.json: Dockerfile compose.yaml | build/docker
# Skip USER_UID and USER_GID on Mac OS to avoid compatibility issues
ifeq ($(OS),Darwin)
	docker compose pull --quiet --ignore-buildable
	COMPOSE_BAKE=true docker compose build --pull
	touch "$@" # required to consistently update the file mtime
else
	docker compose pull --quiet --ignore-buildable
	COMPOSE_BAKE=true docker compose build --pull --build-arg USER_UID=$$(id -u) --build-arg USER_GID=$$(id -g)
	touch "$@" # required to consistently update the file mtime
endif

build/docker/pinch-%.json: Dockerfile | build/docker
	docker buildx build --target="$*" --pull --load --tag="pinch-$*" --file Dockerfile .
	docker image inspect "pinch-$*" > "$@"

##------------------------------------------------------------------------------
# Build/Setup/Teardown Targets
##------------------------------------------------------------------------------

$(BUILD_DIRS): | .env phpstan.neon phpunit.xml
	mkdir -p "$@"

.env:
	@$(call copy-safe,.env.dist,.env)
	@$(call generate-key,PINCH_APP_KEY)

phpstan.neon:
	@$(call copy-safe,phpstan.dist.neon,phpstan.neon)

phpunit.xml:
	@$(call copy-safe,phpunit.dist.xml,phpunit.xml)

composer.lock vendor: build/composer build/docker/docker-compose.json composer.json | build .env
	mkdir -p "$@"
	@$(call check-token,GITHUB_TOKEN)
	$(docker-php) composer install
	@touch vendor composer.lock

build/.install: $(BUILD_DIRS) build/docker/docker-compose.json vendor build/.migrations | .env phpunit.xml phpstan.neon
	@$(call generate-key,PINCH_APP_KEY)
	@echo "Application Build Complete."
	@touch build/.install

build/.migrations: database/migrations/*
	docker compose up --detach --wait --wait-timeout=30
	$(docker-php) pinch migrations:migrate --no-interaction && touch $@

.PHONY: clean
clean:
	$(docker-php) rm -rf ./build ./vendor/ ./public/phpunit
	$(docker-php) find /app/storage/ -type f -not -name .gitignore -delete

##------------------------------------------------------------------------------
# Code Quality, Testing & Utility Targets
##------------------------------------------------------------------------------

.PHONY: up
up:
	docker compose up --detach

.PHONY: down
down:
	docker compose down --remove-orphans

.PHONY: bash
bash: build/docker/docker-compose.json
	$(docker-php) bash

# Run the PsySH REPL shell
.PHONY: shell psysh
shell psysh: build/.install
	docker compose up --detach
	$(docker-php) pinch shell

.PHONY: lint phpcbf phpcs phpstan rector rector-dry-run
lint phpcbf phpcs phpstan rector rector-dry-run: build/.install
	docker compose run --rm --user=$$(id -u):$$(id -g) -e XDEBUG_MODE=off php composer run-script "$@"

.PHONY: phpunit phpunit-coverage test behat paratest paratest-coverage
phpunit phpunit-coverage test behat paratest paratest-coverage: build/.install
	docker compose up --detach
	docker compose run --rm --user=$$(id -u):$$(id -g) -e XDEBUG_MODE=off php composer run-script "$@"

.NOTPARALLEL: ci pre-ci preci
.PHONY: ci pre-ci preci
ci: lint phpcs phpstan test prettier-check rector-dry-run

.NOTPARALLEL: pre-ci preci
.PHONY: pre-ci preci
pre-ci preci: prettier-write rector phpcbf ci

# Run the PHP development server to serve the HTML test coverage report on port 8000.
.PHONY: serve-coverage
serve-coverage:
	@docker compose run --rm --publish 8000:80 ghcr.io/phoneburner/pinch-php php -S 0.0.0.0:80 -t /app/build/phpunit

##------------------------------------------------------------------------------
# Prettier Code Formatter for JSON, YAML, HTML, Markdown, and CSS Files
# Example Usage: `make prettier-check`, `makeprettier-write`
##------------------------------------------------------------------------------

.PHONY: prettier-%
prettier-%: | build/docker/pinch-prettier.json
	$(docker-run) --volume $${PWD}:/app --user=$$(id -u):$$(id -g) pinch-prettier --$* .


##------------------------------------------------------------------------------
# Redocly OpenAPI Validation and Documentation Generation
##-----------------------------------------------------------------------------_

.PHONY: openapi-lint
openapi-lint: openapi.yaml | build/docker/redocly/cli/latest.json
	$(redocly-cli) lint --format="github-actions" openapi.yaml

.PHONY: openapi-docs
openapi-docs: resources/views/openapi.json resources/views/openapi.html

resources/views/openapi.%: openapi.yaml redocly.yaml | build/docker/redocly/cli/latest.json
	$(redocly-cli) $(if $(filter json,$*),bundle,build-docs) openapi.yaml --output="$@"

##------------------------------------------------------------------------------
# Enable Makefile Overrides
#
# If a "build/Makefile" exists, it can define additional targets/behavior and/or
# override the targets of this Makefile. Note that this declaration has to occur
# at the end of the file in order to effect the override behavior.
##------------------------------------------------------------------------------

-include build/Makefile
-include ./local/Makefile
