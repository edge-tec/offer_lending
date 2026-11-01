<?php
declare(strict_types=1);

/**
 * Enterprise Offer Management
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\Offer;
use App\Helpers;

$auth = new Auth();
$auth->requireLogin();
$user = $auth->user();

$offerService = new Offer();
$successMessage = null;
$errorMessage = null;

// Handle Offer Actions (Create, Update, Delete, Toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['_csrf_token'] ?? '';
    if (!$auth->validateCsrfToken($csrfToken)) {
        $errorMessage = 'Security verification failed (CSRF).';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $res = $offerService->create([
                'name' => $_POST['name'] ?? '',
                'offer_code' => $_POST['offer_code'] ?? '',
                'destination_website' => $_POST['destination_website'] ?? '',
                'affiliate_tracking_url' => $_POST['affiliate_tracking_url'] ?? '',
                'description' => $_POST['description'] ?? '',
                'image_url' => $_POST['image_url'] ?? '',
                'cta_text' => $_POST['cta_text'] ?? 'Explore Now',
                'priority' => (int)($_POST['priority'] ?? 50),
                'status' => $_POST['status'] ?? 'active'
            ], (int)$user['id']);

            if ($res['success']) {
                $successMessage = 'Offer created successfully and domain automatically whitelisted.';
            } else {
                $errorMessage = $res['message'];
            }
        } elseif ($action === 'update') {
            $offerId = (int)($_POST['offer_id'] ?? 0);
            $res = $offerService->update($offerId, [
                'name' => $_POST['name'] ?? '',
                'offer_code' => $_POST['offer_code'] ?? '',
                'destination_website' => $_POST['destination_website'] ?? '',
                'affiliate_tracking_url' => $_POST['affiliate_tracking_url'] ?? '',
                'description' => $_POST['description'] ?? '',
                'image_url' => $_POST['image_url'] ?? '',
                'cta_text' => $_POST['cta_text'] ?? 'Explore Now',
                'priority' => (int)($_POST['priority'] ?? 50),
                'status' => $_POST['status'] ?? 'active'
            ], (int)$user['id']);

            if ($res['success']) {
                $successMessage = 'Offer updated successfully. All landing pages now use this updated tracking URL immediately.';
            } else {
                $errorMessage = $res['message'];
            }
        } elseif ($action === 'delete') {
            $offerId = (int)($_POST['offer_id'] ?? 0);
            $res = $offerService->delete($offerId, (int)$user['id']);
            if ($res['success']) {
                $successMessage = 'Offer deleted successfully.';
            } else {
                $errorMessage = $res['message'];
            }
        } elseif ($action === 'toggle_status') {
            $offerId = (int)($_POST['offer_id'] ?? 0);
            $offerService->toggleStatus($offerId, (int)$user['id']);
            $successMessage = 'Offer status toggled.';
        }
    }
}

$statusFilter = $_GET['status'] ?? 'all';
$offers = $offerService->getAll($statusFilter);
$editOffer = isset($_GET['edit']) ? $offerService->getById((int)$_GET['edit']) : null;
$csrfToken = $auth->getCsrfToken();

$currentPage = 'offers';
$pageTitle = 'Dating Offer Management';
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
            Centralized offers database. When you update an affiliate tracking URL here, <strong>all associated landing pages and CTA buttons update dynamically</strong>.
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <button type="button" class="btn btn-primary" onclick="openAdminModal('addOfferModal')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>+ Add New Offer</span>
        </button>
    </div>
</div>

<!-- Edit Offer Form (if requested) -->
<?php if ($editOffer): ?>
<div class="card" style="border: 2px solid #f43f5e; margin-bottom: 2.5rem;">
    <div class="card-header">
        <h2 class="card-title">Edit Offer: <?= Helpers::e($editOffer['name']) ?></h2>
        <a href="/admin/offers.php" class="btn btn-secondary btn-sm">Cancel</a>
    </div>

    <form method="POST" action="/admin/offers.php">
        <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="offer_id" value="<?= $editOffer['id'] ?>">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
            <div class="form-group">
                <label class="form-label">Offer Name</label>
                <input type="text" name="name" class="form-control" value="<?= Helpers::e($editOffer['name']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Offer Code</label>
                <input type="text" name="offer_code" class="form-control" value="<?= Helpers::e($editOffer['offer_code']) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Destination Website</label>
            <input type="url" name="destination_website" class="form-control" value="<?= Helpers::e($editOffer['destination_website']) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Affiliate Tracking URL</label>
            <input type="url" name="affiliate_tracking_url" class="form-control" value="<?= Helpers::e($editOffer['affiliate_tracking_url']) ?>" required style="font-family: monospace;">
            <div class="form-text">All clicks on this offer's CTA buttons route here. Destination domain is auto-authorized in whitelist.</div>
        </div>

        <div class="form-group">
            <label class="form-label">Offer Description</label>
            <textarea name="description" class="form-control" rows="2"><?= Helpers::e($editOffer['description'] ?? '') ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.25rem;">
            <div class="form-group">
                <label class="form-label">CTA Button Text</label>
                <input type="text" name="cta_text" class="form-control" value="<?= Helpers::e($editOffer['cta_text'] ?? 'Explore Now') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Priority (Display Weight)</label>
                <input type="number" name="priority" class="form-control" value="<?= (int)$editOffer['priority'] ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?= $editOffer['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="paused" <?= $editOffer['status'] === 'paused' ? 'selected' : '' ?>>Paused</option>
                    <option value="inactive" <?= $editOffer['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            Save Offer & Propagate URL Instantly
        </button>
    </form>
</div>
<?php endif; ?>

<!-- Offers Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">All Configured Offers (<?= count($offers) ?>)</h2>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Offer Name & Code</th>
                    <th>Destination & Affiliate Tracking URL</th>
                    <th>Assigned Pages</th>
                    <th>Clicks / Conv.</th>
                    <th>Revenue</th>
                    <th style="text-align: right;">Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($offers)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 2rem;">No offers found. Click "+ Add New Offer" to create one.</td></tr>
                <?php else: ?>
                    <?php foreach ($offers as $off): ?>
                        <tr>
                            <td>
                                <?php if ($off['status'] === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Paused</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong style="font-size: 0.95rem;"><?= Helpers::e($off['name']) ?></strong>
                                <div style="font-size: 0.78rem; color: var(--text-dim); margin-top: 0.2rem;">
                                    Code: <code><?= Helpers::e($off['offer_code']) ?></code> &bull; Prio: <?= (int)$off['priority'] ?>
                                </div>
                            </td>

                            <td style="max-width: 280px;">
                                <div style="font-weight: 600; color: #cbd5e1; font-size: 0.82rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= Helpers::e($off['destination_website']) ?>
                                </div>
                                <div style="font-family: monospace; font-size: 0.75rem; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 0.2rem;">
                                    <?= Helpers::e($off['affiliate_tracking_url']) ?>
                                </div>
                            </td>

                            <td>
                                <span class="badge badge-default"><?= (int)$off['assigned_pages_count'] ?> Pages</span>
                            </td>

                            <td>
                                <span style="font-weight: 700; color: #fff;"><?= number_format((int)$off['total_offer_clicks']) ?></span> Clicks
                                <div style="color: var(--text-dim); font-size: 0.78rem;">
                                    <?= number_format((int)$off['total_conversions']) ?> Conv.
                                </div>
                            </td>

                            <td style="color: #34d399; font-weight: 700;">
                                $<?= number_format((float)$off['total_revenue'], 2) ?>
                            </td>

                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.4rem;">
                                    <a href="/admin/offers.php?edit=<?= $off['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>

                                    <form method="POST" action="/admin/offers.php" style="display: inline;">
                                        <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="offer_id" value="<?= $off['id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            <?= $off['status'] === 'active' ? 'Pause' : 'Activate' ?>
                                        </button>
                                    </form>

                                    <form method="POST" action="/admin/offers.php" style="display: inline;" onsubmit="return confirm('Delete this offer?');">
                                        <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="offer_id" value="<?= $off['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">✕</button>
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

<!-- Modal: Add New Offer -->
<div id="addOfferModal" class="modal-admin">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="card-title">Add New Dating Offer</h3>
            <button type="button" class="modal-close" onclick="closeAdminModal('addOfferModal')">&times;</button>
        </div>

        <form method="POST" action="/admin/offers.php">
            <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label class="form-label">Offer Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Passion Hookups" required>
            </div>

            <div class="form-group">
                <label class="form-label">Offer Identifier Code</label>
                <input type="text" name="offer_code" class="form-control" placeholder="e.g. passion-hookups">
            </div>

            <div class="form-group">
                <label class="form-label">Destination Website URL</label>
                <input type="url" name="destination_website" class="form-control" placeholder="https://example-dating.com" required>
            </div>

            <div class="form-group">
                <label class="form-label">Affiliate Tracking URL</label>
                <input type="url" name="affiliate_tracking_url" class="form-control" placeholder="https://example-dating.com/?aff_id=YOUR_ID" required>
                <div class="form-text">Destination domain is auto-authorized in whitelist upon save.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Offer Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Brief value proposition shown on cards"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">CTA Button Text</label>
                    <input type="text" name="cta_text" class="form-control" value="Explore Now" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Priority Weight</label>
                    <input type="number" name="priority" class="form-control" value="50">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Save & Whitelist Offer
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

