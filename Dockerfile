# syntax=docker/dockerfile:1
##------------------------------------------------------------------------------
# Caddy Build Stages
##------------------------------------------------------------------------------

# Web Server for Development (Caddy File Must Be Defined In Volume Mount)
FROM caddy:latest AS development-web
RUN apk upgrade --no-cache

# Web Server for Production
FROM development-web AS production-web
ARG GIT_COMMIT="undefined"
ENV GIT_COMMIT=${GIT_COMMIT}
COPY --link caddy/ /etc/caddy/
RUN caddy fmt --overwrite /etc/caddy/Caddyfile
COPY --link ./public /app/public

##------------------------------------------------------------------------------
# Utility Build Stages
##------------------------------------------------------------------------------

# Prettier Image for Code Formatting
FROM node:alpine AS prettier
ENV NPM_CONFIG_PREFIX=/home/node/.npm-global
ENV PATH=$PATH:/home/node/.npm-global/bin
WORKDIR /app
RUN npm install --global --save-dev --save-exact npm@latest prettier
ENTRYPOINT ["prettier"]

##------------------------------------------------------------------------------
# PHP Build Stages
##------------------------------------------------------------------------------

FROM php:8.4-fpm AS php-base
ARG USER_UID=1000
ARG USER_GID=1000
WORKDIR /
SHELL ["/bin/bash", "-c"]
ENV PATH="/app/bin:/app/vendor/bin:/app/build/composer/bin:$PATH"

# Create a non-root user to run the application
RUN groupadd --gid $USER_GID dev \
    && useradd --uid $USER_UID --gid $USER_GID --groups www-data --create-home --shell /bin/bash dev

# Update the package list and install the latest version of the packages
RUN --mount=type=cache,target=/var/lib/apt,sharing=locked apt-get update && apt-get dist-upgrade --yes

# Install system dependencies
RUN --mount=type=cache,target=/var/lib/apt apt-get install --yes --quiet --no-install-recommends \
    curl \
    git \
    jq \
    less \
    minisign \
    unzip \
    vim-tiny \
    zip \
    libgmp-dev \
    libicu-dev \
    libzip-dev \
    librabbitmq-dev \
    zlib1g-dev \
  && ln -s /usr/bin/vim.tiny /usr/bin/vim \
  && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

FROM php-base AS php-extensions
ENV PHP_PEAR_PHP_BIN="php -d error_reporting=E_ALL&~E_DEPRECATED"
RUN docker-php-ext-install -j$(nproc) bcmath exif gmp intl pcntl pdo_mysql zip
RUN --mount=type=tmpfs,target=/tmp/pear MAKEFLAGS="-j $(nproc)" pecl install amqp igbinary redis xdebug \
    && docker-php-ext-enable opcache amqp igbinary redis

# The Sodium extension originally compiled with PHP is based on an older version
# of the libsodium library provided by Debian. Since it was compiled as a shared
# extension, we can compile the latest stable version of libsodium from source and
# rebuild the extension. We grab the latest stable version of libsodium from their
# official releases, verify its authenticity with minisign and their published
# Ed25519 public key, and then compile it.
FROM php-base AS libsodium
WORKDIR /usr/src/libsodium
RUN <<-EOF
  set -eux
  export MAKEFLAGS="-j $(nproc)"
  curl -fsSL --remote-name-all https://download.libsodium.org/libsodium/releases/libsodium-1.0.20-stable.tar.gz{,.minisig}
  minisign -VP RWQf6LRCGA9i53mlYecO4IzT51TGPpvWucNSCh1CBM0QTaLn73Y7GFO3 -m libsodium-1.0.20-stable.tar.gz
  tar -xzf libsodium-1.0.20-stable.tar.gz --strip-components=1
  ./configure
  make && make check
  make install
  rm -rf /usr/src/libsodium
EOF

