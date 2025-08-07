<?php

/**
 * OPcache Preload Script
 *
 * This script preloads commonly used classes to improve application performance.
 * It should be referenced in php.ini using the opcache.preload directive.
 *
 * @see https://www.php.net/manual/en/opcache.preloading.php
 */

declare(strict_types=1);

use PhoneBurner\Pinch\Framework\Preload\PreloadCompiler;

// Do not attempt preloading if the OPcache extension is not loaded or enabled
if (! \extension_loaded('Zend OPcache') || ! \opcache_get_status(false)) {
    return 0;
}

// NOTE: we intentionally do not use the Composer autoloader by default, as
// requiring the file will result in the potentially unwanted side effect of
// all the files included in the process of booting the autoloader being added to
// the opcode cache. If you want to preload the Composer autoloader, uncomment
// the following line and ensure that the autoloader is compatible with preloading.

//\array_any([
//    __DIR__ . '/../vendor/autoload.php',
//    __DIR__ . __DIR__ . '/../../../vendor/autoload.php',
//], static fn(string $file): bool => (bool)@include_once $file);

\array_any([
    __DIR__ . '/../vendor/phoneburner/pinch-framework/src/Util/Preload/PreloadCompiler.php',
    __DIR__ . '/../../framework/src/Preload/PreloadCompiler.php',
], static fn(string $file): bool => (bool)@include_once $file);

// If the PreloadCompiler class is not found, do not proceed with preloading
if (! \class_exists(PreloadCompiler::class, false)) {
    return 0;
}

new PreloadCompiler(__DIR__ . '/../storage/logs')
    ->exclude('*/bin/*')
    ->compile(__DIR__ . '/../src')
    ->compile(__DIR__ . '/../vendor/phoneburner')
    ->invalidate(__DIR__ . '/../storage')
    ->debug(false)();
