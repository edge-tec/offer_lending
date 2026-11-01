<?php
declare(strict_types=1);

/**
 * Multi-Domain Management
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;

$auth = new Auth();
$auth->requireLogin();
$db = Database::getInstance()->getConnection();

$successMessage = null;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['_csrf_token'] ?? '';
    if ($auth->validateCsrfToken($csrfToken)) {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $domain = strtolower(trim((string)($_POST['domain_name'] ?? '')));
            $notes = trim((string)($_POST['notes'] ?? ''));

            if ($domain === '') {
                $errorMessage = 'Domain name cannot be empty.';
            } else {
                try {
                    $stmt = $db->prepare("INSERT INTO domains (domain_name, is_default, is_active, notes) VALUES (?, 0, 1, ?)");
                    $stmt->execute([$domain, $notes]);
                    $successMessage = "Domain '{$domain}' connected successfully.";
                } catch (\Throwable $e) {
                    $errorMessage = 'Domain already exists.';
                }
            }
        } elseif ($action === 'set_default') {
            $id = (int)($_POST['domain_id'] ?? 0);
            $db->exec("UPDATE domains SET is_default = 0");
            $db->prepare("UPDATE domains SET is_default = 1 WHERE id = ?")->execute([$id]);
            $successMessage = 'Primary default domain updated.';
        } elseif ($action === 'delete') {
            $id = (int)($_POST['domain_id'] ?? 0);
            $db->prepare("DELETE FROM domains WHERE id = ? AND is_default = 0")->execute([$id]);
            $successMessage = 'Domain removed.';
        }
    }
}

$domains = $db->query("SELECT * FROM domains ORDER BY is_default DESC, id ASC")->fetchAll();
$csrfToken = $auth->getCsrfToken();

$currentPage = 'domains';
$pageTitle = 'Multi-Domain Management';
require_once __DIR__ . '/layout/header.php';
?>

<?php if ($successMessage): ?>
    <div class="alert alert-success"><span><?= Helpers::e($successMessage) ?></span></div>
<?php endif; ?>
<?php if ($errorMessage): ?>
    <div class="alert alert-danger"><span><?= Helpers::e($errorMessage) ?></span></div>
<?php endif; ?>

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <p style="color: var(--text-muted); font-size: 0.92rem;">
            Control and assign multiple brand domains to individual landing pages from this single centralized dashboard.
        </p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openAdminModal('addDomainModal')">
        + Connect Custom Domain
    </button>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Connected Domains (<?= count($domains) ?>)</h2>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Domain Name</th>
                    <th>Type</th>
                    <th>SSL Security</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th style="text-align: right;">Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($domains as $d): ?>
                    <tr>
                        <td>
                            <strong style="font-size: 0.95rem; color: #fff;"><?= Helpers::e($d['domain_name']) ?></strong>
                        </td>
                        <td>
                            <?php if ($d['is_default']): ?>
                                <span class="badge badge-default">Primary Default</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Secondary Domain</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="color: #34d399; font-size: 0.82rem;">🔒 Active (TLS/SSL)</span>
                        </td>
                        <td>
                            <span class="badge badge-success">Connected</span>
                        </td>
                        <td style="font-size: 0.82rem; color: var(--text-dim);"><?= Helpers::e($d['notes'] ?? '-') ?></td>
                        <td style="text-align: right;">
                            <?php if (!$d['is_default']): ?>
                                <form method="POST" action="/admin/domains.php" style="display: inline;">
                                    <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="set_default">
                                    <input type="hidden" name="domain_id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm">Make Default</button>
                                </form>
                                <form method="POST" action="/admin/domains.php" style="display: inline;" onsubmit="return confirm('Remove domain?');">
                                    <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="domain_id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">✕</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Domain -->
<div id="addDomainModal" class="modal-admin">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="card-title">Connect New Custom Domain</h3>
            <button type="button" class="modal-close" onclick="closeAdminModal('addDomainModal')">&times;</button>
        </div>

        <form method="POST" action="/admin/domains.php">
            <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label class="form-label">Domain Name (FQDN)</label>
                <input type="text" name="domain_name" class="form-control" placeholder="e.g. datingoffers.com" required>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label class="form-label">Notes / Description</label>
                <input type="text" name="notes" class="form-control" placeholder="Primary social ads domain">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Connect Domain
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