FROM php-base AS php-common
ARG GIT_COMMIT="undefined"
ENV GIT_COMMIT=${GIT_COMMIT}
ENV COMPOSER_HOME="/home/dev/.composer"
ENV COMPOSER_CACHE_DIR="/app/build/composer/cache"
ARG PHP_EXT_DIR="/usr/local/lib/php/extensions/no-debug-non-zts-20240924"
WORKDIR /app
COPY --link --from=php-extensions /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --link --from=php-extensions /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --link --from=libsodium /usr/local/include/sodium /usr/local/include/sodium
COPY --link --from=libsodium /usr/local/include/sodium.h /usr/local/include/
COPY --link --from=libsodium /usr/local/lib/libsodium.* /usr/local/lib/
COPY --link --from=libsodium /usr/local/lib/pkgconfig /usr/local/lib/pkgconfig
RUN docker-php-ext-install -j$(nproc) sodium
RUN chown -R dev:dev /app /home/dev

FROM php-common AS development-php
ENV SALT_BUILD_STAGE="development"
ENV XDEBUG_MODE="off"
COPY --link php-development.ini /usr/local/etc/php/conf.d/settings.ini
COPY --link --from=composer/composer /usr/bin/composer /usr/local/bin/composer
COPY --link --chown=$USER_UID:$USER_GID --from=composer/composer /tmp/* /home/dev/.composer/
RUN docker-php-ext-enable xdebug
USER dev

FROM php-common AS production-php-stage-0
ENV SALT_BUILD_STAGE="production"
COPY --link php-production.ini /usr/local/etc/php/conf.d/settings.ini
COPY --link --chown=$USER_UID:$USER_GID ./bin /app/bin
COPY --link --chown=$USER_UID:$USER_GID ./config /app/config
COPY --link --chown=$USER_UID:$USER_GID ./database /app/database
COPY --link --chown=$USER_UID:$USER_GID ./public /app/public
COPY --link --chown=$USER_UID:$USER_GID ./resources /app/resources
COPY --link --chown=$USER_UID:$USER_GID ./src /app/src
COPY --link --chown=$USER_UID:$USER_GID ./storage /app/storage
COPY --link --chown=$USER_UID:$USER_GID ./composer.json ./composer.lock /app/
COPY --link --chown=$USER_UID:$USER_GID ./openapi.yaml /app/openapi.yaml
USER dev
RUN --mount=type=bind,from=composer/composer,source=/usr/bin/composer,target=/usr/local/bin/composer \
    --mount=type=cache,mode=0777,uid=$USER_UID,gid=$USER_GID,target=/app/build/composer \
    --mount=type=secret,id=GITHUB_TOKEN,env=GITHUB_TOKEN,required=false <<-EOF
    set -eux
    mkdir -p /app/build/composer
    mkdir -p /app/storage
    find /app/storage -type d -exec chmod 0777 {} \;
    find /app/storage -type f -exec chmod 0666 {} \;
    [ -n "${GITHUB_TOKEN}" ] && composer config --global github-oauth.github.com ${GITHUB_TOKEN}
    export SALT_APP_KEY=$(head -c 32 /dev/urandom | base64) # temporary key for build
    composer install --classmap-authoritative --no-dev
    salt orm:generate-proxies
    salt routing:cache

    # Set the application version in the welcome view
    sed -i "s/v.0.0.0/v.0.0.0-${GIT_COMMIT:0:9}/" "/app/resources/views/welcome.html"

    # Remove the storage cache and auth.json file to avoid baking the them into the build
    rm -f /app/storage/bootstrap/config.cache.php
    rm -f /app/build/composer/auth.json
EOF

FROM redocly/cli AS production-redocly
RUN --mount=type=bind,from=production-php-stage-0,source=/app,target=/app redocly bundle /app/openapi.yaml --output=/spec/openapi.json
RUN --mount=type=bind,from=production-php-stage-0,source=/app,target=/app redocly build-docs /app/openapi.yaml --output=/spec/openapi.html

FROM production-php-stage-0 AS production-php
COPY --link --chown=$USER_UID:$USER_GID --from=production-redocly /spec/openapi.json /app/resources/views/openapi.json
COPY --link --chown=$USER_UID:$USER_GID --from=production-redocly /spec/openapi.html /app/resources/views/openapi.html
