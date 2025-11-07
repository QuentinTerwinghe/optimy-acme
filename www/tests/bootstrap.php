<?php

/**
 * PHPUnit Bootstrap File
 *
 * This file is executed before any tests run.
 * It ensures that the testing environment is properly configured.
 */

// Require Composer's autoloader first
require __DIR__.'/../vendor/autoload.php';

// Load all environment variables from .env.testing
// This ensures tests run in an isolated environment
if (file_exists(__DIR__.'/../.env.testing')) {
    // Parse the .env.testing file and manually set all variables
    // This bypasses Laravel's environment loading and ensures our test environment is used
    $lines = file(__DIR__.'/../.env.testing', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Set in all environment locations
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    // Ensure APP_ENV is definitely testing
    $_ENV['APP_ENV'] = 'testing';
    $_SERVER['APP_ENV'] = 'testing';
    putenv('APP_ENV=testing');
}
