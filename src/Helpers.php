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
     * Replace tracking macros in an affiliate tracking URL (e.g. {click_id}, {clickid}, {subid})
     */
    public static function replaceTrackingMacros(string $url, string $clickId, ?string $subid = null, array $extra = []): string
    {
        $effectiveSubid = ($subid !== null && $subid !== '') ? $subid : $clickId;

        $replacements = [
            '{click_id}'       => $clickId,
            '{clickid}'        => $clickId,
            '{CLICK_ID}'       => $clickId,
            '{CLICKID}'        => $clickId,
            '{clickId}'        => $clickId,
            '{cid}'            => $clickId,
            '{CID}'            => $clickId,
            '{txid}'           => $clickId,
            '{transaction_id}' => $clickId,
            '[click_id]'       => $clickId,
            '[clickid]'        => $clickId,
            '[CLICK_ID]'       => $clickId,
            '[CLICKID]'        => $clickId,
            '%7Bclick_id%7D'   => $clickId,
            '%7Bclickid%7D'    => $clickId,
            '%7BCLICK_ID%7D'   => $clickId,
            '%7BCLICKID%7D'    => $clickId,
            '%7BclickId%7D'    => $clickId,
            '%5Bclick_id%5D'   => $clickId,
            '%5Bclickid%5D'    => $clickId,
            '{subid}'          => $effectiveSubid,
            '{sub_id}'         => $effectiveSubid,
            '{SUBID}'          => $effectiveSubid,
            '[subid]'          => $effectiveSubid,
            '%7Bsubid%7D'      => $effectiveSubid,
            '%7Bsub_id%7D'     => $effectiveSubid,
            '%5Bsubid%5D'      => $effectiveSubid,
        ];

        foreach ($extra as $key => $val) {
            if ($val !== null && is_scalar($val) && !isset($replacements['{' . $key . '}'])) {
                $valStr = (string)$val;
                $replacements['{' . $key . '}'] = $valStr;
                $replacements['[' . $key . ']'] = $valStr;
                $replacements['%7B' . $key . '%7D'] = $valStr;
                $replacements['%5B' . $key . '%5D'] = $valStr;
            }
        }

        return str_ireplace(array_keys($replacements), array_values($replacements), $url);
    }

    /**
     * Cleanly merge query parameters into a target affiliate URL with macro replacement
     */
    public static function mergeQueryParams(string $targetUrl, array $incomingParams): string
    {
        $clickId = trim((string)($incomingParams['click_id'] ?? $incomingParams['clickid'] ?? ''));
        $subid = trim((string)($incomingParams['subid'] ?? ''));

        // 1. Perform macro substitution on the target URL if click_id or subid is available
        if ($clickId !== '' || $subid !== '') {
            $targetUrl = self::replaceTrackingMacros($targetUrl, $clickId, $subid ?: null, $incomingParams);
        }

        $parsed = parse_url($targetUrl);
        $existingQuery = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $existingQuery);
        }

        // Whitelisted incoming tracking parameters to forward
        $forwardKeys = [
            'click_id', 'clickid', 'clickId',
            'subid', 'sub_id', 'subid1', 'subid2', 'subid3', 'subid4', 'subid5',
            'aff_sub', 'aff_sub2', 'aff_sub3', 'aff_sub4', 'aff_sub5',
            's1', 's2', 's3', 's4', 's5', 'c1', 'c2', 'c3', 'cid', 'sid', 'tid',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
            'source', 'placement', 'ad_id', 'campaign_id', 'gclid', 'fbclid', 'ttclid'
        ];

        $forwardParams = [];
        foreach ($forwardKeys as $key) {
            if (isset($incomingParams[$key]) && trim((string)$incomingParams[$key]) !== '') {
                $forwardParams[$key] = trim((string)$incomingParams[$key]);
            }
        }

        // Guarantee both click_id and clickid are passed so every affiliate network receives the real-time click ID
        if ($clickId !== '') {
            if (!isset($existingQuery['click_id']) && !isset($forwardParams['click_id'])) {
                $forwardParams['click_id'] = $clickId;
            }
            if (!isset($existingQuery['clickid']) && !isset($forwardParams['clickid'])) {
                $forwardParams['clickid'] = $clickId;
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
     * Auto-whitelist the destination domain for open-redirect safety
     */
    public static function autoWhitelistDomain(string $url, int $userId = 1): void
    {
        $parsedHost = parse_url($url, PHP_URL_HOST);
        if (!$parsedHost) return;

        $settings = new Settings();
        $allowed = (string)$settings->get('allowed_redirect_domains', '');
        $allowedRules = array_filter(array_map('trim', explode("\n", $allowed)));

        if (!self::isDomainAllowed($url, $allowedRules)) {
            $allowedRules[] = $parsedHost;
            $allowedRules[] = '*.' . $parsedHost;
            $settings->updateMany(['allowed_redirect_domains' => implode("\n", array_unique($allowedRules))], $userId);
        }
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

