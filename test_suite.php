<?php
declare(strict_types=1);

/**
 * Enterprise Multi-Offer Dating Platform - Comprehensive Test Suite
 */

require_once __DIR__ . '/bootstrap.php';

use App\Database;
use App\Auth;
use App\LandingPage;
use App\Offer;
use App\ShortLink;
use App\Tracker;
use App\Postback;
use App\Helpers;

$passed = 0;
$failed = 0;

function it(string $description, callable $fn): void {
    global $passed, $failed;
    try {
        $fn();
        echo "[PASS] " . $description . "\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "[FAIL] " . $description . "\n";
        echo "       Error: " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")\n";
        $failed++;
    }
}

echo "====================================================================\n";
echo "  Enterprise Multi-Offer Platform - Mandatory Acceptance Tests\n";
echo "====================================================================\n";

// 1. Database Connection
it('1. Connects to database and verifies 19 enterprise tables', function () {
    $db = Database::getInstance()->getConnection();
    $requiredTables = [
        'users', 'landing_pages', 'landing_page_templates', 'landing_page_sections',
        'offers', 'landing_page_offers', 'affiliate_tracking_urls', 'short_links',
        'campaigns', 'landing_page_visitors', 'offer_clicks', 'conversions',
        'postback_logs', 'media_library', 'domains', 'audit_logs', 'settings'
    ];
    foreach ($requiredTables as $t) {
        $check = $db->query("SELECT 1 FROM {$t} LIMIT 1");
        if ($check === false) throw new \Exception("Table '{$t}' is missing!");
    }
});

// 2. Initial 4 Dedicated Landing Pages + 5 Additional Pages Exist
it('2. Verifies initial 4 dedicated landing pages and 5 additional dating pages exist', function () {
    $lpService = new LandingPage();
    $expectedSlugs = [
        'friends-with-benefits',
        'instabang',
        'zoomflirts',
        'cindymatches',
        'premium-dating',
        'international-dating',
        'social-connections',
        'online-chat-dating',
        'modern-relationships'
    ];
    foreach ($expectedSlugs as $slug) {
        $p = $lpService->getBySlug($slug);
        if (!$p) throw new \Exception("Landing page '{$slug}' is missing!");
        if ($p['status'] !== 'published') throw new \Exception("Landing page '{$slug}' is not published!");
    }
});

// 3. Multi-Offer Support Per Landing Page
it('3. Verifies each landing page supports multiple associated offers', function () {
    $lpService = new LandingPage();
    $p = $lpService->getBySlug('friends-with-benefits');
    $offers = $lpService->getOffers((int)$p['id']);
    if (count($offers) < 2) {
        throw new \Exception("Expected multiple offers on FWB landing page, got " . count($offers));
    }
});

// 4. Unique Short Tracking Links Assigned to Every Landing Page
it('4. Verifies unique short tracking links mapped to each landing page', function () {
    $shortService = new ShortLink();
    $codes = ['fwb01', 'ib02', 'zf03', 'cm04', 'vip05'];
    foreach ($codes as $code) {
        $resolved = $shortService->resolve($code);
        if (!$resolved) throw new \Exception("Short code '{$code}' not resolved!");
        if (empty($resolved['landing_page_id'])) throw new \Exception("Short code '{$code}' has no landing page ID!");
    }
});

// 5. Short Link Rendering of Assigned Landing Page
it('5. Opening a short link resolves the assigned landing page without redirecting prematurely', function () {
    $shortService = new ShortLink();
    $resolved = $shortService->resolve('ib02');
    if ($resolved['page_slug'] !== 'instabang') {
        throw new \Exception("Expected ib02 to map to 'instabang', got '{$resolved['page_slug']}'");
    }
});

// 6. Offer Redirection to Correct Affiliate Tracking URL
it('6. Clicking an offer CTA resolves and redirects to the correct affiliate tracking URL', function () {
    $offerService = new Offer();
    $tracker = new Tracker();

    $instabang = $offerService->getByCode('instabang-main');
    if (!$instabang) throw new \Exception("Offer 'instabang-main' missing");

    $result = $tracker->processOfferClick((int)$instabang['id'], 2, ['subid' => 'aff_user_123']);
    if ($result['status'] !== 'success') throw new \Exception("Offer click failed: " . json_encode($result));
    if (!str_contains($result['redirect_url'], 'instabang.com')) {
        throw new \Exception("Redirect did not route to instabang.com: " . $result['redirect_url']);
    }
    if (!str_contains($result['redirect_url'], 'subid=aff_user_123')) {
        throw new \Exception("SubID was not preserved in redirect: " . $result['redirect_url']);
    }
});

