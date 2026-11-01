<?php
declare(strict_types=1);

namespace App;

/**
 * Campaign & Affiliate Tracking URL Management
 */
class Campaign
{
    private \PDO $db;
    private AuditLog $auditLog;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->auditLog = new AuditLog();
    }

    /**
     * Get all campaigns with their click metrics
     */
    public function getAll(): array
    {
        $sql = "
            SELECT 
                c.*,
                COUNT(cl.id) as total_clicks,
                COUNT(DISTINCT cl.ip_hash) as unique_clicks,
                MAX(cl.created_at) as last_click_at
            FROM campaigns c
            LEFT JOIN clicks cl ON cl.campaign_id = c.id
            GROUP BY c.id
            ORDER BY c.is_default DESC, c.id DESC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get single campaign by ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM campaigns WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Get single campaign by unique campaign_code
     */
    public function getByCode(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM campaigns WHERE campaign_code = ? LIMIT 1");
        $stmt->execute([$code]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Resolve the active campaign to route to:
     * 1. If explicit campaign_code is passed and active, return it.
     * 2. Else return the default active campaign.
     * 3. Else fallback to any active campaign.
     */
    public function resolveActiveCampaign(?string $code = null): ?array
    {
        if (!empty($code)) {
            $stmt = $this->db->prepare("SELECT * FROM campaigns WHERE campaign_code = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$code]);
            $campaign = $stmt->fetch();
            if ($campaign) {
                return $campaign;
            }
        }

        // Default active campaign
        $stmt = $this->db->query("SELECT * FROM campaigns WHERE is_default = 1 AND is_active = 1 LIMIT 1");
        $default = $stmt->fetch();
        if ($default) {
            return $default;
        }

        // Fallback to any active campaign
        $stmt = $this->db->query("SELECT * FROM campaigns WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
        $anyActive = $stmt->fetch();
        return $anyActive ?: null;
    }

    /**
     * Create a new tracking campaign
     */
    public function create(array $data, int $userId): array
    {
        $name = trim($data['name'] ?? '');
        $code = trim($data['campaign_code'] ?? '');
        $url = trim($data['target_url'] ?? '');
        $isActive = !empty($data['is_active']) ? 1 : 0;
        $isDefault = !empty($data['is_default']) ? 1 : 0;
        $notes = trim($data['notes'] ?? '');

        // Validations
        if ($name === '') {
            return ['success' => false, 'message' => 'Campaign name is required.'];
        }
        if ($code === '') {
            $code = preg_replace('/[^a-z0-9_-]/i', '-', strtolower($name));
        }
        $testUrl = preg_replace('/\{[a-zA-Z0-9_-]+\}/', 'macro', $url);
        $testUrl = preg_replace('/\[[a-zA-Z0-9_-]+\]/', 'macro', (string)$testUrl);
        if (!filter_var($testUrl, FILTER_VALIDATE_URL) || (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://'))) {
            return ['success' => false, 'message' => 'A valid HTTP/HTTPS affiliate tracking URL is required.'];
        }

        // Check unique code
        if ($this->getByCode($code)) {
            return ['success' => false, 'message' => 'Campaign code is already in use. Please choose a unique code.'];
        }

        // If setting as default, remove default from others
        if ($isDefault) {
            $this->db->exec("UPDATE campaigns SET is_default = 0");
        }

        $stmt = $this->db->prepare("
            INSERT INTO campaigns (campaign_code, name, target_url, is_active, is_default, notes, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$code, $name, $url, $isActive, $isDefault, $notes]);
        $campaignId = (int)$this->db->lastInsertId();

        $this->auditLog->log($userId, 'CREATE_CAMPAIGN', 'campaign', $campaignId, null, json_encode([
            'name' => $name,
            'code' => $code,
            'target_url' => $url,
            'is_active' => $isActive,
            'is_default' => $isDefault
        ]));

        return ['success' => true, 'message' => 'Campaign created successfully.', 'id' => $campaignId];
    }

    /**
     * Update an existing campaign & tracking URL
     */
    public function update(int $id, array $data, int $userId): array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Campaign not found.'];
        }

        $name = trim($data['name'] ?? $existing['name']);
        $code = trim($data['campaign_code'] ?? $existing['campaign_code']);
        $url = trim($data['target_url'] ?? $existing['target_url']);
        $isActive = isset($data['is_active']) ? (!empty($data['is_active']) ? 1 : 0) : (int)$existing['is_active'];
        $isDefault = isset($data['is_default']) ? (!empty($data['is_default']) ? 1 : 0) : (int)$existing['is_default'];
        $notes = isset($data['notes']) ? trim($data['notes']) : $existing['notes'];

        if ($name === '') {
            return ['success' => false, 'message' => 'Campaign name cannot be empty.'];
        }
        $testUrl = preg_replace('/\{[a-zA-Z0-9_-]+\}/', 'macro', $url);
        $testUrl = preg_replace('/\[[a-zA-Z0-9_-]+\]/', 'macro', (string)$testUrl);
        if (!filter_var($testUrl, FILTER_VALIDATE_URL) || (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://'))) {
            return ['success' => false, 'message' => 'A valid HTTP/HTTPS affiliate tracking URL is required.'];
        }

        // If code changed, check uniqueness
        if ($code !== $existing['campaign_code']) {
            $duplicate = $this->getByCode($code);
            if ($duplicate && (int)$duplicate['id'] !== $id) {
                return ['success' => false, 'message' => 'Campaign code is already taken.'];
            }
        }

        // If setting as default, clear others
        if ($isDefault) {
            $this->db->prepare("UPDATE campaigns SET is_default = 0 WHERE id != ?")->execute([$id]);
        }

        $stmt = $this->db->prepare("
            UPDATE campaigns 
            SET campaign_code = ?, name = ?, target_url = ?, is_active = ?, is_default = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$code, $name, $url, $isActive, $isDefault, $notes, $id]);

        // Audit Log tracking URL or status modifications
        $changes = [];
        if ($existing['target_url'] !== $url) {
            $changes['target_url'] = ['old' => $existing['target_url'], 'new' => $url];
        }
        if ((int)$existing['is_active'] !== $isActive) {
            $changes['is_active'] = ['old' => $existing['is_active'], 'new' => $isActive];
        }
        if ((int)$existing['is_default'] !== $isDefault) {
            $changes['is_default'] = ['old' => $existing['is_default'], 'new' => $isDefault];
        }

        if (!empty($changes)) {
            $this->auditLog->log(
                $userId,
                'UPDATE_CAMPAIGN',
                'campaign',
                $id,
                json_encode($existing),
                json_encode($changes)
            );
        }

        return ['success' => true, 'message' => 'Campaign updated successfully.'];
    }

    /**
     * Delete campaign
     */
    public function delete(int $id, int $userId): array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Campaign not found.'];
        }

        // Don't delete if it's the only default campaign
        if ($existing['is_default']) {
            $activeCount = (int)$this->db->query("SELECT COUNT(*) FROM campaigns WHERE id != {$id}")->fetchColumn();
            if ($activeCount > 0) {
                // designate another as default
                $this->db->exec("UPDATE campaigns SET is_default = 1 WHERE id != {$id} LIMIT 1");
            }
        }

        $stmt = $this->db->prepare("DELETE FROM campaigns WHERE id = ?");
        $stmt->execute([$id]);

        $this->auditLog->log($userId, 'DELETE_CAMPAIGN', 'campaign', $id, json_encode($existing), null);

        return ['success' => true, 'message' => 'Campaign deleted.'];
    }

    /**
     * Set campaign as default
     */
    public function setDefault(int $id, int $userId): bool
    {
        $this->db->beginTransaction();
        try {
            $this->db->exec("UPDATE campaigns SET is_default = 0");
            $stmt = $this->db->prepare("UPDATE campaigns SET is_default = 1, is_active = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $this->db->commit();

            $this->auditLog->log($userId, 'SET_DEFAULT_CAMPAIGN', 'campaign', $id, null, 'Set as primary default');
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Test a tracking URL destination before activating
     */
    public function testUrl(string $url): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'status' => 0, 'message' => 'Invalid URL syntax.'];
        }

        $settings = (new Settings())->getAll();
        $allowedRules = explode("\n", (string)($settings['allowed_redirect_domains'] ?? ''));
        $isWhitelisted = Helpers::isDomainAllowed($url, $allowedRules);

        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';

        // Attempt a quick cURL HEAD request with 4s timeout
        $httpCode = 0;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 TestCheck/1.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true
        ]);
        curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        if (PHP_VERSION_ID < 80500) {
            @curl_close($ch);
        }

        $isReachable = ($httpCode >= 200 && $httpCode < 400) || $httpCode === 403; // some CDNs return 403 to automated HEAD

        return [
            'valid' => true,
            'host' => $host,
            'is_whitelisted' => $isWhitelisted,
            'http_status' => $httpCode,
            'is_reachable' => $isReachable,
            'error' => $error ?: null
        ];
    }
}
