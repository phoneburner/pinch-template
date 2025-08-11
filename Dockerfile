# syntax=docker/dockerfile:1
##------------------------------------------------------------------------------
# Caddy Build Stages
##------------------------------------------------------------------------------

FROM ghcr.io/phoneburner/pinch-web:latest AS production-web
COPY --link caddy/ /etc/caddy/
COPY --link ./public /app/public
RUN caddy fmt --overwrite /etc/caddy/Caddyfile

##------------------------------------------------------------------------------
# PHP Build Stages
##------------------------------------------------------------------------------

FROM ghcr.io/phoneburner/pinch-php:latest AS development-php
ENV PINCH_BUILD_STAGE="development"
COPY --link php-development.ini /usr/local/etc/php/conf.d/settings.ini
ARG USER_UID=1000
ARG USER_GID=1000
RUN groupadd --gid ${USER_GID} dev
RUN useradd --uid ${USER_UID} --gid ${USER_GID} --groups www-data --no-create-home --home /home/dev --shell /bin/bash dev
RUN chown -R dev:dev /app /home/dev
USER dev

FROM ghcr.io/phoneburner/pinch-php:latest AS production-php-base
ENV PINCH_BUILD_STAGE="production"
COPY --link php-production.ini /usr/local/etc/php/conf.d/settings.ini
ARG USER_UID=1000
ARG USER_GID=1000
RUN groupadd --gid ${USER_GID} dev
RUN useradd --uid ${USER_UID} --gid ${USER_GID} --groups www-data --no-create-home --home /home/dev --shell /bin/bash dev
RUN chown -R dev:dev /app /home/dev
COPY --link --chown=$USER_UID:$USER_GID ./bin /app/bin
COPY --link --chown=$USER_UID:$USER_GID ./config /app/config
COPY --link --chown=$USER_UID:$USER_GID ./database /app/database
COPY --link --chown=$USER_UID:$USER_GID ./public /app/public
COPY --link --chown=$USER_UID:$USER_GID ./resources /app/resources
COPY --link --chown=$USER_UID:$USER_GID ./src /app/src
COPY --link --chown=$USER_UID:$USER_GID ./storage /app/storage
COPY --link --chown=$USER_UID:$USER_GID ./composer.* /app/
COPY --link --chown=$USER_UID:$USER_GID ./openapi.yaml /app/openapi.yaml
USER dev

# Install Composer dependencies and generate application files
ARG PINCH_GIT_COMMIT="undefined"
ENV PINCH_GIT_COMMIT=${PINCH_GIT_COMMIT}
RUN --mount=type=cache,mode=0777,uid=$USER_UID,gid=$USER_GID,target=/app/build/composer \
    --mount=type=secret,id=GITHUB_TOKEN,env=GITHUB_TOKEN,required=false <<-EOF
    set -eux
    mkdir -p /app/storage
    find /app/storage -type d -exec chmod 0777 {} \;
    find /app/storage -type f -exec chmod 0666 {} \;
    [ -n "${GITHUB_TOKEN}" ] && composer config --global github-oauth.github.com ${GITHUB_TOKEN}
    export PINCH_APP_KEY=$(head -c 32 /dev/urandom | base64) # temporary key for build
    composer install --classmap-authoritative --no-dev
    composer audit # Fail the build if vulnerabilities are found (can be adjusted with --ignore-severity option)
    pinch orm:generate-proxies
    pinch routing:cache

    # Set the application version in the welcome view
    sed -i "s/v.0.0.0/v.0.0.0-${PINCH_GIT_COMMIT:0:9}/" "/app/resources/views/welcome.html"

    # Remove the storage cache and auth.json file to avoid baking the them into the build
    rm -f /app/storage/bootstrap/config.cache.php
    rm -f /app/build/composer/auth.json
EOF

FROM redocly/cli AS production-redocly
RUN --mount=type=bind,from=production-php-base,source=/app,target=/app redocly bundle /app/openapi.yaml --output=/spec/openapi.json
RUN --mount=type=bind,from=production-php-base,source=/app,target=/app redocly build-docs /app/openapi.yaml --output=/spec/openapi.html

FROM production-php-base AS production-php
COPY --link --chown=$USER_UID:$USER_GID --from=production-redocly /spec/openapi.json /app/resources/views/openapi.json
COPY --link --chown=$USER_UID:$USER_GID --from=production-redocly /spec/openapi.html /app/resources/views/openapi.html
