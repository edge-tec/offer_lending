<?php
declare(strict_types=1);

/**
 * Enterprise S2S Postback & Conversion Manager
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\Postback;
use App\Helpers;

$auth = new Auth();
$auth->requireLogin();

$postbackService = new Postback();
$successMessage = null;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'deduplicate') {
        $count = $postbackService->deduplicateConversions();
        $successMessage = "Successfully removed {$count} duplicate conversion records. Unique clicks preserved.";
    } elseif ($action === 'delete') {
        $id = (int)($_POST['conversion_id'] ?? 0);
        if ($id > 0) {
            $postbackService->deleteConversion($id);
            $successMessage = "Conversion record #{$id} deleted successfully.";
        }
    } elseif ($action === 'clear_test') {
        $count = $postbackService->clearTestConversions();
        $successMessage = "Successfully removed {$count} automated test/mock conversion records.";
    }
}

$conversions = $postbackService->getConversions(50);
$logs = $postbackService->getLogs(50);

$currentPage = 'postback';
$pageTitle = 'S2S Postback & Conversion Manager';
require_once __DIR__ . '/layout/header.php';
?>

<?php if ($successMessage): ?>
    <div class="alert alert-success" style="display: flex; align-items: center; gap: 0.75rem; background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; padding: 1rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <span><?= Helpers::e($successMessage) ?></span>
    </div>
<?php endif; ?>

<div style="margin-bottom: 2rem;">
    <p style="color: var(--text-muted); font-size: 0.92rem;">
        Integrate server-to-server (S2S) postbacks with your affiliate networks to record verified conversions, sales, and commissions.
    </p>
</div>

<!-- Postback URL Integration Helper Card -->
<div class="card" style="border: 1px solid rgba(139, 92, 246, 0.4); background: rgba(139, 92, 246, 0.05);">
    <div class="card-header">
        <h3 class="card-title">Real-Time S2S Postback Integration</h3>
    </div>
    <p style="font-size: 0.88rem; color: #cbd5e1; margin-bottom: 0.75rem;">
        Paste this Postback URL in your Affiliate Network / Smartlink Dashboard (AffsCash, Cake, Tune, HasOffers, Voluum, Everflow, etc.):
    </p>
    <div style="background: rgba(0,0,0,0.5); padding: 1rem; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.88rem; color: #34d399; word-break: break-all; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
        <span id="postbackUrlText"><?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'https://') . ($_SERVER['HTTP_HOST'] ?? 'onlyflrt.net') ?>/postback?click_id={click_id}&payout={payout}&txid={txid}</span>
        <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('postbackUrlText').innerText); alert('Postback URL copied!');">
            Copy URL
        </button>
    </div>
    <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.75rem; line-height: 1.6;">
        <div><strong>Supported Click Parameters:</strong> <code>click_id</code>, <code>clickid</code>, <code>subid</code>, <code>cid</code> (automatically reconciled in real time).</div>
        <div><strong>Macro Support in Offer URLs:</strong> You can place <code>{click_id}</code> or <code>{subid}</code> in your offer URLs (e.g., <code>https://network.com/offer?click_id={click_id}</code>) and it will automatically be populated with the live generated click ID upon redirect.</div>
        <div><strong>Endpoints Active:</strong> <code>/postback</code> &bull; <code>/postback.php</code> &bull; <code>/api/postback.php</code></div>
    </div>
</div>

<!-- Verified Conversions Table -->
<div class="card">
    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
        <h2 class="card-title">Verified Conversions (Latest <?= count($conversions) ?>)</h2>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <form method="POST" style="margin: 0;" onsubmit="return confirm('Clean all duplicate conversions? Only the earliest conversion per click will be kept.');">
                <input type="hidden" name="action" value="deduplicate">
                <button type="submit" class="btn btn-secondary btn-sm">
                    ⚡ Clean Duplicate Pings
                </button>
            </form>
            <form method="POST" style="margin: 0;" onsubmit="return confirm('Remove all automated test conversions?');">
                <input type="hidden" name="action" value="clear_test">
                <button type="submit" class="btn btn-secondary btn-sm" style="color: #f87171;">
                    🧹 Clear Test Data
                </button>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Transaction ID</th>
                    <th>Matched Click ID</th>
                    <th>Offer</th>
                    <th>Landing Page</th>
                    <th>Payout / Revenue</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($conversions)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-dim);">No conversions recorded yet. Incoming affiliate postbacks will appear here.</td></tr>
                <?php else: ?>
                    <?php foreach ($conversions as $c): ?>
                        <tr>
                            <td style="font-size: 0.82rem; white-space: nowrap;"><?= date('Y-m-d H:i:s', strtotime($c['converted_at'])) ?></td>
                            <td><code><?= Helpers::e($c['transaction_id']) ?></code></td>
                            <td><code><?= Helpers::e($c['click_id']) ?></code></td>
                            <td><strong><?= Helpers::e($c['offer_name'] ?? 'Direct Campaign') ?></strong></td>
                            <td><?= Helpers::e($c['page_name'] ?? '-') ?></td>
                            <td style="color: #34d399; font-weight: 700;">$<?= number_format((float)$c['payout'], 2) ?> <?= Helpers::e($c['currency']) ?></td>
                            <td><span class="badge badge-success"><?= Helpers::e($c['status']) ?></span></td>
                            <td>
                                <form method="POST" style="margin:0;" onsubmit="return confirm('Delete conversion #<?= $c['id'] ?>?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="conversion_id" value="<?= (int)$c['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.25rem 0.55rem; font-size: 0.75rem;">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Raw Postback Request Logs -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent Incoming Postback HTTP Logs</h2>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Method</th>
                    <th>IP Address</th>
                    <th>Status</th>
                    <th>Result Message</th>
                    <th>Payload Parameters</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-dim);">No postback logs yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td style="font-size: 0.82rem; white-space: nowrap;"><?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?></td>
                            <td><span class="badge badge-secondary"><?= Helpers::e($l['request_method']) ?></span></td>
                            <td><?= Helpers::e($l['ip_address']) ?></td>
                            <td><span class="badge <?= $l['response_status'] === 200 ? 'badge-success' : 'badge-danger' ?>"><?= $l['response_status'] ?></span></td>
                            <td><?= Helpers::e($l['response_message']) ?></td>
                            <td style="max-width: 300px; font-size: 0.75rem; font-family: monospace; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= Helpers::e($l['query_params']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

