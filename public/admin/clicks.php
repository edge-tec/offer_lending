<?php
declare(strict_types=1);

/**
 * Click Logs & Attribution Explorer
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\Analytics;
use App\Helpers;

$auth = new Auth();
$auth->requireLogin();

$analytics = new Analytics();
$clicks = $analytics->getRecentClicks(100);

$currentPage = 'clicks';
$pageTitle = 'Click Logs & Attribution Explorer';
require_once __DIR__ . '/layout/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <p style="color: var(--text-muted); font-size: 0.92rem;">
        Detailed audit trail of incoming CTA clicks with anonymized visitor hashes, campaign tracking codes, and subid parameters.
    </p>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent CTA Click Events (Latest 100)</h2>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date & Time (UTC)</th>
                    <th>Campaign</th>
                    <th>SubID / ClickID</th>
                    <th>Traffic Source / UTM</th>
                    <th>Device & OS</th>
                    <th>Country</th>
                    <th>IP Hash</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clicks)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-dim);">
                            No clicks recorded yet. Visit the landing page and click "EXPLORE NOW" to generate tracking logs!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clicks as $c): ?>
                        <tr>
                            <td style="white-space: nowrap; font-size: 0.82rem;">
                                <?= date('Y-m-d H:i:s', strtotime($c['created_at'])) ?>
                            </td>

                            <td>
                                <strong><?= Helpers::e($c['campaign_name'] ?? 'Unknown') ?></strong>
                                <div style="font-size: 0.75rem; color: var(--text-dim);">
                                    Code: <code><?= Helpers::e($c['campaign_code'] ?? 'default') ?></code>
                                </div>
                            </td>

                            <td>
                                <?php if (!empty($c['subid']) || !empty($c['clickid'])): ?>
                                    <div style="font-family: monospace; font-size: 0.82rem;">
                                        <?= Helpers::e($c['subid'] ?: '-') ?> / <?= Helpers::e($c['clickid'] ?: '-') ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-dim);">-</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!empty($c['utm_source'])): ?>
                                    <span class="badge badge-secondary"><?= Helpers::e($c['utm_source']) ?></span>
                                    <?php if (!empty($c['utm_campaign'])): ?>
                                        <span style="font-size: 0.78rem; color: var(--text-muted);">(<?= Helpers::e($c['utm_campaign']) ?>)</span>
                                    <?php endif; ?>
                                <?php elseif (!empty($c['referer'])): ?>
                                    <div style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.78rem;" title="<?= Helpers::e($c['referer']) ?>">
                                        <?= Helpers::e($c['referer']) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-dim); font-size: 0.82rem;">Direct</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span style="text-transform: capitalize; font-weight: 600;"><?= Helpers::e($c['device_type']) ?></span>
                                <div style="font-size: 0.75rem; color: var(--text-dim);">
                                    <?= Helpers::e($c['browser']) ?> on <?= Helpers::e($c['os']) ?>
                                </div>
                            </td>

                            <td>
                                <span class="badge badge-secondary"><?= Helpers::e($c['country']) ?></span>
                            </td>

                            <td style="font-family: monospace; font-size: 0.78rem; color: var(--text-dim);" title="Hashed for GDPR privacy">
                                <?= substr($c['ip_hash'], 0, 10) ?>…
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