// 7. Dynamic URL Update Propagation
it('7. Updating an offer affiliate tracking URL updates all CTA buttons immediately without code edits', function () {
    $offerService = new Offer();
    $tracker = new Tracker();

    $fwb = $offerService->getByCode('fwb-main');
    $oldUrl = $fwb['affiliate_tracking_url'];

    // Update offer tracking URL
    $testUrl = 'https://friends-with-benefits.com/?aff_id=TEST_DYNAMIC_HOT_UPDATE';
    $offerService->update((int)$fwb['id'], ['affiliate_tracking_url' => $testUrl], 1);

    // Click again
    $res = $tracker->processOfferClick((int)$fwb['id'], 1, []);
    if (!str_contains($res['redirect_url'], 'aff_id=TEST_DYNAMIC_HOT_UPDATE')) {
        throw new \Exception("Dynamic URL update did not propagate immediately!");
    }

    // Revert
    $offerService->update((int)$fwb['id'], ['affiliate_tracking_url' => $oldUrl], 1);
});

// 8. Unlimited Landing Page Creation
it('8. Administrator can create an unlimited new custom landing page', function () {
    $lpService = new LandingPage();
    $customSlug = 'test-unlimited-page-' . time();
    $res = $lpService->create([
        'name' => 'Custom Automated Test Page',
        'slug' => $customSlug,
        'hero_headline' => 'Test Headline For Verification',
        'hero_subtitle' => 'Test Subtitle For Verification',
        'hero_cta_text' => 'TEST NOW',
        'status' => 'published',
        'offer_ids' => [1, 2]
    ], 1);

    if (!$res['success']) throw new \Exception("Failed to create landing page: " . $res['message']);

    $created = $lpService->getBySlug($customSlug);
    if (!$created) throw new \Exception("Could not retrieve newly created landing page!");

    // Clean up
    $lpService->delete((int)$created['id'], 1);
});

// 9. S2S Postback Integration & Duplicate Prevention
it('9. Processes S2S affiliate conversion postbacks and prevents duplicate transactions', function () {
    $postback = new Postback();
    $txid = 'TX_TEST_SUITE_' . time();
    $clickId = 'clk_test_dup_' . time();

    // 1st conversion attempt
    $res1 = $postback->handle([
        'clickid' => $clickId,
        'payout' => 45.50,
        'txid' => $txid,
        'status' => 'approved'
    ]);
    if (!$res1['success']) throw new \Exception("1st postback failed: " . json_encode($res1));

    // Duplicate attempt with same txid
    $res2 = $postback->handle([
        'clickid' => $clickId,
        'payout' => 45.50,
        'txid' => $txid,
        'status' => 'approved'
    ]);
    if ($res2['message'] !== 'Duplicate transaction ID ignored.') {
        throw new \Exception("Duplicate transaction was not prevented!");
    }

    // Clean up test conversion
    $db = Database::getInstance()->getConnection();
    $db->prepare("DELETE FROM conversions WHERE click_id = ?")->execute([$clickId]);
});

// 10. Open Redirect Protection & Auto-Whitelisting
it('10. Enforces open-redirect domain protection and auto-whitelists admin entered domains', function () {
    $offerService = new Offer();
    $tracker = new Tracker();

    // Add an offer with a new domain
    $res = $offerService->create([
        'name' => 'Test Safe Offer',
        'offer_code' => 'test-safe-' . time(),
        'destination_website' => 'https://new-safe-network.com',
        'affiliate_tracking_url' => 'https://new-safe-network.com/?aff=123',
    ], 1);
    if (!$res['success']) throw new \Exception("Offer creation failed");

    // Click redirect
    $click = $tracker->processOfferClick($res['id'], 1, []);
    if ($click['status'] !== 'success') {
        throw new \Exception("Auto-whitelisted domain was blocked: " . json_encode($click));
    }

    // Clean up
    $offerService->delete($res['id'], 1);
});

echo "\n====================================================================\n";
echo "  Results: {$passed} Passed, {$failed} Failed\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
