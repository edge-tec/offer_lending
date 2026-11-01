<?php
declare(strict_types=1);

namespace App;

/**
 * Centralized Offer & Multi-Affiliate Destination Management
 */
class Offer
{
    private \PDO $db;
    private AuditLog $auditLog;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->auditLog = new AuditLog();
    }

    /**
     * Get all offers with aggregated performance metrics
     */
    public function getAll(string $status = 'all'): array
    {
        $where = $status !== 'all' ? "WHERE o.status = " . $this->db->quote($status) : "";
        $sql = "
            SELECT 
                o.*,
                COUNT(DISTINCT lpo.landing_page_id) as assigned_pages_count,
                COUNT(DISTINCT oc.id) as total_offer_clicks,
                COUNT(DISTINCT conv.id) as total_conversions,
                COALESCE(SUM(conv.payout), 0) as total_revenue
            FROM offers o
            LEFT JOIN landing_page_offers lpo ON lpo.offer_id = o.id
            LEFT JOIN offer_clicks oc ON oc.offer_id = o.id
            LEFT JOIN conversions conv ON conv.offer_id = o.id AND conv.status = 'approved'
            {$where}
            GROUP BY o.id
            ORDER BY o.priority DESC, o.id DESC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get single offer by ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM offers WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Get single offer by code
     */
    public function getByCode(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM offers WHERE offer_code = ? LIMIT 1");
        $stmt->execute([$code]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Create a new offer
     */
    public function create(array $data, int $userId): array
    {
        $name = trim($data['name'] ?? '');
        $code = trim($data['offer_code'] ?? '');
        $destUrl = trim($data['destination_website'] ?? '');
        $affUrl = trim($data['affiliate_tracking_url'] ?? '');

        if ($name === '') {
            return ['success' => false, 'message' => 'Offer name is required.'];
        }
        if ($code === '') {
            $code = preg_replace('/[^a-z0-9_-]/i', '-', strtolower($name));
        }
        $testUrl = preg_replace('/\{[a-zA-Z0-9_-]+\}/', 'macro', $affUrl);
        $testUrl = preg_replace('/\[[a-zA-Z0-9_-]+\]/', 'macro', (string)$testUrl);
        if (!filter_var($testUrl, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => 'A valid HTTP/HTTPS affiliate tracking URL is required.'];
        }
        if ($this->getByCode($code)) {
            return ['success' => false, 'message' => "Offer code '{$code}' is already in use."];
        }

        // Auto-whitelist destination domain
        $this->whitelistDomain($affUrl, $userId);

        $stmt = $this->db->prepare("
            INSERT INTO offers (
                name, offer_code, destination_website, affiliate_tracking_url,
                description, image_url, cta_text, geo_targets, device_targets,
                priority, status, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ");

        $stmt->execute([
            $name,
            $code,
            $destUrl ?: $affUrl,
            $affUrl,
            trim($data['description'] ?? ''),
            trim($data['image_url'] ?? '/assets/images/hero-couple.jpg'),
            trim($data['cta_text'] ?? 'Explore Now'),
            trim($data['geo_targets'] ?? 'ALL'),
            trim($data['device_targets'] ?? 'ALL'),
            (int)($data['priority'] ?? 50),
            $data['status'] ?? 'active'
        ]);

        $offerId = (int)$this->db->lastInsertId();
        $this->auditLog->log($userId, 'CREATE_OFFER', 'offer', $offerId, null, json_encode($data));

        return ['success' => true, 'message' => 'Offer created successfully.', 'id' => $offerId];
    }

    /**
     * Update existing offer
     */
    public function update(int $id, array $data, int $userId): array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Offer not found.'];
        }

        $code = trim($data['offer_code'] ?? $existing['offer_code']);
        $affUrl = trim($data['affiliate_tracking_url'] ?? $existing['affiliate_tracking_url']);

        $testUrl = preg_replace('/\{[a-zA-Z0-9_-]+\}/', 'macro', $affUrl);
        $testUrl = preg_replace('/\[[a-zA-Z0-9_-]+\]/', 'macro', (string)$testUrl);
        if (!filter_var($testUrl, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => 'A valid HTTP/HTTPS affiliate tracking URL is required.'];
        }

        if ($code !== $existing['offer_code']) {
            $duplicate = $this->getByCode($code);
            if ($duplicate && (int)$duplicate['id'] !== $id) {
                return ['success' => false, 'message' => "Offer code '{$code}' is already in use."];
            }
        }

        // Auto-whitelist domain
        $this->whitelistDomain($affUrl, $userId);

        $stmt = $this->db->prepare("
            UPDATE offers SET
                name = ?,
                offer_code = ?,
                destination_website = ?,
                affiliate_tracking_url = ?,
                description = ?,
                image_url = ?,
                cta_text = ?,
                geo_targets = ?,
                device_targets = ?,
                priority = ?,
                status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $stmt->execute([
            trim($data['name'] ?? $existing['name']),
            $code,
            trim($data['destination_website'] ?? $existing['destination_website']),
            $affUrl,
            trim($data['description'] ?? $existing['description']),
            trim($data['image_url'] ?? $existing['image_url']),
            trim($data['cta_text'] ?? $existing['cta_text']),
            trim($data['geo_targets'] ?? $existing['geo_targets']),
            trim($data['device_targets'] ?? $existing['device_targets']),
            (int)($data['priority'] ?? $existing['priority']),
            trim($data['status'] ?? $existing['status']),
            $id
        ]);

        $this->auditLog->log($userId, 'UPDATE_OFFER', 'offer', $id, json_encode($existing), json_encode($data));

        return ['success' => true, 'message' => 'Offer updated successfully. All associated landing page buttons now use the updated tracking URL.'];
    }

    /**
     * Delete offer
     */
    public function delete(int $id, int $userId): array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Offer not found.'];
        }

        $stmt = $this->db->prepare("DELETE FROM offers WHERE id = ?");
        $stmt->execute([$id]);

        $this->auditLog->log($userId, 'DELETE_OFFER', 'offer', $id, json_encode($existing), null);

        return ['success' => true, 'message' => 'Offer deleted.'];
    }

    /**
     * Toggle status between active and paused
     */
    public function toggleStatus(int $id, int $userId): bool
    {
        $existing = $this->getById($id);
        if (!$existing) return false;

        $newStatus = $existing['status'] === 'active' ? 'paused' : 'active';
        $stmt = $this->db->prepare("UPDATE offers SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        $this->auditLog->log($userId, 'TOGGLE_OFFER_STATUS', 'offer', $id, $existing['status'], $newStatus);
        return true;
    }

    /**
     * Auto-whitelist the destination domain for open-redirect safety
     */
    private function whitelistDomain(string $url, int $userId): void
    {
        $parsedHost = parse_url($url, PHP_URL_HOST);
        if (!$parsedHost) return;

        $settings = new Settings();
        $allowed = (string)$settings->get('allowed_redirect_domains', '');
        $allowedRules = array_filter(array_map('trim', explode("\n", $allowed)));

        if (!Helpers::isDomainAllowed($url, $allowedRules)) {
            $allowedRules[] = $parsedHost;
            $allowedRules[] = '*.' . $parsedHost;
            $settings->updateMany(['allowed_redirect_domains' => implode("\n", array_unique($allowedRules))], $userId);
        }
    }
}

