<?php
declare(strict_types=1);

/**
 * Enterprise User Roles & RBAC Management
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;

$auth = new Auth();
$auth->requireLogin();
$currentUser = $auth->user();

$db = Database::getInstance()->getConnection();
$successMessage = null;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['_csrf_token'] ?? '';
    if ($auth->validateCsrfToken($csrfToken)) {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_user') {
            $username = trim((string)($_POST['username'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $role = $_POST['role'] ?? 'editor';

            if ($username === '' || $email === '' || strlen($password) < 8) {
                $errorMessage = 'All fields are required. Password must be at least 8 characters.';
            } else {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO users (username, email, password_hash, role, created_at, updated_at)
                        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ");
                    $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                    $successMessage = "User '{$username}' with role '{$role}' created successfully.";
                } catch (\Throwable $e) {
                    $errorMessage = 'Username or email already exists.';
                }
            }
        } elseif ($action === 'delete_user') {
            $id = (int)($_POST['user_id'] ?? 0);
            if ($id === (int)$currentUser['id']) {
                $errorMessage = 'You cannot delete your own account.';
            } else {
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                $successMessage = 'User removed.';
            }
        }
    }
}

$users = $db->query("SELECT id, username, email, role, created_at FROM users ORDER BY id ASC")->fetchAll();
$csrfToken = $auth->getCsrfToken();

$currentPage = 'users';
$pageTitle = 'User Management & Roles';
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
            Control team access and delegate responsibilities across Landing Page Builders, Offer Managers, and Analytics Viewers.
        </p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openAdminModal('addUserModal')">
        + Add New Team User
    </button>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Administrative Team Members (<?= count($users) ?>)</h2>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email Address</th>
                    <th>Access Role</th>
                    <th>Created At</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <strong style="color: #fff;"><?= Helpers::e($u['username']) ?></strong>
                            <?php if ((int)$u['id'] === (int)$currentUser['id']): ?>
                                <span class="badge badge-default" style="margin-left: 0.35rem;">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?= Helpers::e($u['email']) ?></td>
                        <td>
                            <span class="badge badge-success"><?= strtoupper(str_replace('_', ' ', Helpers::e($u['role']))) ?></span>
                        </td>
                        <td style="font-size: 0.82rem; color: var(--text-dim);"><?= date('M j, Y H:i', strtotime($u['created_at'])) ?></td>
                        <td style="text-align: right;">
                            <?php if ((int)$u['id'] !== (int)$currentUser['id']): ?>
                                <form method="POST" action="/admin/users.php" style="display: inline;" onsubmit="return confirm('Delete this team user?');">
                                    <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">✕ Delete</button>
                                </form>
                            <?php else: ?>
                                <span style="font-size: 0.78rem; color: var(--text-dim);">Active Session</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add User -->
<div id="addUserModal" class="modal-admin">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="card-title">Add Team User</h3>
            <button type="button" class="modal-close" onclick="closeAdminModal('addUserModal')">&times;</button>
        </div>

        <form method="POST" action="/admin/users.php">
            <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">
            <input type="hidden" name="action" value="create_user">

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password (Min. 8 characters)</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label class="form-label">Role Assignment</label>
                <select name="role" class="form-control">
                    <option value="super_admin">Super Administrator (Full access)</option>
                    <option value="admin">Administrator</option>
                    <option value="campaign_manager">Campaign Manager (Offers & links)</option>
                    <option value="editor">Content Editor (Landing pages)</option>
                    <option value="viewer">Analytics Viewer (Read-only)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Create Account
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

