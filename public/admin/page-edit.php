<?php
declare(strict_types=1);

/**
 * Enterprise Visual Landing Page Builder & Editor
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\LandingPage;
use App\Offer;
use App\Helpers;
use App\Database;

$auth = new Auth();
$auth->requireLogin();
$user = $auth->user();

$lpService = new LandingPage();
$offerService = new Offer();
$db = Database::getInstance()->getConnection();

$pageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$landingPage = $pageId > 0 ? $lpService->getById($pageId) : null;
$isEditing = $landingPage !== null;

$successMessage = null;
$errorMessage = null;

// Handle Form Submission (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['_csrf_token'] ?? '';
    if (!$auth->validateCsrfToken($csrfToken)) {
        $errorMessage = 'Security verification failed (CSRF).';
    } else {
        $data = [
            'name' => $_POST['name'] ?? '',
            'slug' => $_POST['slug'] ?? '',
            'target_dating_website' => $_POST['target_dating_website'] ?? '',
            'page_title' => $_POST['page_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? '',
            'hero_headline' => $_POST['hero_headline'] ?? '',
            'hero_subtitle' => $_POST['hero_subtitle'] ?? '',
            'hero_cta_text' => $_POST['hero_cta_text'] ?? 'EXPLORE NOW',
            'hero_image' => $_POST['hero_image'] ?? '/assets/images/hero-couple.jpg',
            'theme_color_primary' => $_POST['theme_color_primary'] ?? '#f43f5e',
            'theme_color_accent' => $_POST['theme_color_accent'] ?? '#8b5cf6',
            'status' => $_POST['status'] ?? 'published',
            'custom_css' => $_POST['custom_css'] ?? '',
            'short_code' => $_POST['short_code'] ?? '',
            'offer_ids' => $_POST['offer_ids'] ?? []
        ];

        if ($isEditing) {
            $res = $lpService->update($pageId, $data, (int)$user['id']);
            if ($res['success']) {
                $successMessage = 'Landing page updated successfully. Live page reflects all changes immediately.';
                $landingPage = $lpService->getById($pageId);
            } else {
                $errorMessage = $res['message'];
            }
        } else {
            $res = $lpService->create($data, (int)$user['id']);
            if ($res['success']) {
                header('Location: /admin/page-edit.php?id=' . $res['id'] . '&created=1');
                exit;
            } else {
                $errorMessage = $res['message'];
            }
        }
    }
}

if (isset($_GET['created'])) {
    $successMessage = 'New landing page successfully created and published!';
}

// Fetch all available offers
$allOffers = $offerService->getAll();
$currentOfferIds = $isEditing ? array_column($lpService->getOffers($pageId), 'id') : [];

// Fetch short link
$currentShortCode = '';
if ($isEditing) {
    $stmt = $db->prepare("SELECT short_code FROM short_links WHERE landing_page_id = ? LIMIT 1");
    $stmt->execute([$pageId]);
    $currentShortCode = (string)$stmt->fetchColumn();
}

$csrfToken = $auth->getCsrfToken();
$currentPage = 'page-builder';
$pageTitle = $isEditing ? 'Edit: ' . $landingPage['name'] : 'Create New Landing Page';
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

<form method="POST" action="/admin/page-edit.php<?= $isEditing ? '?id=' . $pageId : '' ?>">
    <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">

    <!-- Top Action Bar -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <a href="/admin/pages.php" class="btn btn-secondary btn-sm" style="margin-bottom: 0.5rem;">&larr; Back to All Pages</a>
            <h2 style="font-size: 1.4rem; font-weight: 800;"><?= $isEditing ? 'Visual Builder: ' . Helpers::e($landingPage['name']) : 'New Custom Landing Page' ?></h2>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <?php if ($isEditing): ?>
                <a href="/<?= Helpers::e($landingPage['slug']) ?>?preview=1" target="_blank" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <span>Live Preview</span>
                </a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                <span>Save & Publish Changes</span>
            </button>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Left Column: Content, Headlines & Offers -->
        <div>
            <!-- Section 1: Page Details & Slug -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">1. General Page Identification</h3>
                </div>

                <div class="form-group">
                    <label class="form-label">Internal Landing Page Name</label>
                    <input type="text" name="name" class="form-control" value="<?= Helpers::e($landingPage['name'] ?? '') ?>" placeholder="e.g. Friends With Benefits - Winter Campaign" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">URL Slug</label>
                        <input type="text" name="slug" class="form-control" value="<?= Helpers::e($landingPage['slug'] ?? '') ?>" placeholder="e.g. friends-with-benefits" required>
                        <div class="form-text">Direct URL: <code>/your-slug</code></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Unique Short Tracking Code</label>
                        <input type="text" name="short_code" class="form-control" value="<?= Helpers::e($currentShortCode) ?>" placeholder="e.g. fwb01">
                        <div class="form-text">Short Link: <code>/go/your-code</code></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Promoted Dating Website</label>
                    <input type="url" name="target_dating_website" class="form-control" value="<?= Helpers::e($landingPage['target_dating_website'] ?? 'https://friends-with-benefits.com') ?>" placeholder="https://friends-with-benefits.com">
                </div>
            </div>

            <!-- Section 2: Hero Section Customizer -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">2. Hero Banner & Content Customization</h3>
                </div>

                <div class="form-group">
                    <label class="form-label">Hero Main Headline</label>
                    <input type="text" name="hero_headline" class="form-control" value="<?= Helpers::e($landingPage['hero_headline'] ?? 'Discover Exciting New Connections') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Hero Subtitle</label>
                    <textarea name="hero_subtitle" class="form-control" rows="3"><?= Helpers::e($landingPage['hero_subtitle'] ?? 'Meet open-minded singles looking for honest intentions, mutual chemistry, and uncomplicated dating.') ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Primary CTA Button Text</label>
                        <input type="text" name="hero_cta_text" class="form-control" value="<?= Helpers::e($landingPage['hero_cta_text'] ?? 'EXPLORE NOW') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Hero Featured Couple Image URL</label>
                        <input type="text" name="hero_image" class="form-control" value="<?= Helpers::e($landingPage['hero_image'] ?? '/assets/images/hero-couple.jpg') ?>">
                        <div class="form-text">Use <code>/assets/images/hero-couple.jpg</code> or choose from Media Library.</div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Multiple Associated Offers -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">3. Multi-Offer Selection & Cards (Multiple Offers per Page)</h3>
                    <a href="/admin/offers.php" target="_blank" class="btn btn-secondary btn-sm">+ Add New Offer</a>
                </div>

                <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.25rem;">
                    Select which dating offers will be showcased on this landing page. The first selected offer serves as the primary hero CTA:
                </p>

                <div style="display: grid; gap: 0.75rem;">
                    <?php foreach ($allOffers as $off): ?>
                        <?php $isChecked = in_array((int)$off['id'], $currentOfferIds, true); ?>
                        <label style="display: flex; align-items: center; justify-content: space-between; padding: 0.85rem 1.25rem; background: rgba(255,255,255,0.03); border: 1px solid var(--admin-card-border); border-radius: var(--radius-sm); cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 0.85rem;">
                                <input type="checkbox" name="offer_ids[]" value="<?= $off['id'] ?>" <?= $isChecked ? 'checked' : '' ?> style="transform: scale(1.2);">
                                <div>
                                    <strong style="color: #fff;"><?= Helpers::e($off['name']) ?></strong>
                                    <div style="font-size: 0.78rem; color: var(--text-dim);"><?= Helpers::e($off['destination_website']) ?></div>
                                </div>
                            </div>
                            <div>
                                <span class="badge badge-success"><?= Helpers::e($off['status']) ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Section 4: SEO Metadata -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">4. SEO & Social Meta Settings</h3>
                </div>

                <div class="form-group">
                    <label class="form-label">Page Title (&lt;title&gt;)</label>
                    <input type="text" name="page_title" class="form-control" value="<?= Helpers::e($landingPage['page_title'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Meta Description</label>
                    <textarea name="meta_description" class="form-control" rows="2"><?= Helpers::e($landingPage['meta_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Right Column: Theme & Status -->
        <div>
            <!-- Status Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Publishing Status</h3>
                </div>

                <div class="form-group">
                    <label class="form-label">Page Status</label>
                    <select name="status" class="form-control">
                        <option value="published" <?= ($landingPage['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                        <option value="draft" <?= ($landingPage['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft (Admin only)</option>
                        <option value="paused" <?= ($landingPage['status'] ?? '') === 'paused' ? 'selected' : '' ?>>Paused</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Save Landing Page
                </button>
            </div>

            <!-- Visual Theme Styling -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Theme & Color Palette</h3>
                </div>

                <div class="form-group">
                    <label class="form-label">Primary Color (Buttons & Highlights)</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="color" name="theme_color_primary" value="<?= Helpers::e($landingPage['theme_color_primary'] ?? '#f43f5e') ?>" style="width: 48px; height: 38px; border: none; cursor: pointer; border-radius: 6px;">
                        <input type="text" class="form-control" value="<?= Helpers::e($landingPage['theme_color_primary'] ?? '#f43f5e') ?>" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Accent Color (Glows & Secondary)</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="color" name="theme_color_accent" value="<?= Helpers::e($landingPage['theme_color_accent'] ?? '#8b5cf6') ?>" style="width: 48px; height: 38px; border: none; cursor: pointer; border-radius: 6px;">
                        <input type="text" class="form-control" value="<?= Helpers::e($landingPage['theme_color_accent'] ?? '#8b5cf6') ?>" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Custom CSS Overrides</label>
                    <textarea name="custom_css" class="form-control" rows="4" placeholder="/* Custom CSS */" style="font-family: monospace; font-size: 0.8rem;"><?= Helpers::e($landingPage['custom_css'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Image Asset Quick Pick -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Available Curated Images</h3>
                </div>
                <div style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.8;">
                    <div><code>/assets/images/hero-couple.jpg</code></div>
                    <div><code>/assets/images/discovery-dates.jpg</code></div>
                    <div><code>/assets/images/discovery-chemistry.jpg</code></div>
                    <a href="/admin/media.php" style="color: #fb7185; text-decoration: underline; margin-top: 0.5rem; display: inline-block;">Open Media Library &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

