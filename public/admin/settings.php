<?php
declare(strict_types=1);

/**
 * Settings, Tracking Pixels & Admin Security
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\Settings;
use App\Helpers;

$auth = new Auth();
$auth->requireLogin();
$user = $auth->user();

$settingsService = new Settings();

$successMessage = null;
$errorMessage = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrfToken = $_POST['_csrf_token'] ?? '';
    if (!$auth->validateCsrfToken($csrfToken)) {
        $errorMessage = 'CSRF validation failed. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. Update Platform & Pixel Settings
        if ($action === 'update_settings') {
            $updated = [
                'site_title' => trim((string)($_POST['site_title'] ?? '')),
                'allowed_redirect_domains' => trim((string)($_POST['allowed_redirect_domains'] ?? '')),
                'enable_open_redirect_protection' => isset($_POST['enable_open_redirect_protection']) ? '1' : '0',
                'require_age_verification' => isset($_POST['require_age_verification']) ? '1' : '0',
                'affiliate_disclosure_short' => trim((string)($_POST['affiliate_disclosure_short'] ?? '')),
                'google_analytics_id' => trim((string)($_POST['google_analytics_id'] ?? '')),
                'meta_pixel_id' => trim((string)($_POST['meta_pixel_id'] ?? '')),
                'custom_header_scripts' => (string)($_POST['custom_header_scripts'] ?? ''),
                'custom_footer_scripts' => (string)($_POST['custom_footer_scripts'] ?? ''),
            ];

            $settingsService->updateMany($updated, (int)$user['id']);
            $successMessage = 'Settings and marketing pixels updated successfully.';
        }

        // 2. Change Admin Password
        elseif ($action === 'change_password') {
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');

            if ($newPassword !== $confirmPassword) {
                $errorMessage = 'New password and confirmation do not match.';
            } else {
                $res = $auth->changePassword((int)$user['id'], $currentPassword, $newPassword);
                if ($res['success']) {
                    $successMessage = 'Admin password successfully updated.';
                } else {
                    $errorMessage = $res['message'];
                }
            }
        }
    }
}

$settings = $settingsService->getAll();
$csrfToken = $auth->getCsrfToken();
$currentPage = 'settings';
$pageTitle = 'Settings, Pixels & Security';
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <!-- Main Configuration Form -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Open Redirect & Security Whitelist</h2>
            </div>

            <form method="POST" action="/admin/settings.php">
                <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                <input type="hidden" name="action" value="update_settings">

                <div class="form-group">
                    <label class="form-label">Authorized Destination Domains (One per line)</label>
                    <textarea name="allowed_redirect_domains" class="form-control" rows="4" style="font-family: monospace;"><?= Helpers::e($settings['allowed_redirect_domains'] ?? '') ?></textarea>
                    <div class="form-text">
                        Controls which domains CTA redirects (/go) can forward visitors to. Wildcards supported (e.g. <code>*.friends-with-benefits.com</code>). Prevents open redirect exploits.
                    </div>
                </div>

                <div style="margin-bottom: 2rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="enable_open_redirect_protection" value="1" <?= ($settings['enable_open_redirect_protection'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <span style="font-weight: 600;">Enforce Strict Open-Redirect Protection</span>
                    </label>
                </div>

                <div class="card-header" style="padding-top: 1.5rem; margin-top: 1.5rem; border-top: 1px solid var(--admin-card-border);">
                    <h2 class="card-title">Marketing Pixels & Analytics Tracking</h2>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Google Analytics 4 Measurement ID</label>
                        <input type="text" name="google_analytics_id" class="form-control" placeholder="G-XXXXXXXXXX" value="<?= Helpers::e($settings['google_analytics_id'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Meta (Facebook) Pixel ID</label>
                        <input type="text" name="meta_pixel_id" class="form-control" placeholder="123456789012345" value="<?= Helpers::e($settings['meta_pixel_id'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Custom Header Scripts (HTML / JS)</label>
                    <textarea name="custom_header_scripts" class="form-control" rows="3" style="font-family: monospace;"><?= Helpers::e($settings['custom_header_scripts'] ?? '') ?></textarea>
                    <div class="form-text">Injected into &lt;head&gt; of the public landing page (TikTok Pixel, Google Tag Manager, etc.).</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Custom Footer Scripts (HTML / JS)</label>
                    <textarea name="custom_footer_scripts" class="form-control" rows="2" style="font-family: monospace;"><?= Helpers::e($settings['custom_footer_scripts'] ?? '') ?></textarea>
                </div>

                <div class="card-header" style="padding-top: 1.5rem; margin-top: 1.5rem; border-top: 1px solid var(--admin-card-border);">
                    <h2 class="card-title">Site Brand & Compliance</h2>
                </div>

                <div class="form-group">
                    <label class="form-label">Landing Page Title</label>
                    <input type="text" name="site_title" class="form-control" value="<?= Helpers::e($settings['site_title'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Short Affiliate Disclosure</label>
                    <textarea name="affiliate_disclosure_short" class="form-control" rows="2"><?= Helpers::e($settings['affiliate_disclosure_short'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                    Save All Settings
                </button>
            </form>
        </div>
    </div>

    <!-- Password Change Side Card -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Change Admin Password</h2>
            </div>

            <form method="POST" action="/admin/settings.php">
                <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                </div>

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
                    <div class="form-text">Minimum 8 characters.</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8" autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-secondary" style="width: 100%;">
                    Update Password
                </button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Server & Platform Info</h2>
            </div>
            <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.8;">
                <div><strong>PHP Version:</strong> <?= PHP_VERSION ?></div>
                <div><strong>Database Driver:</strong> <?= \App\Database::getInstance()->getDriver() ?></div>
                <div><strong>Current Admin:</strong> <?= Helpers::e($user['username']) ?></div>
                <div><strong>Server Time:</strong> <?= date('Y-m-d H:i:s T') ?></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
