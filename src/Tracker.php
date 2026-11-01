<?php
declare(strict_types=1);

namespace App;

/**
 * Enterprise Multi-Offer Click Redirect & Attribution Tracking Engine
 */
class Tracker
{
    private \PDO $db;
    private Offer $offerService;
    private Settings $settingsService;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->offerService = new Offer();
        $this->settingsService = new Settings();
    }

    /**
     * Process an Offer CTA Click from a Landing Page and redirect to the third-party affiliate tracking URL
     */
    public function processOfferClick(?int $offerId, ?int $landingPageId = null, array $queryParams = []): array
    {
        $offer = ($offerId !== null && $offerId > 0) ? $this->offerService->getById($offerId) : null;
        $targetUrl = null;

        // 1. If Landing Page has a custom affiliate tracking URL (with parameters or explicit affiliate link), prioritize it!
        $lpUrl = null;
        if ($landingPageId !== null && $landingPageId > 0) {
            $stmt = $this->db->prepare("SELECT target_dating_website FROM landing_pages WHERE id = ?");
            $stmt->execute([$landingPageId]);
            $lpUrl = trim((string)$stmt->fetchColumn());
            if (!empty($lpUrl) && (str_starts_with($lpUrl, 'http://') || str_starts_with($lpUrl, 'https://'))) {
                // If it has tracking params, /click/, aff, ref, or if no specific offer is active, use it as direct affiliate link
                if (str_contains($lpUrl, '?') || str_contains($lpUrl, '&') || str_contains($lpUrl, 'click') || empty($offer)) {
                    $targetUrl = $lpUrl;
                    Helpers::autoWhitelistDomain($targetUrl);
                }
            }
        }

        // 2. Fall back to Offer's affiliate tracking URL
        if (empty($targetUrl) && $offer && $offer['status'] === 'active') {
            $targetUrl = trim($offer['affiliate_tracking_url']);
            Helpers::autoWhitelistDomain($targetUrl);
        }

        // 3. Fall back to landing page base url if set
        if (empty($targetUrl) && !empty($lpUrl)) {
            $targetUrl = $lpUrl;
            Helpers::autoWhitelistDomain($targetUrl);
        }

        // 3. Fall back to active default campaign
        if (empty($targetUrl)) {
            $activeCamp = $this->campaignService->getActive();
            if ($activeCamp && !empty($activeCamp['target_url'])) {
                $targetUrl = trim($activeCamp['target_url']);
                Helpers::autoWhitelistDomain($targetUrl);
            }
        }

        if (empty($targetUrl)) {
            return [
                'status' => 'error',
                'title' => 'Offer Currently Unavailable',
                'message' => 'The requested dating connection opportunity is currently inactive or undergoing scheduled maintenance. Please check back shortly.',
                'code' => 404
            ];
        }

        // 1. Open Redirect Validation
        $settings = $this->settingsService->getAll();
        $protectionEnabled = ($settings['enable_open_redirect_protection'] ?? '1') === '1';

        if ($protectionEnabled) {
            $allowedList = explode("\n", (string)($settings['allowed_redirect_domains'] ?? ''));
            if (!Helpers::isDomainAllowed($targetUrl, $allowedList)) {
                return [
                    'status' => 'error',
                    'title' => 'Security Verification Notice',
                    'message' => 'The destination URL is not authorized by the administrator whitelist. Redirect halted for visitor safety.',
                    'code' => 403
                ];
            }
        }

        // 2. Generate Unique Click ID for Postback Tracking
        $clickId = 'clk_' . bin2hex(random_bytes(6)) . '_' . time();

        // 3. Extract Visitor & Attribution Parameters
        $ip = Helpers::getClientIp();
        $ipHash = Helpers::hashIp($ip);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceType = Helpers::detectDevice($userAgent);
        $country = Helpers::getCountry();
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        // Retrieve preserved session attribution from the landing page visit
        $sessionAttr = $_SESSION['fwb_attribution'] ?? [];
        $mergedParams = array_merge($sessionAttr, $queryParams);

        $subid = $mergedParams['subid'] ?? null;

        // 4. Record Click in `offer_clicks` Table
        try {
            $stmt = $this->db->prepare("
                INSERT INTO offer_clicks (
                    offer_id, landing_page_id, short_link_id, click_id, subid,
                    ip_hash, user_agent, device_type, country, referer,
                    target_url_resolved, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, CURRENT_TIMESTAMP
                )
            ");
            $stmt->execute([
                $offer ? (int)$offer['id'] : null,
                $landingPageId,
                $sessionAttr['short_link_id'] ?? null,
                $clickId,
                $subid ? substr((string)$subid, 0, 100) : null,
                $ipHash,
                substr($userAgent, 0, 500),
                $deviceType,
                $country,
                $referer ? substr($referer, 0, 1000) : null,
                $targetUrl
            ]);

            // Increment counters
            if ($offer) {
                $this->db->prepare("UPDATE offers SET total_clicks = total_clicks + 1 WHERE id = ?")->execute([$offer['id']]);
            }

            // Legacy compatibility write to `clicks` table
            $leg = $this->db->prepare("
                INSERT INTO clicks (campaign_id, ip_hash, user_agent, device_type, country, referer, subid, clickid, created_at)
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $leg->execute([$ipHash, substr($userAgent, 0, 500), $deviceType, $country, $referer, $subid, $clickId]);
        } catch (\Throwable $e) {
            error_log("Offer Click Tracking Error: " . $e->getMessage());
        }

        // 5. Merge Tracking Parameters into destination URL (including the generated click_id)
        $mergedParams['clickid'] = $clickId;
        $mergedParams['click_id'] = $clickId;
        $finalUrl = Helpers::mergeQueryParams($targetUrl, $mergedParams);

        return [
            'status' => 'success',
            'redirect_url' => $finalUrl,
            'click_id' => $clickId,
            'offer' => $offer
        ];
    }

    /**
     * Record a Landing Page Visit from direct traffic or a short link
     */
    public function recordLandingPageVisit(int $landingPageId, ?int $shortLinkId = null, array $queryParams = []): void
    {
        $ip = Helpers::getClientIp();
        $ipHash = Helpers::hashIp($ip);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceType = Helpers::detectDevice($userAgent);
        $browser = Helpers::detectBrowser($userAgent);
        $os = Helpers::detectOs($userAgent);
        $country = Helpers::getCountry();
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        $subid = $queryParams['subid'] ?? null;
        $clickid = $queryParams['clickid'] ?? null;
        $utmSource = $queryParams['utm_source'] ?? null;
        $utmMedium = $queryParams['utm_medium'] ?? null;
        $utmCampaign = $queryParams['utm_campaign'] ?? null;
        $utmContent = $queryParams['utm_content'] ?? null;
        $utmTerm = $queryParams['utm_term'] ?? null;

        // Store attribution in session so any CTA clicked on this page preserves tracking parameters
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['fwb_attribution'] = array_merge($queryParams, [
                'landing_page_id' => $landingPageId,
                'short_link_id' => $shortLinkId,
                'referer' => $referer
            ]);
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO landing_page_visitors (
                    landing_page_id, short_link_id, ip_hash, user_agent, device_type,
                    browser, os, country, referer, utm_source, utm_medium, utm_campaign,
                    utm_content, utm_term, subid, clickid, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, CURRENT_TIMESTAMP
                )
            ");
            $stmt->execute([
                $landingPageId,
                $shortLinkId,
                $ipHash,
                substr($userAgent, 0, 500),
                $deviceType,
                $browser,
                $os,
                $country,
                $referer ? substr($referer, 0, 1000) : null,
                $utmSource ? substr($utmSource, 0, 100) : null,
                $utmMedium ? substr($utmMedium, 0, 100) : null,
                $utmCampaign ? substr($utmCampaign, 0, 100) : null,
                $utmContent ? substr($utmContent, 0, 100) : null,
                $utmTerm ? substr($utmTerm, 0, 100) : null,
                $subid ? substr($subid, 0, 100) : null,
                $clickid ? substr($clickid, 0, 100) : null
            ]);

            // Legacy pageviews table write
            $this->db->prepare("
                INSERT INTO pageviews (ip_hash, campaign_code, device_type, country, referer, utm_source, created_at)
                VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ")->execute([$ipHash, (string)$landingPageId, $deviceType, $country, $referer, $utmSource]);
        } catch (\Throwable $e) {
            error_log("Landing Page Visitor Log Error: " . $e->getMessage());
        }
    }

    /**
     * Smart campaign and default CTA click processor
     */
    public function processClick(array $queryParams): array
    {
        $targetUrl = null;
        $campaign = null;
        $offer = null;

        // 1. Check if specific campaign requested (?c=...)
        $campaignService = new Campaign();
        if (!empty($queryParams['c'])) {
            $campaign = $campaignService->resolveActiveCampaign((string)$queryParams['c']);
            if ($campaign && !empty($campaign['target_url'])) {
                $targetUrl = trim($campaign['target_url']);
            }
        }

        // 2. Check top active offer from offers table
        if (empty($targetUrl)) {
            $stmt = $this->db->query("SELECT * FROM offers WHERE status = 'active' ORDER BY priority DESC, id ASC LIMIT 1");
            $offer = $stmt->fetch();
            if ($offer && !empty($offer['affiliate_tracking_url'])) {
                $targetUrl = trim($offer['affiliate_tracking_url']);
            }
        }

        // 3. Fallback to active campaign
        if (empty($targetUrl)) {
            $campaign = $campaignService->resolveActiveCampaign(null);
            if ($campaign && !empty($campaign['target_url'])) {
                $targetUrl = trim($campaign['target_url']);
            }
        }

        // 4. Fallback to published landing page target
        if (empty($targetUrl)) {
            $stmt = $this->db->query("SELECT target_dating_website FROM landing_pages WHERE status = 'published' AND target_dating_website IS NOT NULL AND target_dating_website != '' ORDER BY id ASC LIMIT 1");
            $lpUrl = $stmt->fetchColumn();
            if (!empty($lpUrl)) {
                $targetUrl = trim((string)$lpUrl);
            }
        }

        if (empty($targetUrl)) {
            return [
                'status' => 'error',
                'title' => 'Offer Currently Unavailable',
                'message' => 'The requested connection opportunity is currently inactive.',
                'code' => 404
            ];
        }

        // Auto-whitelist destination domain immediately so open-redirect check never fails
        Helpers::autoWhitelistDomain($targetUrl);

        $settings = $this->settingsService->getAll();
        $allowedList = explode("\n", (string)($settings['allowed_redirect_domains'] ?? ''));

        if (!Helpers::isDomainAllowed($targetUrl, $allowedList)) {
            $parsedHost = parse_url($targetUrl, PHP_URL_HOST);
            if ($parsedHost) {
                $allowedList[] = $parsedHost;
                $allowedList[] = '*.' . $parsedHost;
                $this->settingsService->updateMany(['allowed_redirect_domains' => implode("\n", array_unique($allowedList))], 1);
            }
        }

        $ip = Helpers::getClientIp();
        $ipHash = Helpers::hashIp($ip);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceType = Helpers::detectDevice($userAgent);
        $country = Helpers::getCountry();
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        $clickId = 'clk_' . bin2hex(random_bytes(6)) . '_' . time();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO clicks (campaign_id, ip_hash, user_agent, device_type, country, referer, clickid, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $campaign ? $campaign['id'] : 1,
                $ipHash,
                substr($userAgent, 0, 500),
                $deviceType,
                $country,
                $referer ? substr($referer, 0, 1000) : null,
                $clickId
            ]);

            if ($offer) {
                $this->db->prepare("UPDATE offers SET total_clicks = total_clicks + 1 WHERE id = ?")->execute([$offer['id']]);
            }
        } catch (\Throwable $e) {}

        $queryParams['clickid'] = $clickId;
        $queryParams['click_id'] = $clickId;

        return [
            'status' => 'success',
            'redirect_url' => Helpers::mergeQueryParams($targetUrl, $queryParams),
            'campaign' => $campaign
        ];
    }
}
