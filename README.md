# [READ ONLY] Pinch Framework: API Application Template

> **Note:** This repository is a **read-only** subtree split of
> the [Pinch Framework monorepo](https://github.com/phoneburner/pinch). Please submit issues and pull requests to the main
> repository.

## About

The Pinch Template package provides a complete application starter template for building REST APIs with the Pinch
Framework. This opinionated template includes pre-configured Docker setup, database migrations, testing infrastructure,
and example implementations following the framework's type-safe, API-first philosophy.

## Quick Start

### Create a New Project

```bash
composer create-project phoneburner/pinch-template my-api
cd my-api
```

### Initial Setup

0. **Configure Environment**

    ```bash
    cp .env.dist .env
    # Edit .env with your configuration
    ```

1. **Build Docker Images and Install Dependencies**

    ```bash
    make
    ```

2. **Start Docker Services**

    ```bash
    docker compose up -d
    ```

3. **Run Database Migrations**

    ```bash
    docker compose run --rm php pinch migrations:migrate
    ```

4. **Verify Installation**
    ```bash
    curl http://localhost:8080/
    ```

## Project Overview

```
my-api/
├── bin/               # Console commands
├── config/            # Configuration files
├── database/          # Database migrations
├── public/            # Web root
├── src/               # Application source code
├── tests/             # Test suites
├── storage/           # Cache and logs
├── .env.dist          # Environment template
├── composer.json      # Project Composer dependencies
├── compose.yaml       # Docker Compose configuration
├── Makefile           # Development commands
└── openapi.yaml       # API specification
```

## Development

### Common Commands

```bash
# Run tests
make test

# Code quality checks
make lint
make phpstan
```

## Documentation

- [Main Framework Documentation](https://github.com/phoneburner/pinch)
- [API Design Guidelines](https://github.com/phoneburner/pinch/blob/main/docs/api-design.md)
- [Testing Best Practices](https://github.com/phoneburner/pinch/blob/main/docs/testing.md)
- [Security Considerations](https://github.com/phoneburner/pinch/blob/main/docs/security.md)

## Troubleshooting

### Common Issues

**Docker services not starting:**

```bash
docker compose down -v
docker compose up -d
```

**Permission issues:**

```bash
docker compose exec php chown -R www-data:www-data var/
```

**Database connection errors:**

- Verify `.env` database settings
- Check Docker network: `docker compose ps`
- Verify database service: `docker compose logs db`

## Contributing

This is a read-only repository. To contribute:

1. Visit the [main repository](https://github.com/phoneburner/pinch)
2. Submit issues and pull requests there
3. Follow the contribution guidelines in the main repository

## License

The Pinch Framework is open-source software licensed under
the [MIT license](https://github.com/phoneburner/pinch/blob/main/LICENSE).
