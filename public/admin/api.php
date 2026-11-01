<?php
declare(strict_types=1);

/**
 * Admin Internal JSON API
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\Campaign;
use App\Helpers;

header('Content-Type: application/json; charset=UTF-8');

$auth = new Auth();
if (!$auth->check()) {
    Helpers::jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? '';

if ($action === 'test_url') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $url = trim($input['url'] ?? '');

    if ($url === '') {
        Helpers::jsonResponse(['valid' => false, 'message' => 'URL cannot be empty.'], 400);
    }

    $campaign = new Campaign();
    $result = $campaign->testUrl($url);
    Helpers::jsonResponse($result);
}

Helpers::jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);

