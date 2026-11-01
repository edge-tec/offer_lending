<?php
declare(strict_types=1);

/**
 * Enterprise Dashboard Overview
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\Helpers;
use App\Database;

$auth = new Auth();
$auth->requireLogin();
$db = Database::getInstance()->getConnection();

// 1. High Level Metrics
$totalPages = (int)$db->query("SELECT COUNT(*) FROM landing_pages")->fetchColumn();
$publishedPages = (int)$db->query("SELECT COUNT(*) FROM landing_pages WHERE status = 'published'")->fetchColumn();
$draftPages = (int)$db->query("SELECT COUNT(*) FROM landing_pages WHERE status = 'draft'")->fetchColumn();

$totalOffers = (int)$db->query("SELECT COUNT(*) FROM offers")->fetchColumn();
$activeOffers = (int)$db->query("SELECT COUNT(*) FROM offers WHERE status = 'active'")->fetchColumn();

$totalShortLinks = (int)$db->query("SELECT COUNT(*) FROM short_links WHERE is_active = 1")->fetchColumn();

// Clicks & Visitors
$totalVisitors = (int)$db->query("SELECT COUNT(*) FROM landing_page_visitors")->fetchColumn();
$uniqueVisitors = (int)$db->query("SELECT COUNT(DISTINCT ip_hash) FROM landing_page_visitors")->fetchColumn();

$totalOfferClicks = (int)$db->query("SELECT COUNT(*) FROM offer_clicks")->fetchColumn();
$totalCtaClicks = $totalOfferClicks + (int)$db->query("SELECT COUNT(*) FROM clicks")->fetchColumn();

// Conversions & Revenue
$totalConversions = (int)$db->query("SELECT COUNT(*) FROM conversions WHERE status = 'approved'")->fetchColumn();
$totalRevenue = (float)$db->query("SELECT COALESCE(SUM(payout), 0) FROM conversions WHERE status = 'approved'")->fetchColumn();
$overallConversionRate = $totalOfferClicks > 0 ? round(($totalConversions / $totalOfferClicks) * 100, 2) : 0.0;

// Top Performing Landing Pages
$topPages = $db->query("
    SELECT 
        lp.id, lp.name, lp.slug, lp.status,
        sl.short_code,
        COUNT(DISTINCT lpv.id) as visitors_count,
        COUNT(DISTINCT oc.id) as clicks_count
    FROM landing_pages lp
    LEFT JOIN short_links sl ON sl.landing_page_id = lp.id
    LEFT JOIN landing_page_visitors lpv ON lpv.landing_page_id = lp.id
    LEFT JOIN offer_clicks oc ON oc.landing_page_id = lp.id
    GROUP BY lp.id
    ORDER BY clicks_count DESC, visitors_count DESC
    LIMIT 5
")->fetchAll();

// Top Performing Offers
$topOffers = $db->query("
    SELECT 
        o.id, o.name, o.offer_code, o.status, o.affiliate_tracking_url,
        COUNT(DISTINCT oc.id) as clicks_count,
        COUNT(DISTINCT conv.id) as conversions_count,
        COALESCE(SUM(conv.payout), 0) as total_payout
    FROM offers o
    LEFT JOIN offer_clicks oc ON oc.offer_id = o.id
    LEFT JOIN conversions conv ON conv.offer_id = o.id AND conv.status = 'approved'
    GROUP BY o.id
    ORDER BY clicks_count DESC
    LIMIT 5
")->fetchAll();

$currentPage = 'dashboard';
$pageTitle = 'Enterprise Dashboard Overview';
require_once __DIR__ . '/layout/header.php';
?>

<!-- Metric Badges Row -->
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <p style="color: var(--text-muted); font-size: 0.92rem;">
            Centralized management hub for multi-offer dating affiliate landing pages, dynamic tracking, and conversions.
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="/admin/page-edit.php" class="btn btn-primary btn-sm">
            <span>+ Create New Page</span>
        </a>
        <a href="/admin/offers.php" class="btn btn-secondary btn-sm">
            <span>+ Add Offer</span>
        </a>
    </div>
</div>

<!-- Primary Stats Grid (8 Core Metrics) -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Landing Pages</div>
        <div class="stat-value"><?= number_format($totalPages) ?></div>
        <div class="stat-sub positive"><?= $publishedPages ?> Published &bull; <?= $draftPages ?> Draft</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Dating Offers</div>
        <div class="stat-value"><?= number_format($totalOffers) ?></div>
        <div class="stat-sub highlight"><?= $activeOffers ?> Active Offers</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Total Visitors</div>
        <div class="stat-value"><?= number_format($uniqueVisitors) ?></div>
        <div class="stat-sub"><?= number_format($totalVisitors) ?> Total Pageviews</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Total Outgoing Clicks</div>
        <div class="stat-value text-gradient"><?= number_format($totalCtaClicks) ?></div>
        <div class="stat-sub highlight"><?= number_format($totalOfferClicks) ?> Multi-Offer Clicks</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Short Tracking Links</div>
        <div class="stat-value"><?= number_format($totalShortLinks) ?></div>
        <div class="stat-sub positive">Auto-Mapped to Pages</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">S2S Conversions</div>
        <div class="stat-value"><?= number_format($totalConversions) ?></div>
        <div class="stat-sub positive">Postback Verified</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Conversion Rate</div>
        <div class="stat-value"><?= $overallConversionRate ?>%</div>
        <div class="stat-sub">Offer-to-Conversion</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Affiliate Revenue</div>
        <div class="stat-value" style="color: #34d399;">$<?= number_format($totalRevenue, 2) ?></div>
        <div class="stat-sub positive">Approved Earnings</div>
    </div>
</div>

<!-- Visual Shortcut & Quick Launch Bar -->
<div class="card" style="background: linear-gradient(135deg, rgba(244, 63, 94, 0.08), rgba(139, 92, 246, 0.08)), var(--admin-card); border-color: rgba(244, 63, 94, 0.25);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.35rem;">Live Initial Dating Websites (Ready to Promote)</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem;">Each dedicated landing page has its own direct URL and unique short tracking link:</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="/friends-with-benefits" target="_blank" class="btn btn-secondary btn-sm">FWB (/friends-with-benefits)</a>
            <a href="/instabang" target="_blank" class="btn btn-secondary btn-sm">InstaBang (/instabang)</a>
            <a href="/zoomflirts" target="_blank" class="btn btn-secondary btn-sm">ZoomFlirts (/zoomflirts)</a>
            <a href="/cindymatches" target="_blank" class="btn btn-secondary btn-sm">CindyMatches (/cindymatches)</a>
        </div>
    </div>
</div>

<!-- Two Column Table: Top Landing Pages & Top Offers -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Top Landing Pages -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h2 class="card-title">Top Performing Landing Pages</h2>
            <a href="/admin/pages.php" class="btn btn-secondary btn-sm">View All (<?= $totalPages ?>)</a>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Page Name</th>
                        <th>Short Link</th>
                        <th>Visitors</th>
                        <th>Clicks</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topPages as $tp): ?>
                        <tr>
                            <td>
                                <strong><?= Helpers::e($tp['name']) ?></strong>
                                <div style="font-size: 0.75rem; color: var(--text-dim);">/<?= Helpers::e($tp['slug']) ?></div>
                            </td>
                            <td>
                                <?php if (!empty($tp['short_code'])): ?>
                                    <div style="display: flex; align-items: center; gap: 0.35rem;">
                                        <code>/go/<?= Helpers::e($tp['short_code']) ?></code>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="copyToClipboard('/go/<?= Helpers::e($tp['short_code']) ?>', this)" style="padding: 0.15rem 0.45rem; font-size: 0.7rem;" title="Copy Short Link">
                                            📋
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-dim);">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format((int)$tp['visitors_count']) ?></td>
                            <td style="font-weight: 700;"><?= number_format((int)$tp['clicks_count']) ?></td>
                            <td>
                                <div style="display: inline-flex; gap: 0.35rem;">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="copyToClipboard('/<?= Helpers::e($tp['slug']) ?>', this)" style="padding: 0.2rem 0.5rem; font-size: 0.72rem;" title="Copy Page URL">
                                        📋 Copy
                                    </button>
                                    <a href="/<?= Helpers::e($tp['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Open Page">↗</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Offers -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h2 class="card-title">Top Performing Dating Offers</h2>
            <a href="/admin/offers.php" class="btn btn-secondary btn-sm">Manage Offers (<?= $totalOffers ?>)</a>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Offer Name</th>
                        <th>Status</th>
                        <th>Clicks</th>
                        <th>Conv.</th>
                        <th>Payout</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topOffers as $to): ?>
                        <tr>
                            <td>
                                <strong><?= Helpers::e($to['name']) ?></strong>
                                <div style="font-size: 0.75rem; color: var(--text-dim); max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= Helpers::e($to['affiliate_tracking_url']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-success"><?= Helpers::e($to['status']) ?></span>
                            </td>
                            <td style="font-weight: 700;"><?= number_format((int)$to['clicks_count']) ?></td>
                            <td><?= number_format((int)$to['conversions_count']) ?></td>
                            <td style="color: #34d399; font-weight: 700;">$<?= number_format((float)$to['total_payout'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
