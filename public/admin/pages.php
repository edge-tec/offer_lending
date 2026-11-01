<?php
declare(strict_types=1);

/**
 * Manage Landing Pages
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\LandingPage;
use App\Helpers;

$auth = new Auth();
$auth->requireLogin();
$user = $auth->user();

$lpService = new LandingPage();
$successMessage = null;
$errorMessage = null;

// Handle Actions (Duplicate, Delete, Toggle Status)
if (isset($_GET['duplicate'])) {
    $dupId = (int)$_GET['duplicate'];
    $res = $lpService->duplicate($dupId, (int)$user['id']);
    if ($res['success']) {
        $successMessage = "Landing page duplicated as draft: '{$res['slug']}'.";
    } else {
        $errorMessage = $res['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['_csrf_token'] ?? '';
    if ($auth->validateCsrfToken($csrfToken)) {
        $action = $_POST['action'] ?? '';
        $pageId = (int)($_POST['page_id'] ?? 0);

        if ($action === 'delete') {
            $res = $lpService->delete($pageId, (int)$user['id']);
            if ($res['success']) {
                $successMessage = 'Landing page deleted successfully.';
            } else {
                $errorMessage = $res['message'];
            }
        } elseif ($action === 'toggle_status') {
            $existing = $lpService->getById($pageId);
            if ($existing) {
                $newStatus = $existing['status'] === 'published' ? 'paused' : 'published';
                $lpService->update($pageId, ['status' => $newStatus], (int)$user['id']);
                $successMessage = "Page status changed to {$newStatus}.";
            }
        }
    }
}

$statusFilter = $_GET['status'] ?? 'all';
$pages = $lpService->getAll($statusFilter);
$csrfToken = $auth->getCsrfToken();

$currentPage = 'pages';
$pageTitle = 'Manage Landing Pages';
require_once __DIR__ . '/layout/header.php';
?>

<?php if ($successMessage): ?>
    <div class="alert alert-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <span><?= Helpers::e($successMessage) ?></span>
    </div>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <div class="alert alert-danger">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span><?= Helpers::e($errorMessage) ?></span>
    </div>
<?php endif; ?>

<!-- Top Toolbar -->
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div style="display: flex; gap: 0.5rem; background: var(--admin-card); padding: 0.35rem; border-radius: var(--radius-sm); border: 1px solid var(--admin-card-border);">
        <a href="/admin/pages.php?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">All Pages</a>
        <a href="/admin/pages.php?status=published" class="btn btn-sm <?= $statusFilter === 'published' ? 'btn-primary' : 'btn-secondary' ?>">Published</a>
        <a href="/admin/pages.php?status=draft" class="btn btn-sm <?= $statusFilter === 'draft' ? 'btn-primary' : 'btn-secondary' ?>">Draft</a>
        <a href="/admin/pages.php?status=paused" class="btn btn-sm <?= $statusFilter === 'paused' ? 'btn-primary' : 'btn-secondary' ?>">Paused</a>
    </div>

    <a href="/admin/page-edit.php" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        <span>+ Create New Landing Page</span>
    </a>
</div>

<!-- Pages Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">All Landing Pages (<?= count($pages) ?>)</h2>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Landing Page Name</th>
                    <th>Direct URL & Short Link</th>
                    <th>Traffic (Visitors / Clicks)</th>
                    <th>Template</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pages)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 2rem;">No landing pages found. Click "+ Create New Landing Page" to start.</td></tr>
                <?php else: ?>
                    <?php foreach ($pages as $p): ?>
                        <tr>
                            <td>
                                <?php if ($p['status'] === 'published'): ?>
                                    <span class="badge badge-success">Published</span>
                                <?php elseif ($p['status'] === 'draft'): ?>
                                    <span class="badge badge-warning">Draft</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Paused</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong style="font-size: 0.95rem;"><?= Helpers::e($p['name']) ?></strong>
                                <div style="font-size: 0.78rem; color: var(--text-dim); margin-top: 0.2rem;">
                                    Target: <?= Helpers::e($p['target_dating_website'] ?: 'Multi-Offer') ?>
                                </div>
                            </td>

                            <td>
                                <div>
                                    <a href="/<?= Helpers::e($p['slug']) ?>" target="_blank" style="color: #fb7185; text-decoration: underline; font-weight: 600;">
                                        /<?= Helpers::e($p['slug']) ?> ↗
                                    </a>
                                </div>
                                <?php if (!empty($p['short_code'])): ?>
                                    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.25rem;">
                                        Short Link: <code>/go/<?= Helpers::e($p['short_code']) ?></code>
                                        <button type="button" onclick="navigator.clipboard.writeText(window.location.origin + '/go/<?= Helpers::e($p['short_code']) ?>'); alert('Short Link copied!');" style="background: none; border: none; cursor: pointer; color: #a855f7; font-size: 0.75rem;" title="Copy to clipboard">
                                            📋 Copy
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span style="font-weight: 700; color: #fff;"><?= number_format((int)$p['total_visitors']) ?></span> Visitors
                                <div style="color: var(--text-dim); font-size: 0.78rem;">
                                    <?= number_format((int)$p['total_clicks']) ?> Clicks
                                </div>
                            </td>

                            <td style="font-size: 0.85rem; color: var(--text-muted);">
                                <?= Helpers::e($p['template_name'] ?: 'Dark Rose Luxury') ?>
                            </td>

                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.4rem;">
                                    <a href="/admin/page-edit.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="Edit Content & Offers">
                                        Edit
                                    </a>

                                    <a href="/admin/pages.php?duplicate=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="Duplicate this landing page">
                                        Duplicate
                                    </a>

                                    <form method="POST" action="/admin/pages.php" style="display: inline;">
                                        <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="page_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            <?= $p['status'] === 'published' ? 'Pause' : 'Publish' ?>
                                        </button>
                                    </form>

                                    <form method="POST" action="/admin/pages.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this landing page?');">
                                        <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="page_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

