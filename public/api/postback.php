<?php
declare(strict_types=1);

/**
 * S2S Affiliate Network Conversion Postback API (/api/postback.php)
 * 
 * Supports:
 * - GET and POST requests
 * - Parameters: click_id, clickid, subid, txid, transaction_id, payout, revenue, currency, status, offer_id
 * - Prevents duplicate conversions
 * - Logs raw postback requests
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Postback;
use App\Helpers;

header('Content-Type: application/json; charset=UTF-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rawBody = file_get_contents('php://input');

$params = $_GET;
if (empty($params) && !empty($_SERVER['REQUEST_URI'])) {
    $q = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    if ($q) {
        parse_str($q, $params);
    }
}

if ($method === 'POST') {
    $postData = $_POST;
    if (empty($postData) && !empty($rawBody)) {
        $json = json_decode($rawBody, true);
        if (is_array($json)) {
            $postData = $json;
        }
    }
    $params = array_merge($params, $postData);
}

$postback = new Postback();
$result = $postback->handle($params, $method, $rawBody ?: null);

Helpers::jsonResponse($result, $result['code'] ?? 200);
