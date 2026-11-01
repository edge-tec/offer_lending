<?php
declare(strict_types=1);

namespace App;

/**
 * Utility & Security Helpers
 */
class Helpers
{
    /**
     * Escape HTML output against XSS
     */
    public static function e(?string $string): string
    {
        return htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Get Client IP Address safely
     */
    public static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($parts[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '127.0.0.1';
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }

    /**
     * Hash IP for privacy compliance (GDPR/ePrivacy) with monthly rotating salt
     */
    public static function hashIp(string $ip): string
    {
        $salt = 'FWB_SALT_' . date('Y_m');
        return hash('sha256', $ip . $salt);
    }

    /**
     * Detect Device Type (mobile, tablet, desktop, bot)
     */
    public static function detectDevice(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'desktop';
        }

        $ua = strtolower($userAgent);

        // Bots & Crawlers
        if (preg_match('/(bot|crawler|spider|slurp|facebookexternalhit|bingbot|googlebot)/i', $ua)) {
            return 'bot';
        }

        // Tablets
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            return 'tablet';
        }

        // Mobile Phones
        if (preg_match('/(mobi|iphone|ipod|blackberry|opera mini|iemobile|kindle|mobile)/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Detect Browser
     */
    public static function detectBrowser(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown';
        }
        $ua = $userAgent;

        if (preg_match('/Edge\/([0-9.]+)|Edg\/([0-9.]+)/i', $ua)) return 'Edge';
        if (preg_match('/Chrome\/([0-9.]+)/i', $ua) && !preg_match('/Chromium|OPR/i', $ua)) return 'Chrome';
        if (preg_match('/Firefox\/([0-9.]+)/i', $ua)) return 'Firefox';
        if (preg_match('/Safari\/([0-9.]+)/i', $ua) && !preg_match('/Chrome/i', $ua)) return 'Safari';
        if (preg_match('/OPR\/([0-9.]+)|Opera/i', $ua)) return 'Opera';

        return 'Other';
    }

    /**
     * Detect Operating System
     */
    public static function detectOs(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown';
        }
        $ua = $userAgent;

        if (preg_match('/windows nt 10/i', $ua)) return 'Windows 10/11';
        if (preg_match('/windows/i', $ua)) return 'Windows';
        if (preg_match('/iphone|ipad|ipod/i', $ua)) return 'iOS';
        if (preg_match('/android/i', $ua)) return 'Android';
        if (preg_match('/macintosh|mac os x/i', $ua)) return 'macOS';
        if (preg_match('/linux/i', $ua)) return 'Linux';

        return 'Other';
    }

    /**
     * Get Country Code (via Cloudflare header or default)
     */
    public static function getCountry(): string
    {
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            $country = strtoupper(trim($_SERVER['HTTP_CF_IPCOUNTRY']));
            if (strlen($country) === 2 && ctype_alpha($country)) {
                return $country;
            }
        }
        return 'US'; // Default fallback
    }

    /**
     * Check if a destination URL host matches an allowed domain list (wildcard supported)
     * Protects strictly against Open Redirect vulnerabilities.
     */
    public static function isDomainAllowed(string $url, array $allowedRules): bool
    {
        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
            return false;
        }

        $host = strtolower($parsed['host']);

        foreach ($allowedRules as $rule) {
            $rule = strtolower(trim($rule));
            if ($rule === '') {
                continue;
            }

            // Wildcard match, e.g. *.friends-with-benefits.com
            if (str_starts_with($rule, '*.')) {
                $baseDomain = substr($rule, 2);
                if ($host === $baseDomain || str_ends_with($host, '.' . $baseDomain)) {
                    return true;
                }
            } else {
                if ($host === $rule) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Cleanly merge query parameters into a target affiliate URL
     */
    public static function mergeQueryParams(string $targetUrl, array $incomingParams): string
    {
        $parsed = parse_url($targetUrl);
        $existingQuery = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $existingQuery);
        }

        // Whitelisted incoming tracking parameters to forward
        $forwardKeys = [
            'subid', 'subid2', 'subid3', 'clickid', 'aff_sub', 'aff_sub2', 'aff_sub3',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
            'source', 'placement', 'ad_id', 'campaign_id', 'gclid', 'fbclid', 'ttclid'
        ];

        $forwardParams = [];
        foreach ($forwardKeys as $key) {
            if (isset($incomingParams[$key]) && trim((string)$incomingParams[$key]) !== '') {
                $forwardParams[$key] = trim((string)$incomingParams[$key]);
            }
        }

        // Merge: incoming params can complement or update
        $merged = array_merge($existingQuery, $forwardParams);

        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = $parsed['path'] ?? '';
        $queryString = http_build_query($merged);
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $scheme . '://' . $host . $port . $path . ($queryString ? '?' . $queryString : '') . $fragment;
    }

    /**
     * Send standard JSON response
     */
    public static function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send standard Security Headers
     */
    public static function applySecurityHeaders(): void
    {
        $security = require __DIR__ . '/../config/security.php';
        foreach ($security['headers'] as $header => $val) {
            header("{$header}: {$val}");
        }
    }
}

