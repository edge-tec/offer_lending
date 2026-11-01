<?php
declare(strict_types=1);

/**
 * Database Configuration
 */

return [
    // 'mysql' or 'sqlite'
    'default' => env('DB_CONNECTION', 'sqlite'),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int)env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'fwb_affiliate'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_SQLITE_PATH', __DIR__ . '/../storage/database.sqlite'),
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ],
        ]
    ]
];
