<?php
declare(strict_types=1);

/**
 * Enterprise Centralized Offer Redirect Engine (/click)
 * 
 * Handles individual offer CTA clicks across all landing pages:
 * 1. Resolves offer & active affiliate tracking URL
 * 2. Generates unique click_id for S2S Postback reconciliation
 * 3. Logs click in offer_clicks table
 * 4. Merges subid, clickid, and UTM parameters
 * 5. Sends 302 redirect to third-party affiliate destination
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Tracker;
use App\Helpers;

Helpers::applySecurityHeaders();

$offerId = (int)($_GET['offer_id'] ?? 0);
$pageId = isset($_GET['page_id']) ? (int)$_GET['page_id'] : null;

if ($offerId <= 0 && (!$pageId || $pageId <= 0)) {
    // If no offer_id and no page_id, fallback to general campaign redirect (/go)
    require __DIR__ . '/go.php';
    exit;
}

$tracker = new Tracker();
$result = $tracker->processOfferClick($offerId, $pageId, $_GET);

if ($result['status'] === 'success') {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Location: " . $result['redirect_url'], true, 302);
    exit;
}

// Fallback error screen
http_response_code($result['code'] ?? 404);
$title = Helpers::e($result['title'] ?? 'Notice');
$message = Helpers::e($result['message'] ?? 'An error occurred during redirect.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem; background: #08090d; }
        .box { max-width: 460px; width: 100%; background: #12151f; border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; padding: 2.5rem 2rem; text-align: center; }
        .icon { width: 56px; height: 56px; border-radius: 50%; background: rgba(244,63,94,0.12); color: #f43f5e; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto; font-size: 1.5rem; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">!</div>
        <h2 style="font-size: 1.4rem; margin-bottom: 0.85rem;"><?= $title ?></h2>
        <p style="color: #94a3b8; font-size: 0.95rem; line-height: 1.6; margin-bottom: 2rem;"><?= $message ?></p>
        <a href="/" class="btn btn-primary" style="display: inline-block;">Return to Homepage</a>
    </div>
</body>
</html>

