<?php

declare(strict_types=1);

/**
 * Standalone unit-test bootstrap.
 *
 * This file is used when running the test suite without a full Magento
 * installation — for example in GitHub Actions CI or on a developer machine
 * that only has PHP and PHPUnit available.
 *
 * It does two things:
 *
 *  1. Registers a PSR-4 autoloader for the extension's own namespace
 *     (CtiDigital\Configurator\) so PHPUnit can load the production classes
 *     being tested.
 *
 *  2. Requires Test/stubs.php, which declares minimal stub classes and
 *     interfaces for every Magento, FireGento, GuzzleHttp, PSR-7, and Symfony
 *     dependency that the test suite mocks with getMockBuilder(). The stubs
 *     only need to exist — actual logic is never called because the tests
 *     replace every external dependency with a PHPUnit mock object.
 *
 * When running inside a Magento installation (e.g. via Docker) the real
 * Magento autoloader is already registered, so every class_exists / interface_exists
 * guard in stubs.php is true and the stubs are silently skipped.
 */

// ─── 1. Extension PSR-4 autoloader ───────────────────────────────────────────
//
// Maps CtiDigital\Configurator\ → the extension root directory.
// This mirrors the psr-4 entry in composer.json without needing a vendor dir.

spl_autoload_register(static function (string $class): void {
    $prefix = 'CtiDigital\\Configurator\\';
    $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── 2. Magento / third-party stubs ──────────────────────────────────────────
//
// Must be loaded after the extension autoloader so that production classes
// (which are loaded on demand) can reference stub interfaces in their
// type declarations without triggering fatal errors.

require_once __DIR__ . '/stubs.php';
