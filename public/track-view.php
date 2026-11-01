<?php
declare(strict_types=1);

/**
 * Pageview Impression Beacon (/track-view)
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Tracker;
use App\Helpers;

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$campaignCode = isset($_GET['c']) ? trim((string)$_GET['c']) : null;

$tracker = new Tracker();
$tracker->trackPageView($campaignCode);

echo json_encode(['status' => 'ok']);
exit;

