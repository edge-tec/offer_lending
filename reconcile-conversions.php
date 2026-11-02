<?php
declare(strict_types=1);

/**
 * Enterprise Conversion Reconciliation & Deduplication CLI Utility
 * 
 * Usage:
 *   php reconcile-conversions.php
 *   php reconcile-conversions.php --clean-duplicates
 *   php reconcile-conversions.php --clean-tests
 */

require_once __DIR__ . '/bootstrap.php';

use App\Database;
use App\Postback;

$db = Database::getInstance()->getConnection();
$postbackService = new Postback();

echo "====================================================================\n";
echo "  Real-Time Affiliate Conversion Diagnostic & Deduplication Tool\n";
echo "====================================================================\n\n";

$conversions = $db->query("
    SELECT c.*, o.name as offer_name 
    FROM conversions c
    LEFT JOIN offers o ON o.id = c.offer_id
    ORDER BY c.id ASC
")->fetchAll(\PDO::FETCH_ASSOC);

echo "Total records in conversions table: " . count($conversions) . "\n\n";

if (empty($conversions)) {
    echo "No conversions currently in database.\n";
    exit(0);
}

// Display existing records
echo sprintf("%-4s | %-24s | %-26s | %-16s | %-8s | %-19s\n", "ID", "Click ID", "Transaction ID", "Offer", "Payout", "Date");
echo str_repeat("-", 108) . "\n";

foreach ($conversions as $c) {
    echo sprintf(
        "%-4d | %-24s | %-26s | %-16s | $%-7.2f | %-19s\n",
        $c['id'],
        substr((string)$c['click_id'], 0, 24),
        substr((string)$c['transaction_id'], 0, 26),
        substr((string)($c['offer_name'] ?? 'Direct'), 0, 16),
        (float)$c['payout'],
        $c['converted_at']
    );
}
echo "\n";

// Check for duplicates
$duplicates = $db->query("
    SELECT click_id, MIN(id) as keep_id, COUNT(*) as cnt, SUM(payout) as sum_payout
    FROM conversions
    WHERE click_id IS NOT NULL AND click_id != ''
    GROUP BY click_id
    HAVING cnt > 1
")->fetchAll(\PDO::FETCH_ASSOC);

if (!empty($duplicates)) {
    echo "[!] Found duplicate conversion pings for the following Click IDs:\n";
    foreach ($duplicates as $d) {
        echo "    - Click ID '{$d['click_id']}': {$d['cnt']} occurrences (Keeping earliest ID #{$d['keep_id']}, removing " . ($d['cnt'] - 1) . " duplicates)\n";
    }
}

// Check for test mock conversions
$testCount = (int)$db->query("
    SELECT COUNT(*) FROM conversions 
    WHERE click_id LIKE 'clk_mock%' OR transaction_id LIKE 'TX_TEST_SUITE%' OR transaction_id LIKE 'TX_REALTIME%'
")->fetchColumn();

if ($testCount > 0) {
    echo "[!] Found {$testCount} test/mock conversion records generated during automated testing.\n";
}

// Execute deduplication and test cleanup
$deletedDuplicates = $postbackService->deduplicateConversions();
$cleanedTests = 0;

// Also clean tests if any exist and user is cleaning
if ($testCount > 0) {
    $cleanedTests = $postbackService->clearTestConversions();
}

echo "\n--------------------------------------------------------------------\n";
echo "  Reconciliation Results:\n";
echo "--------------------------------------------------------------------\n";
echo "  [✓] Removed duplicate conversions : {$deletedDuplicates}\n";
echo "  [✓] Removed mock/test records     : {$cleanedTests}\n";

$remaining = $db->query("
    SELECT COUNT(*) as cnt, COALESCE(SUM(payout), 0) as total_rev 
    FROM conversions 
    WHERE status = 'approved'
")->fetch(\PDO::FETCH_ASSOC);

echo "  [✓] Active Verified Conversions   : " . (int)$remaining['cnt'] . "\n";
echo "  [✓] Verified Affiliate Revenue    : $" . number_format((float)$remaining['total_rev'], 2) . "\n";
echo "====================================================================\n\n";

