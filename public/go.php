<?php
declare(strict_types=1);

/**
 * Smart CTA Redirect Gateway (/go)
 * 
 * 1. Dynamically fetches current active campaign tracking URL
 * 2. Records click analytics (device, geo, referer, subid, clickid, utm parameters)
 * 3. Enforces domain whitelist protection against open-redirect exploits
 * 4. Merges query parameters seamlessly
 * 5. Sends instant 302/307 redirect to active destination
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Tracker;
use App\Helpers;

Helpers::applySecurityHeaders();

// 0. Handle Short Links (/go/{code})
if (!empty($_GET['short_code'])) {
    $shortCode = trim((string)$_GET['short_code']);
    $shortService = new \App\ShortLink();
    $resolved = $shortService->resolve($shortCode);
    if ($resolved && $resolved['success'] && !empty($resolved['landing_page'])) {
        $slug = $resolved['landing_page']['slug'];
        header("Location: /" . $slug, true, 302);
        exit;
    }
}

$tracker = new Tracker();
$result = $tracker->processClick($_GET);

if ($result['status'] === 'success') {
    // Prevent browser and proxy caching of dynamic redirect destination
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("Location: " . $result['redirect_url'], true, 302);
    exit;
}

// Display friendly fallback error if tracking link is inactive or domain validation fails
$errorCode = $result['code'] ?? 404;
http_response_code($errorCode);
$title = Helpers::e($result['title'] ?? 'Notice');
$message = Helpers::e($result['message'] ?? 'An error occurred during redirect.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> | FWB Connect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0c10;
            --card-bg: #151821;
            --text-primary: #ffffff;
            --text-secondary: #9ba1b0;
            --accent: #f43f5e;
            --accent-glow: rgba(244, 63, 94, 0.25);
            --border: #232838;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .container {
            max-width: 480px;
            width: 100%;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 50px var(--accent-glow);
        }
        .icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(244, 63, 94, 0.12);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto;
            font-size: 1.75rem;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.85rem;
            letter-spacing: -0.02em;
        }
        p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.85rem 1.75rem;
            background: linear-gradient(135deg, #f43f5e, #e11d48);
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 9999px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(244, 63, 94, 0.4);
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(244, 63, 94, 0.6);
        }
        .footer-note {
            margin-top: 1.75rem;
            font-size: 0.78rem;
            color: #636b7c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        <h1><?= $title ?></h1>
        <p><?= $message ?></p>
        <a href="/" class="btn">Return to Homepage</a>
        <div class="footer-note">FWB Connect &bull; Secure Referral Gateway</div>
    </div>
</body>
</html>

