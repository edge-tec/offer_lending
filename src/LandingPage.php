<?php
declare(strict_types=1);

namespace App;

/**
 * Enterprise Landing Page Engine & Section Builder
 */
class LandingPage
{
    private \PDO $db;
    private AuditLog $auditLog;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->auditLog = new AuditLog();
    }

    /**
     * Get all landing pages with metrics
     */
    public function getAll(string $status = 'all'): array
    {
        $where = $status !== 'all' ? "WHERE lp.status = " . $this->db->quote($status) : "";
        $sql = "
            SELECT 
                lp.*,
                d.domain_name,
                c.name as campaign_name,
                t.name as template_name,
                sl.short_code,
                COUNT(DISTINCT lpv.id) as total_visitors,
                COUNT(DISTINCT oc.id) as total_clicks
            FROM landing_pages lp
            LEFT JOIN domains d ON d.id = lp.domain_id
            LEFT JOIN campaigns c ON c.id = lp.campaign_id
            LEFT JOIN landing_page_templates t ON t.id = lp.template_id
            LEFT JOIN short_links sl ON sl.landing_page_id = lp.id AND sl.is_active = 1
            LEFT JOIN landing_page_visitors lpv ON lpv.landing_page_id = lp.id
            LEFT JOIN offer_clicks oc ON oc.landing_page_id = lp.id
            {$where}
            GROUP BY lp.id
            ORDER BY lp.id DESC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get single landing page by slug
     */
    public function getBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("
            SELECT lp.*, d.domain_name, sl.short_code
            FROM landing_pages lp
            LEFT JOIN domains d ON d.id = lp.domain_id
            LEFT JOIN short_links sl ON sl.landing_page_id = lp.id AND sl.is_active = 1
            WHERE lp.slug = ?
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Get single landing page by ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT lp.*, sl.short_code
            FROM landing_pages lp
            LEFT JOIN short_links sl ON sl.landing_page_id = lp.id AND sl.is_active = 1
            WHERE lp.id = ? 
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Get associated offers for a landing page
     */
    public function getOffers(int $pageId): array
    {
        $sql = "
            SELECT 
                o.*,
                lpo.display_order,
                lpo.is_featured,
                COALESCE(NULLIF(lpo.custom_cta_text, ''), o.cta_text) as final_cta_text,
                lpo.custom_badge
            FROM landing_page_offers lpo
            JOIN offers o ON o.id = lpo.offer_id
            WHERE lpo.landing_page_id = ? AND o.status = 'active'
            ORDER BY lpo.display_order ASC, o.priority DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pageId]);
        return $stmt->fetchAll();
    }

    /**
     * Get sections for a landing page
     */
    public function getSections(int $pageId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM landing_page_sections
            WHERE landing_page_id = ? AND is_visible = 1
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$pageId]);
        return $stmt->fetchAll();
    }

    /**
     * Create a new landing page
     */
    public function create(array $data, int $userId): array
    {
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '');
        if ($name === '') {
            return ['success' => false, 'message' => 'Landing page name is required.'];
        }
        if ($slug === '') {
            $slug = preg_replace('/[^a-z0-9_-]/i', '-', strtolower($name));
        }

        // Check unique slug
        if ($this->getBySlug($slug)) {
            return ['success' => false, 'message' => "Slug '{$slug}' is already taken. Please choose another."];
        }

        $stmt = $this->db->prepare("
            INSERT INTO landing_pages (
                domain_id, campaign_id, slug, name, target_dating_website,
                page_title, meta_description, hero_headline, hero_subtitle,
                hero_cta_text, hero_image, theme_color_primary, theme_color_accent,
                status, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ");

        $stmt->execute([
            !empty($data['domain_id']) ? (int)$data['domain_id'] : 1,
            !empty($data['campaign_id']) ? (int)$data['campaign_id'] : null,
            $slug,
            $name,
            trim($data['target_dating_website'] ?? ''),
            trim($data['page_title'] ?? $name),
            trim($data['meta_description'] ?? ''),
            trim($data['hero_headline'] ?? $name),
            trim($data['hero_subtitle'] ?? ''),
            trim($data['hero_cta_text'] ?? 'EXPLORE NOW'),
            trim($data['hero_image'] ?? '/assets/images/hero-couple.jpg'),
            trim($data['theme_color_primary'] ?? '#f43f5e'),
            trim($data['theme_color_accent'] ?? '#8b5cf6'),
            $data['status'] ?? 'published'
        ]);

        $pageId = (int)$this->db->lastInsertId();

        // Automatically assign unique short tracking link
        $shortCode = trim($data['short_code'] ?? '');
        if ($shortCode === '') {
            $shortCode = substr(preg_replace('/[^a-z0-9]/i', '', $slug), 0, 4) . rand(10, 99);
        }
        $this->ensureShortLink($pageId, $shortCode);

        // Sync offers
        if (!empty($data['offer_ids']) && is_array($data['offer_ids'])) {
            $this->syncOffers($pageId, $data['offer_ids']);
        }

        // Create initial sections
        $this->createDefaultSections($pageId);

        $this->auditLog->log($userId, 'CREATE_PAGE', 'landing_page', $pageId, null, json_encode($data));

        return ['success' => true, 'message' => 'Landing page created successfully.', 'id' => $pageId, 'slug' => $slug];
    }

    /**
     * Update an existing landing page
     */
    public function update(int $id, array $data, int $userId): array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Landing page not found.'];
        }

        $slug = trim($data['slug'] ?? $existing['slug']);
        if ($slug !== $existing['slug']) {
            $duplicate = $this->getBySlug($slug);
            if ($duplicate && (int)$duplicate['id'] !== $id) {
                return ['success' => false, 'message' => "Slug '{$slug}' is already in use."];
            }
        }

        $stmt = $this->db->prepare("
            UPDATE landing_pages SET
                name = ?,
                slug = ?,
                target_dating_website = ?,
                page_title = ?,
                meta_description = ?,
                hero_headline = ?,
                hero_subtitle = ?,
                hero_cta_text = ?,
                hero_image = ?,
                theme_color_primary = ?,
                theme_color_accent = ?,
                status = ?,
                custom_css = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $stmt->execute([
            trim($data['name'] ?? $existing['name']),
            $slug,
            trim($data['target_dating_website'] ?? $existing['target_dating_website']),
            trim($data['page_title'] ?? $existing['page_title']),
            trim($data['meta_description'] ?? $existing['meta_description']),
            trim($data['hero_headline'] ?? $existing['hero_headline']),
            trim($data['hero_subtitle'] ?? $existing['hero_subtitle']),
            trim($data['hero_cta_text'] ?? $existing['hero_cta_text']),
            trim($data['hero_image'] ?? $existing['hero_image']),
            trim($data['theme_color_primary'] ?? $existing['theme_color_primary']),
            trim($data['theme_color_accent'] ?? $existing['theme_color_accent']),
            trim($data['status'] ?? $existing['status']),
            trim($data['custom_css'] ?? $existing['custom_css']),
            $id
        ]);

        // Sync offers if provided
        if (isset($data['offer_ids']) && is_array($data['offer_ids'])) {
            $this->syncOffers($id, $data['offer_ids']);
        }

        // Custom short link update if provided
        if (!empty($data['short_code'])) {
            $this->ensureShortLink($id, trim($data['short_code']));
        }

        $this->auditLog->log($userId, 'UPDATE_PAGE', 'landing_page', $id, json_encode($existing), json_encode($data));

        return ['success' => true, 'message' => 'Landing page updated successfully.'];
    }

    /**
     * Duplicate an existing landing page
     */
    public function duplicate(int $id, int $userId): array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Source page not found.'];
        }

        $newSlug = $existing['slug'] . '-copy-' . rand(100, 999);
        $newName = $existing['name'] . ' (Copy)';

        $copyData = $existing;
        $copyData['name'] = $newName;
        $copyData['slug'] = $newSlug;
        $copyData['status'] = 'draft';

        // Fetch associated offer IDs
        $offers = $this->getOffers($id);
        $copyData['offer_ids'] = array_column($offers, 'id');

        return $this->create($copyData, $userId);
    }

    /**
     * Delete landing page
     */
    public function delete(int $id, int $userId): array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Landing page not found.'];
        }

        $stmt = $this->db->prepare("DELETE FROM landing_pages WHERE id = ?");
        $stmt->execute([$id]);

        $this->auditLog->log($userId, 'DELETE_PAGE', 'landing_page', $id, json_encode($existing), null);

        return ['success' => true, 'message' => 'Landing page deleted successfully.'];
    }

    /**
     * Associate multiple offers with a landing page
     */
    public function syncOffers(int $pageId, array $offerIds): void
    {
        $this->db->prepare("DELETE FROM landing_page_offers WHERE landing_page_id = ?")->execute([$pageId]);
        $stmt = $this->db->prepare("
            INSERT INTO landing_page_offers (landing_page_id, offer_id, display_order, is_featured)
            VALUES (?, ?, ?, ?)
        ");
        $order = 1;
        foreach ($offerIds as $offId) {
            $offId = (int)$offId;
            if ($offId > 0) {
                $stmt->execute([$pageId, $offId, $order, $order === 1 ? 1 : 0]);
                $order++;
            }
        }
    }

    /**
     * Ensure a short link exists and points to this landing page
     */
    public function ensureShortLink(int $pageId, string $code): void
    {
        $stmt = $this->db->prepare("SELECT id FROM short_links WHERE landing_page_id = ? LIMIT 1");
        $stmt->execute([$pageId]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            $up = $this->db->prepare("UPDATE short_links SET short_code = ?, is_active = 1 WHERE id = ?");
            $up->execute([$code, $existingId]);
        } else {
            $ins = $this->db->prepare("INSERT INTO short_links (short_code, landing_page_id, is_active, created_at) VALUES (?, ?, 1, CURRENT_TIMESTAMP)");
            $ins->execute([$code, $pageId]);
        }
    }

    /**
     * Create default sections for new landing page
     */
    private function createDefaultSections(int $pageId): void
    {
        $insertSection = $this->db->prepare("
            INSERT INTO landing_page_sections (landing_page_id, section_type, title, subtitle, sort_order, is_visible)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $insertSection->execute([$pageId, 'hero', 'Hero Banner', null, 1]);
        $insertSection->execute([$pageId, 'offers_grid', 'Featured Dating Platforms', 'Top rated dating networks for your connection goals', 2]);
        $insertSection->execute([$pageId, 'why_explore', 'Why It Works', 'Clear intentions, total discretion, and real chemistry', 3]);
        $insertSection->execute([$pageId, 'how_it_works', 'How It Works in 3 Steps', 'Getting connected takes less than two minutes', 4]);
        $insertSection->execute([$pageId, 'faq', 'Frequently Asked Questions', 'Everything you need to know before exploring', 5]);
        $insertSection->execute([$pageId, 'cta_banner', 'Final Call to Action', 'Ready for a new kind of connection?', 6]);
    }
}

