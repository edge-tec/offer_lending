<?php
declare(strict_types=1);

/**
 * Security & Anti-Abuse Configuration
 */

return [
    // Maximum failed login attempts before temporary lockout
    'max_login_attempts' => 5,
    'lockout_duration_minutes' => 15,

    // Cookie security
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',

    // CSRF token name
    'csrf_token_name' => '_csrf_token',

    // Allowed affiliate target domains (Open Redirect Protection)
    'default_allowed_domains' => [
        'friends-with-benefits.com',
        '*.friends-with-benefits.com',
        'example.com'
    ],

    // Security Headers
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin'
    ]
];

