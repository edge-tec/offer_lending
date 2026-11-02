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
        $clickId = trim((string)(
            $params['click_id'] ?? 
            $params['clickid'] ?? 
            $params['clickId'] ?? 
            $params['CLICK_ID'] ?? 
            $params['CLICKID'] ?? 
            $params['subid'] ?? 
            $params['sub_id'] ?? 
            $params['cid'] ?? 
            $params['s1'] ?? 
            $params['s2'] ?? 
            $params['aff_sub'] ?? 
            $params['aff_sub1'] ?? 
            ''
        ));
        $txid = trim((string)(
            $params['txid'] ?? 
            $params['transaction_id'] ?? 
            $params['trans_id'] ?? 
            $params['order_id'] ?? 
            $params['conversion_id'] ?? 
            $params['id'] ?? 
            ''
        ));
        $payout = (float)(
            $params['payout'] ?? 
            $params['revenue'] ?? 
            $params['amount'] ?? 
            $params['commission'] ?? 
            0.0
        );
        $currency = strtoupper(trim((string)($params['currency'] ?? 'USD')));
        $status = strtolower(trim((string)($params['status'] ?? 'approved')));

        if ($txid === '') {
            $txid = 'TX_' . md5($clickId);
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

        // 2. Prevent duplicate conversion: check if this transaction_id OR this click_id already converted
        $checkDup = $this->db->prepare("SELECT id, payout, status FROM conversions WHERE transaction_id = ? OR click_id = ? LIMIT 1");
        $checkDup->execute([$txid, $clickId]);
        $existingConv = $checkDup->fetch(\PDO::FETCH_ASSOC);

        if ($existingConv) {
            // If payout changed or status updated, refresh existing record rather than creating a duplicate
            if ($payout > 0 || ($status !== '' && $status !== $existingConv['status'])) {
                $upd = $this->db->prepare("UPDATE conversions SET payout = CASE WHEN ? > 0 THEN ? ELSE payout END, status = ? WHERE id = ?");
                $upd->execute([$payout, $payout, $status, $existingConv['id']]);
            }
            $logStmt->execute([$method, $_SERVER['REQUEST_URI'] ?? '', $rawBody, json_encode($params), 200, 'Duplicate transaction ignored', $ip]);
            return [
                'success' => true,
                'message' => 'Duplicate transaction ID ignored.',
                'code' => 200,
                'transaction_id' => $txid,
                'conversion_id' => $existingConv['id']
            ];
        }

        // 3. Find matching offer click
        $findClick = $this->db->prepare("SELECT * FROM offer_clicks WHERE click_id = ? LIMIT 1");
        $findClick->execute([$clickId]);
        $clickRow = $findClick->fetch();

        if (!$clickRow) {
            $findLegacy = $this->db->prepare("SELECT * FROM clicks WHERE clickid = ? OR subid = ? LIMIT 1");
            $findLegacy->execute([$clickId, $clickId]);
            $legacyRow = $findLegacy->fetch();
            $offerId = !empty($params['offer_id']) ? (int)$params['offer_id'] : null;
            $pageId = !empty($params['landing_page_id']) ? (int)$params['landing_page_id'] : null;
        } else {
            $offerId = $clickRow['offer_id'] ? (int)$clickRow['offer_id'] : (!empty($params['offer_id']) ? (int)$params['offer_id'] : null);
            $pageId = $clickRow['landing_page_id'] ? (int)$clickRow['landing_page_id'] : (!empty($params['landing_page_id']) ? (int)$params['landing_page_id'] : null);
        }

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
            'click_id' => $clickId,
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

    /**
     * Deduplicate conversions table:
     * Keeps the earliest conversion for each click_id and deletes duplicates
     */
    public function deduplicateConversions(): int
    {
        $duplicates = $this->db->query("
            SELECT click_id, MIN(id) as keep_id, COUNT(*) as cnt
            FROM conversions
            WHERE click_id IS NOT NULL AND click_id != ''
            GROUP BY click_id
            HAVING cnt > 1
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $deletedCount = 0;
        foreach ($duplicates as $dup) {
            $stmt = $this->db->prepare("DELETE FROM conversions WHERE click_id = ? AND id != ?");
            $stmt->execute([$dup['click_id'], $dup['keep_id']]);
            $deletedCount += ((int)$dup['cnt'] - 1);
        }

        // Check if duplicate transaction_ids exist as well
        $txDups = $this->db->query("
            SELECT transaction_id, MIN(id) as keep_id, COUNT(*) as cnt
            FROM conversions
            WHERE transaction_id IS NOT NULL AND transaction_id != ''
            GROUP BY transaction_id
            HAVING cnt > 1
        ")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($txDups as $dup) {
            $stmt = $this->db->prepare("DELETE FROM conversions WHERE transaction_id = ? AND id != ?");
            $stmt->execute([$dup['transaction_id'], $dup['keep_id']]);
            $deletedCount += ((int)$dup['cnt'] - 1);
        }

        return $deletedCount;
    }

    /**
     * Delete a single conversion by ID
     */
    public function deleteConversion(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM conversions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Clear all mock / test conversions
     */
    public function clearTestConversions(): int
    {
        $stmt = $this->db->prepare("DELETE FROM conversions WHERE click_id LIKE 'clk_mock%' OR transaction_id LIKE 'TX_TEST_SUITE%' OR transaction_id LIKE 'TX_REALTIME%'");
        $stmt->execute();
        return $stmt->rowCount();
    }
}

