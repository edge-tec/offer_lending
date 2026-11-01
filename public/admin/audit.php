<?php
declare(strict_types=1);

/**
 * System & URL Modification Audit Logs
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\AuditLog;
use App\Helpers;

$auth = new Auth();
$auth->requireLogin();

$auditLog = new AuditLog();
$logs = $auditLog->getRecent(100);

$currentPage = 'audit';
$pageTitle = 'Security & URL Audit Trail';
require_once __DIR__ . '/layout/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <p style="color: var(--text-muted); font-size: 0.92rem;">
        Complete security log recording all administrator modifications to affiliate destination URLs, campaign statuses, and system settings.
    </p>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent Modification Logs</h2>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Modification Details</th>
                    <th>Admin IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-dim);">No audit logs recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td style="white-space: nowrap; font-size: 0.82rem;">
                                <?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?>
                            </td>

                            <td>
                                <strong><?= Helpers::e($l['username'] ?? 'System') ?></strong>
                            </td>

                            <td>
                                <span class="badge badge-default"><?= Helpers::e($l['action']) ?></span>
                            </td>

                            <td>
                                <?= Helpers::e($l['entity_type']) ?> #<?= (int)$l['entity_id'] ?>
                            </td>

                            <td style="max-width: 380px;">
                                <?php if (!empty($l['new_value'])): ?>
                                    <pre style="background: rgba(0,0,0,0.3); padding: 0.4rem; border-radius: 4px; font-size: 0.75rem; white-space: pre-wrap; word-break: break-all;"><?= Helpers::e($l['new_value']) ?></pre>
                                <?php else: ?>
                                    <span style="color: var(--text-dim);">-</span>
                                <?php endif; ?>
                            </td>

                            <td style="font-family: monospace; font-size: 0.82rem; color: var(--text-dim);">
                                <?= Helpers::e($l['ip_address']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

