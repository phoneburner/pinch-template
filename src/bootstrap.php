<?php

/**
 * Bootstrap & Normalize Runtime Environment
 *
 * Loaded via Composer “files” autoloading as the very first file in the
 * process, this script is responsible for:
 *   1. Establishing and normalizing the runtime environment variables and settings.
 *   2. Defining process‑wide constants used throughout the application
 *
 * This file is not part of the application’s business logic—it should
 * only ever be loaded once per process and must not be included directly
 * elsewhere. This script is executed every time the Composer vendor/autoload.php
 * file is required/included (e.g., during tests, tooling execution, etc.).
 *
 * Application‑specific environment initialization can be added to this file
 * before or after the EnvironmentLoader::init() call, depending on the nature
 * of the initialization. However, any such logic should be carefully designed
 * to avoid side effects, especially if executed before the EnvironmentLoader.
 *
 * If the logic is not already encapsulated in a function or class, it should be
 * wrapped in an immediately invoked static function expression to prevent scope
 * pollution and unintended side effects.
 */

declare(strict_types=1);

namespace App;

use PhoneBurner\Pinch\Framework\Configuration\EnvironmentLoader;

EnvironmentLoader::init(\dirname(__DIR__));
