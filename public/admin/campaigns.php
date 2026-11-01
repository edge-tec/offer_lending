<?php
declare(strict_types=1);

/**
 * Dynamic Affiliate Tracking Link Management
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\Campaign;
use App\Helpers;

$auth = new Auth();
$auth->requireLogin();
$user = $auth->user();

$campaignService = new Campaign();

$successMessage = null;
$errorMessage = null;

// Handle Form Submissions (Create, Edit, Delete, Toggle, Set Default)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrfToken = $_POST['_csrf_token'] ?? '';
    if (!$auth->validateCsrfToken($csrfToken)) {
        $errorMessage = 'Security verification failed (CSRF). Please retry.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. Create New Campaign
        if ($action === 'create') {
            $result = $campaignService->create([
                'name' => $_POST['name'] ?? '',
                'campaign_code' => $_POST['campaign_code'] ?? '',
                'target_url' => $_POST['target_url'] ?? '',
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'is_default' => isset($_POST['is_default']) ? 1 : 0,
                'notes' => $_POST['notes'] ?? ''
            ], (int)$user['id']);

            if ($result['success']) {
                $successMessage = 'New campaign and tracking URL created successfully.';
                // Auto-authorize domain in whitelist
                $targetUrl = trim($_POST['target_url'] ?? '');
                $parsedHost = parse_url($targetUrl, PHP_URL_HOST);
                if ($parsedHost) {
                    $settingsService = new \App\Settings();
                    $allowed = (string)$settingsService->get('allowed_redirect_domains', '');
                    $allowedRules = array_filter(array_map('trim', explode("\n", $allowed)));
                    if (!\App\Helpers::isDomainAllowed($targetUrl, $allowedRules)) {
                        $allowedRules[] = $parsedHost;
                        $allowedRules[] = '*.' . $parsedHost;
                        $settingsService->updateMany(['allowed_redirect_domains' => implode("\n", array_unique($allowedRules))], (int)$user['id']);
                    }
                }
            } else {
                $errorMessage = $result['message'];
            }
        }

        // 2. Update Existing Campaign
        elseif ($action === 'update') {
            $campaignId = (int)($_POST['campaign_id'] ?? 0);
            $result = $campaignService->update($campaignId, [
                'name' => $_POST['name'] ?? '',
                'campaign_code' => $_POST['campaign_code'] ?? '',
                'target_url' => $_POST['target_url'] ?? '',
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'is_default' => isset($_POST['is_default']) ? 1 : 0,
                'notes' => $_POST['notes'] ?? ''
            ], (int)$user['id']);

            if ($result['success']) {
                $successMessage = 'Campaign & affiliate tracking URL updated successfully. All landing page CTAs now point to the updated URL.';
                // Auto-authorize domain in whitelist
                $targetUrl = trim($_POST['target_url'] ?? '');
                $parsedHost = parse_url($targetUrl, PHP_URL_HOST);
                if ($parsedHost) {
                    $settingsService = new \App\Settings();
                    $allowed = (string)$settingsService->get('allowed_redirect_domains', '');
                    $allowedRules = array_filter(array_map('trim', explode("\n", $allowed)));
                    if (!\App\Helpers::isDomainAllowed($targetUrl, $allowedRules)) {
                        $allowedRules[] = $parsedHost;
                        $allowedRules[] = '*.' . $parsedHost;
                        $settingsService->updateMany(['allowed_redirect_domains' => implode("\n", array_unique($allowedRules))], (int)$user['id']);
                    }
                }
            } else {
                $errorMessage = $result['message'];
            }
        }

        // 3. Set Default
        elseif ($action === 'set_default') {
            $campaignId = (int)($_POST['campaign_id'] ?? 0);
            if ($campaignService->setDefault($campaignId, (int)$user['id'])) {
                $successMessage = 'Selected campaign is now the primary default tracking link.';
            } else {
                $errorMessage = 'Failed to set default campaign.';
            }
        }

        // 4. Toggle Status
        elseif ($action === 'toggle_status') {
            $campaignId = (int)($_POST['campaign_id'] ?? 0);
            $camp = $campaignService->getById($campaignId);
            if ($camp) {
                $newStatus = $camp['is_active'] ? 0 : 1;
                $campaignService->update($campaignId, ['is_active' => $newStatus], (int)$user['id']);
                $successMessage = 'Campaign status changed to ' . ($newStatus ? 'Active' : 'Inactive') . '.';
            }
        }

        // 5. Delete Campaign
        elseif ($action === 'delete') {
            $campaignId = (int)($_POST['campaign_id'] ?? 0);
            $res = $campaignService->delete($campaignId, (int)$user['id']);
            if ($res['success']) {
                $successMessage = 'Campaign deleted successfully.';
            } else {
                $errorMessage = $res['message'];
            }
        }
    }
}

// Fetch all campaigns
$campaigns = $campaignService->getAll();

// If editing via query param
$editCampaign = null;
if (isset($_GET['edit'])) {
    $editCampaign = $campaignService->getById((int)$_GET['edit']);
}

$csrfToken = $auth->getCsrfToken();
$currentPage = 'campaigns';
$pageTitle = 'Dynamic Affiliate Tracking Links';
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

<!-- Top Actions Bar -->
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <p style="color: var(--text-muted); font-size: 0.92rem;">
            Affiliate URLs are stored securely in the database and loaded dynamically. When you update or replace a link here, <strong>every CTA button on the landing page updates automatically</strong> with zero redeployment.
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <button type="button" class="btn btn-secondary" onclick="openAdminModal('testUrlModal')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polygon points="10 8 16 12 10 16 10 8"></polygon></svg>
            <span>Test URL Reachability</span>
        </button>
        <button type="button" class="btn btn-primary" onclick="openAdminModal('createCampaignModal')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>Add New Tracking Link</span>
        </button>
    </div>
</div>

<!-- Edit Campaign Card (If edit requested) -->
<?php if ($editCampaign): ?>
<div class="card" style="border: 2px solid #f43f5e; margin-bottom: 2.5rem;">
    <div class="card-header">
        <h2 class="card-title">Edit Tracking Link: <?= Helpers::e($editCampaign['name']) ?></h2>
        <a href="/admin/campaigns.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
    </div>

    <form method="POST" action="/admin/campaigns.php">
        <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="campaign_id" value="<?= $editCampaign['id'] ?>">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
            <div class="form-group">
                <label class="form-label">Campaign Name</label>
                <input type="text" name="name" class="form-control" value="<?= Helpers::e($editCampaign['name']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Campaign Identifier Code</label>
                <input type="text" name="campaign_code" class="form-control" value="<?= Helpers::e($editCampaign['campaign_code']) ?>" required>
                <div class="form-text">Used in links: <code>/go?c=<?= Helpers::e($editCampaign['campaign_code']) ?></code></div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Affiliate Tracking Destination URL</label>
            <input type="url" name="target_url" class="form-control" value="<?= Helpers::e($editCampaign['target_url']) ?>" required style="font-family: monospace;">
            <div class="form-text">Destination for visitors clicking CTA buttons. Can include query params (e.g. <code>https://friends-with-benefits.com/?aff_id=67890</code>).</div>
        </div>

        <div class="form-group">
            <label class="form-label">Internal Notes</label>
            <textarea name="notes" class="form-control" rows="2"><?= Helpers::e($editCampaign['notes'] ?? '') ?></textarea>
        </div>

        <div style="display: flex; gap: 2rem; margin-bottom: 1.5rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" <?= $editCampaign['is_active'] ? 'checked' : '' ?>>
                <span>Campaign Active</span>
            </label>
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_default" value="1" <?= $editCampaign['is_default'] ? 'checked' : '' ?>>
                <span>Set as Primary Default Campaign</span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary">
            Save & Propagate Changes Immediately
        </button>
    </form>
</div>
<?php endif; ?>

<!-- Campaigns Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">All Configured Tracking Campaigns (<?= count($campaigns) ?>)</h2>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Campaign Name & Code</th>
                    <th>Affiliate Destination URL</th>
                    <th>Traffic (Clicks / Unique)</th>
                    <th>Updated At</th>
                    <th style="text-align: right;">Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campaigns)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem;">No campaigns found. Click "Add New Tracking Link" to create one.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($campaigns as $camp): ?>
                        <tr>
                            <td>
                                <?php if ($camp['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>

                                <?php if ($camp['is_default']): ?>
                                    <span class="badge badge-default" style="margin-top: 0.25rem; display: block;">Default</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong style="font-size: 0.95rem;"><?= Helpers::e($camp['name']) ?></strong>
                                <div style="color: var(--text-dim); font-size: 0.8rem; margin-top: 0.2rem;">
                                    Code: <code><?= Helpers::e($camp['campaign_code']) ?></code>
                                </div>
                            </td>

                            <td style="max-width: 320px;">
                                <div style="font-family: monospace; font-size: 0.82rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= Helpers::e($camp['target_url']) ?>
                                </div>
                                <div style="margin-top: 0.35rem; display: flex; gap: 0.5rem;">
                                    <a href="<?= Helpers::e($camp['target_url']) ?>" target="_blank" style="font-size: 0.75rem; color: #fb7185; text-decoration: underline;">
                                        Open Destination ↗
                                    </a>
                                    &bull;
                                    <a href="javascript:void(0)" onclick="quickTestUrl('<?= Helpers::e($camp['target_url']) ?>')" style="font-size: 0.75rem; color: var(--text-muted); text-decoration: underline;">
                                        Test Reachability
                                    </a>
                                </div>
                            </td>

                            <td>
                                <span style="font-weight: 700; color: #fff;"><?= number_format((int)$camp['total_clicks']) ?></span> Clicks
                                <div style="color: var(--text-dim); font-size: 0.78rem;">
                                    <?= number_format((int)$camp['unique_clicks']) ?> Uniques
                                </div>
                            </td>

                            <td style="font-size: 0.82rem; color: var(--text-dim);">
                                <?= date('M j, Y H:i', strtotime($camp['updated_at'])) ?>
                            </td>

                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.4rem;">
                                    <!-- Edit Link -->
                                    <a href="/admin/campaigns.php?edit=<?= $camp['id'] ?>" class="btn btn-secondary btn-sm" title="Edit Tracking URL">
                                        Edit
                                    </a>

                                    <!-- Set Default -->
                                    <?php if (!$camp['is_default']): ?>
                                    <form method="POST" action="/admin/campaigns.php" style="display: inline;">
                                        <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="set_default">
                                        <input type="hidden" name="campaign_id" value="<?= $camp['id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Make this the default offer">
                                            Make Default
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <!-- Toggle Active -->
                                    <form method="POST" action="/admin/campaigns.php" style="display: inline;">
                                        <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="campaign_id" value="<?= $camp['id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            <?= $camp['is_active'] ? 'Disable' : 'Enable' ?>
                                        </button>
                                    </form>

                                    <!-- Delete -->
                                    <?php if (!$camp['is_default']): ?>
                                    <form method="POST" action="/admin/campaigns.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this campaign?');">
                                        <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="campaign_id" value="<?= $camp['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete campaign">
                                            ✕
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create New Campaign -->
<div id="createCampaignModal" class="modal-admin">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="card-title">Add New Affiliate Tracking Campaign</h3>
            <button type="button" class="modal-close" onclick="closeAdminModal('createCampaignModal')">&times;</button>
        </div>

        <form method="POST" action="/admin/campaigns.php">
            <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label class="form-label">Campaign Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Google Ads - High Intent" required>
            </div>

            <div class="form-group">
                <label class="form-label">Campaign Identifier Code (URL Slug)</label>
                <input type="text" name="campaign_code" class="form-control" placeholder="e.g. gads-search">
                <div class="form-text">Leave blank to auto-generate from campaign name.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Affiliate Tracking Destination URL</label>
                <input type="url" name="target_url" class="form-control" placeholder="https://friends-with-benefits.com/?aff_id=YOUR_ID" required>
            </div>

            <div class="form-group">
                <label class="form-label">Notes (Optional)</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Campaign strategy, affiliate network details, etc."></textarea>
            </div>

            <div style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" checked>
                    <span>Active Immediately</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_default" value="1">
                    <span>Set as Primary Default</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Create Campaign Link
            </button>
        </form>
    </div>
</div>

<!-- Modal: Test URL Reachability -->
<div id="testUrlModal" class="modal-admin">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="card-title">Test Affiliate URL Reachability & Safety</h3>
            <button type="button" class="modal-close" onclick="closeAdminModal('testUrlModal')">&times;</button>
        </div>

        <div class="form-group">
            <label class="form-label">URL to Test</label>
            <input type="url" id="testUrlInput" class="form-control" placeholder="https://friends-with-benefits.com/?aff_id=12345">
            <div class="form-text">Verifies domain whitelist safety and tests server response code.</div>
        </div>

        <button type="button" id="btnRunTest" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;" onclick="executeTestUrl()">
            Run Diagnostics
        </button>

        <div id="testUrlResult" style="display: none; padding: 1rem; border-radius: var(--radius-sm); font-size: 0.88rem; line-height: 1.6;"></div>
    </div>
</div>

<script>
function quickTestUrl(url) {
    document.getElementById('testUrlInput').value = url;
    openAdminModal('testUrlModal');
    executeTestUrl();
}

function executeTestUrl() {
    const url = document.getElementById('testUrlInput').value.trim();
    const resultBox = document.getElementById('testUrlResult');
    if (!url) {
        alert('Please enter a URL to test.');
        return;
    }

    resultBox.style.display = 'block';
    resultBox.style.background = 'rgba(255, 255, 255, 0.05)';
    resultBox.style.border = '1px solid var(--admin-card-border)';
    resultBox.innerHTML = '<span style="color: var(--text-muted);">Testing connection to destination server...</span>';

    fetch('/admin/api.php?action=test_url', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: url, _csrf_token: '<?= Helpers::e($csrfToken) ?>' })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.valid) {
            resultBox.style.background = 'rgba(239, 68, 68, 0.15)';
            resultBox.style.border = '1px solid #ef4444';
            resultBox.innerHTML = '<strong style="color: #f87171;">✕ Invalid URL:</strong> ' + (data.message || 'Syntax error.');
            return;
        }

        let html = '<div><strong>Target Host:</strong> ' + data.host + '</div>';
        
        if (data.is_whitelisted) {
            html += '<div style="color: #34d399; margin-top: 0.25rem;">✓ Domain Whitelist: Passed (Safe for Open-Redirect Protection)</div>';
        } else {
            html += '<div style="color: #fbbf24; margin-top: 0.25rem;">⚠ Domain Whitelist: Host is not yet in whitelist. Add to Settings to allow redirect.</div>';
        }

        html += '<div style="margin-top: 0.25rem;"><strong>HTTP Status Code:</strong> ' + data.http_status + '</div>';
        if (data.is_reachable) {
            html += '<div style="color: #34d399; margin-top: 0.25rem;">✓ Destination is reachable and ready to receive visitors!</div>';
        } else {
            html += '<div style="color: #f87171; margin-top: 0.25rem;">⚠ Server returned error: ' + (data.error || 'Connection timed out or refused') + '</div>';
        }

        resultBox.style.background = 'rgba(16, 185, 129, 0.1)';
        resultBox.style.border = '1px solid rgba(16, 185, 129, 0.3)';
        resultBox.innerHTML = html;
    })
    .catch(err => {
        resultBox.style.background = 'rgba(239, 68, 68, 0.15)';
        resultBox.innerHTML = '<span style="color: #f87171;">Network error executing test.</span>';
    });
}
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
