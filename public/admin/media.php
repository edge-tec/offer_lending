<?php
declare(strict_types=1);

/**
 * Enterprise Media & Image Library
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\Media;
use App\Helpers;

$auth = new Auth();
$auth->requireLogin();

$mediaService = new Media();
$successMessage = null;
$errorMessage = null;

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['_csrf_token'] ?? '';
    if (!$auth->validateCsrfToken($csrfToken)) {
        $errorMessage = 'CSRF validation failed.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'upload' && !empty($_FILES['image'])) {
            $folder = trim($_POST['folder'] ?? 'general');
            $res = $mediaService->upload($_FILES['image'], $folder);
            if ($res['success']) {
                $successMessage = 'Image uploaded successfully!';
            } else {
                $errorMessage = $res['message'];
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['media_id'] ?? 0);
            if ($mediaService->delete($id)) {
                $successMessage = 'Image deleted.';
            }
        }
    }
}

$folderFilter = $_GET['folder'] ?? 'all';
$mediaFiles = $mediaService->getAll($folderFilter);
$csrfToken = $auth->getCsrfToken();

$currentPage = 'media';
$pageTitle = 'Media & Image Library';
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

<!-- Top Toolbar with Upload Box -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Upload New Image Asset</h3>
    </div>
    <form method="POST" action="/admin/media.php" enctype="multipart/form-data" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
        <input type="hidden" name="action" value="upload">

        <div style="flex: 2; min-width: 250px;">
            <label class="form-label">Select Image (JPG, PNG, WEBP, GIF)</label>
            <input type="file" name="image" class="form-control" accept="image/*" required>
        </div>

        <div style="flex: 1; min-width: 150px;">
            <label class="form-label">Category / Folder</label>
            <select name="folder" class="form-control">
                <option value="general">General</option>
                <option value="hero">Hero Banners</option>
                <option value="offers">Offer Cards</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
            Upload Image
        </button>
    </form>
</div>

<!-- Media Grid -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Uploaded Media Files (<?= count($mediaFiles) ?>)</h3>
        <div style="display: flex; gap: 0.5rem;">
            <a href="/admin/media.php?folder=all" class="btn btn-sm <?= $folderFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
            <a href="/admin/media.php?folder=hero" class="btn btn-sm <?= $folderFilter === 'hero' ? 'btn-primary' : 'btn-secondary' ?>">Hero Banners</a>
            <a href="/admin/media.php?folder=offers" class="btn btn-sm <?= $folderFilter === 'offers' ? 'btn-primary' : 'btn-secondary' ?>">Offer Logos</a>
            <a href="/admin/media.php?folder=general" class="btn btn-sm <?= $folderFilter === 'general' ? 'btn-primary' : 'btn-secondary' ?>">General</a>
        </div>
    </div>

    <?php if (empty($mediaFiles)): ?>
        <p style="text-align: center; color: var(--text-dim); padding: 3rem;">No media files uploaded yet. Built-in images are located in <code>/assets/images/</code>.</p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem;">
            <?php foreach ($mediaFiles as $m): ?>
                <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--admin-card-border); border-radius: var(--radius-sm); overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;">
                    <div style="height: 160px; overflow: hidden; background: #000; display: flex; align-items: center; justify-content: center;">
                        <img src="<?= Helpers::e($m['file_url']) ?>" alt="<?= Helpers::e($m['file_name']) ?>" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                    </div>
                    <div style="padding: 0.85rem;">
                        <div style="font-size: 0.82rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= Helpers::e($m['file_name']) ?>">
                            <?= Helpers::e($m['file_name']) ?>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-dim); margin: 0.25rem 0 0.75rem 0;">
                            <?= round($m['file_size'] / 1024, 1) ?> KB &bull; <?= Helpers::e($m['folder']) ?>
                        </div>
                        <div style="display: flex; gap: 0.4rem;">
                            <button type="button" class="btn btn-secondary btn-sm" style="flex: 1; font-size: 0.75rem;" onclick="navigator.clipboard.writeText('<?= Helpers::e($m['file_url']) ?>'); alert('Copied image URL: <?= Helpers::e($m['file_url']) ?>');">
                                Copy URL
                            </button>
                            <form method="POST" action="/admin/media.php" onsubmit="return confirm('Delete this image?');" style="display: inline;">
                                <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="media_id" value="<?= $m['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">✕</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

