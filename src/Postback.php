<?php
declare(strict_types=1);

namespace App;

/**
 * S2S Affiliate Postback & Conversion Tracking Engine
 */
class Postback
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Process incoming affiliate network postback
     */
    public function handle(array $params, string $method = 'GET', ?string $rawBody = null): array
    {
        $ip = Helpers::getClientIp();
        $clickId = trim((string)($params['clickid'] ?? $params['click_id'] ?? $params['subid'] ?? ''));
        $txid = trim((string)($params['txid'] ?? $params['transaction_id'] ?? $params['trans_id'] ?? ''));
        $payout = (float)($params['payout'] ?? $params['revenue'] ?? $params['amount'] ?? 0.0);
        $currency = strtoupper(trim((string)($params['currency'] ?? 'USD')));
        $status = strtolower(trim((string)($params['status'] ?? 'approved')));

        if ($txid === '') {
            $txid = 'TX_' . md5($clickId . '_' . microtime(true));
        }

        // 1. Log incoming postback
        $logStmt = $this->db->prepare("
            INSERT INTO postback_logs (request_method, request_uri, request_body, query_params, response_status, response_message, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");

        if ($clickId === '') {
            $logStmt->execute([$method, $_SERVER['REQUEST_URI'] ?? '', $rawBody, json_encode($params), 400, 'Missing click_id', $ip]);
            return ['success' => false, 'message' => 'Missing click_id parameter.', 'code' => 400];
        }

        // 2. Prevent duplicate conversion
        $checkDup = $this->db->prepare("SELECT id FROM conversions WHERE transaction_id = ? LIMIT 1");
        $checkDup->execute([$txid]);
        if ($checkDup->fetchColumn()) {
            $logStmt->execute([$method, $_SERVER['REQUEST_URI'] ?? '', $rawBody, json_encode($params), 200, 'Duplicate transaction ignored', $ip]);
            return ['success' => true, 'message' => 'Duplicate transaction ID ignored.', 'code' => 200];
        }

        // 3. Find matching offer click
        $findClick = $this->db->prepare("SELECT * FROM offer_clicks WHERE click_id = ? LIMIT 1");
        $findClick->execute([$clickId]);
        $clickRow = $findClick->fetch();

        $offerId = $clickRow ? (int)$clickRow['offer_id'] : (!empty($params['offer_id']) ? (int)$params['offer_id'] : null);
        $pageId = $clickRow ? (int)$clickRow['landing_page_id'] : null;

        // 4. Record Conversion
        $convStmt = $this->db->prepare("
            INSERT INTO conversions (click_id, offer_id, landing_page_id, transaction_id, payout, currency, status, ip_address, converted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $convStmt->execute([$clickId, $offerId, $pageId, $txid, $payout, $currency, $status, $ip]);

        $logStmt->execute([$method, $_SERVER['REQUEST_URI'] ?? '', $rawBody, json_encode($params), 200, 'Conversion approved', $ip]);

        return [
            'success' => true,
            'message' => 'Conversion recorded successfully.',
            'transaction_id' => $txid,
            'payout' => $payout,
            'code' => 200
        ];
    }

    /**
     * Get recent postback logs
     */
    public function getLogs(int $limit = 50): array
    {
        $sql = "SELECT * FROM postback_logs ORDER BY id DESC LIMIT {$limit}";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get all conversions with details
     */
    public function getConversions(int $limit = 50): array
    {
        $sql = "
            SELECT c.*, o.name as offer_name, lp.name as page_name
            FROM conversions c
            LEFT JOIN offers o ON o.id = c.offer_id
            LEFT JOIN landing_pages lp ON lp.id = c.landing_page_id
            ORDER BY c.id DESC
            LIMIT {$limit}
        ";
        return $this->db->query($sql)->fetchAll();
    }
}

