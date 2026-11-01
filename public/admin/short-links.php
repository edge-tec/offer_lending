<?php
declare(strict_types=1);

/**
 * Enterprise Short Tracking Link Generator & Manager
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\ShortLink;
use App\LandingPage;
use App\Helpers;

$auth = new Auth();
$auth->requireLogin();
$user = $auth->user();

$shortService = new ShortLink();
$lpService = new LandingPage();

$successMessage = null;
$errorMessage = null;

// Handle Form Submission (Create or Edit Short Link)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['_csrf_token'] ?? '';
    if (!$auth->validateCsrfToken($csrfToken)) {
        $errorMessage = 'CSRF validation failed.';
    } else {
        $pageId = (int)($_POST['landing_page_id'] ?? 0);
        $code = trim((string)($_POST['short_code'] ?? ''));

        if ($pageId <= 0 || $code === '') {
            $errorMessage = 'Landing page and short code are required.';
        } else {
            $res = $shortService->save($pageId, $code);
            if ($res['success']) {
                $successMessage = "Short link '/go/{$code}' successfully saved and mapped!";
            } else {
                $errorMessage = $res['message'];
            }
        }
    }
}

$shortLinks = $shortService->getAll();
$pages = $lpService->getAll();
$csrfToken = $auth->getCsrfToken();

$currentPage = 'short-links';
$pageTitle = 'Short Tracking Link Generator';
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
    <div>
        <p style="color: var(--text-muted); font-size: 0.92rem;">
            Every landing page automatically has its own unique short tracking link. Visitors opening <code>/go/{code}</code> are seamlessly routed to their assigned landing page and tracked.
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <button type="button" class="btn btn-primary" onclick="openAdminModal('newShortLinkModal')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>+ Create Short Tracking Link</span>
        </button>
    </div>
</div>

<!-- Short Links Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Configured Short Links (<?= count($shortLinks) ?>)</h2>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Short Code & URL</th>
                    <th>Assigned Landing Page</th>
                    <th>Target Website</th>
                    <th>Clicks Count</th>
                    <th>Unique Visitors</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shortLinks as $sl): ?>
                    <tr>
                        <td>
                            <strong style="color: #fb7185; font-size: 0.95rem;">/go/<?= Helpers::e($sl['short_code']) ?></strong>
                            <div style="margin-top: 0.25rem;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText(window.location.origin + '/go/<?= Helpers::e($sl['short_code']) ?>'); alert('Copied: ' + window.location.origin + '/go/<?= Helpers::e($sl['short_code']) ?>');">
                                    📋 Copy Full Link
                                </button>
                            </div>
                        </td>

                        <td>
                            <strong><?= Helpers::e($sl['page_name']) ?></strong>
                            <div style="font-size: 0.78rem; color: var(--text-dim);">
                                Slug: <code>/<?= Helpers::e($sl['page_slug']) ?></code>
                            </div>
                        </td>

                        <td style="font-size: 0.82rem; color: var(--text-muted);">
                            <?= Helpers::e($sl['target_dating_website'] ?: 'Multi-Offer Hub') ?>
                        </td>

                        <td style="font-weight: 700; color: #fff;">
                            <?= number_format((int)$sl['clicks_count']) ?>
                        </td>

                        <td>
                            <?= number_format((int)$sl['unique_visitors']) ?>
                        </td>

                        <td>
                            <span class="badge badge-success">Active</span>
                        </td>

                        <td style="text-align: right;">
                            <a href="/go/<?= Helpers::e($sl['short_code']) ?>" target="_blank" class="btn btn-secondary btn-sm">
                                Test Flow ↗
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: New Short Link -->
<div id="newShortLinkModal" class="modal-admin">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="card-title">Create / Remap Short Link</h3>
            <button type="button" class="modal-close" onclick="closeAdminModal('newShortLinkModal')">&times;</button>
        </div>

        <form method="POST" action="/admin/short-links.php">
            <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label">Select Landing Page</label>
                <select name="landing_page_id" class="form-control" required>
                    <?php foreach ($pages as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= Helpers::e($p['name']) ?> (/<?= Helpers::e($p['slug']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label class="form-label">Custom Short Code Slug</label>
                <input type="text" name="short_code" class="form-control" placeholder="e.g. fwb-special" required>
                <div class="form-text">Will be accessible via <code>/go/your-slug</code>.</div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Save Short Tracking Link
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

