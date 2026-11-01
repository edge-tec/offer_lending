<?php
declare(strict_types=1);

namespace App;

/**
 * Short Tracking Link Engine & Multi-Offer Mapping
 */
class ShortLink
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Resolve short code e.g. fwb01, ib02 to its landing page
     */
    public function resolve(string $code): ?array
    {
        $stmt = $this->db->prepare("
            SELECT sl.*, lp.slug as page_slug, lp.status as page_status
            FROM short_links sl
            JOIN landing_pages lp ON lp.id = sl.landing_page_id
            WHERE sl.short_code = ? AND sl.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$code]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Record a click on the short link
     */
    public function recordClick(int $shortLinkId): void
    {
        $stmt = $this->db->prepare("UPDATE short_links SET clicks_count = clicks_count + 1 WHERE id = ?");
        $stmt->execute([$shortLinkId]);
    }

    /**
     * Get all short links with performance
     */
    public function getAll(): array
    {
        $sql = "
            SELECT 
                sl.*,
                lp.name as page_name,
                lp.slug as page_slug,
                lp.target_dating_website,
                c.name as campaign_name,
                COUNT(DISTINCT lpv.id) as unique_visitors,
                COUNT(DISTINCT oc.id) as total_offer_clicks
            FROM short_links sl
            JOIN landing_pages lp ON lp.id = sl.landing_page_id
            LEFT JOIN campaigns c ON c.id = sl.campaign_id
            LEFT JOIN landing_page_visitors lpv ON lpv.short_link_id = sl.id
            LEFT JOIN offer_clicks oc ON oc.short_link_id = sl.id
            GROUP BY sl.id
            ORDER BY sl.id DESC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Create or update custom short link
     */
    public function save(int $pageId, string $shortCode, ?int $campaignId = null): array
    {
        $shortCode = strtolower(trim($shortCode));
        if ($shortCode === '') {
            return ['success' => false, 'message' => 'Short code is required.'];
        }

        // Check unique code
        $stmt = $this->db->prepare("SELECT id, landing_page_id FROM short_links WHERE short_code = ? LIMIT 1");
        $stmt->execute([$shortCode]);
        $existing = $stmt->fetch();

        if ($existing && (int)$existing['landing_page_id'] !== $pageId) {
            return ['success' => false, 'message' => "Short code '{$shortCode}' is already in use by another page."];
        }

        if ($existing) {
            $up = $this->db->prepare("UPDATE short_links SET campaign_id = ?, is_active = 1 WHERE id = ?");
            $up->execute([$campaignId, $existing['id']]);
        } else {
            $ins = $this->db->prepare("INSERT INTO short_links (short_code, landing_page_id, campaign_id, is_active, created_at) VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)");
            $ins->execute([$shortCode, $pageId, $campaignId]);
        }

        return ['success' => true, 'message' => 'Short link saved.'];
    }
}

