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
$conversions = $postbackService->getConversions(50);
$logs = $postbackService->getLogs(50);

$currentPage = 'postback';
$pageTitle = 'S2S Postback & Conversion Manager';
require_once __DIR__ . '/layout/header.php';
?>

<div style="margin-bottom: 2rem;">
    <p style="color: var(--text-muted); font-size: 0.92rem;">
        Integrate server-to-server (S2S) postbacks with your affiliate networks to record verified conversions, sales, and commissions.
    </p>
</div>

<!-- Postback URL Integration Helper Card -->
<div class="card" style="border: 1px solid rgba(139, 92, 246, 0.4); background: rgba(139, 92, 246, 0.05);">
    <div class="card-header">
        <h3 class="card-title">Your S2S Postback Integration URL</h3>
    </div>
    <p style="font-size: 0.88rem; color: #cbd5e1; margin-bottom: 1rem;">
        Provide this Postback URL in your Affiliate Network / Smartlink Dashboard (AffsCash, Cake, Tune, HasOffers, Voluum, etc.):
    </p>
    <div style="background: rgba(0,0,0,0.5); padding: 1rem; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.88rem; color: #34d399; word-break: break-all; display: flex; align-items: center; justify-content: space-between;">
        <span id="postbackUrlText"><?= (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000') ?>/postback?clickid={clickid}&payout={payout}&txid={txid}</span>
        <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('postbackUrlText').innerText); alert('Postback URL copied!');">
            Copy URL
        </button>
    </div>
    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.5rem;">
        Parameters: <code>clickid</code> (Required - matches original click), <code>payout</code> (Commission amount), <code>txid</code> (Unique Transaction ID to prevent duplicate reporting).
    </div>
</div>

<!-- Verified Conversions Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Verified Conversions (Latest <?= count($conversions) ?>)</h2>
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
                </tr>
            </thead>
            <tbody>
                <?php if (empty($conversions)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-dim);">No conversions recorded yet. Incoming affiliate postbacks will appear here.</td></tr>
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

