<?php
declare(strict_types=1);

/**
 * Main Application Configuration
 */

// Simple .env parser if exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

return [
    'app_name' => env('APP_NAME', 'FWB Connect'),
    'app_env' => env('APP_ENV', 'production'),
    'app_debug' => (bool)env('APP_DEBUG', false),
    'app_url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'session_lifetime' => (int)env('SESSION_LIFETIME', 7200), // 2 hours
];

