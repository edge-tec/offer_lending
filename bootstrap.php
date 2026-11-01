<?php
declare(strict_types=1);

/**
 * Global Bootstrap & Autoloader
 */

// 1. Load Application Configuration & Env Helper
$appConfig = require_once __DIR__ . '/config/app.php';

// 2. Set Timezone
date_default_timezone_set($appConfig['timezone'] ?? 'UTC');

// 3. Error Reporting
if ($appConfig['app_debug'] ?? false) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// 4. PSR-4 Class Autoloader for \App\*
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

