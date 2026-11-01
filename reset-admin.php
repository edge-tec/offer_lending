<?php
declare(strict_types=1);

/**
 * CLI Helper: Unlock Admin & Reset Credentials
 * Usage: php reset-admin.php [new_password]
 */

require_once __DIR__ . '/bootstrap.php';

use App\Database;

$newPassword = $argv[1] ?? 'AdminPassword2026!';
$username = 'admin';

try {
    $db = Database::getInstance()->getConnection();

    // 1. Clear all rate-limiting lockouts
    $db->exec("DELETE FROM login_attempts");

    // 2. Hash new password
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // 3. Check if admin exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $userId = $stmt->fetchColumn();

    if ($userId) {
        $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$passwordHash, $userId]);
    } else {
        $db->prepare("
            INSERT INTO users (username, email, password_hash, role, created_at, updated_at)
            VALUES (?, 'admin@example.com', ?, 'super_admin', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ")->execute([$username, $passwordHash]);
    }

    echo "\n=======================================================\n";
    echo "  Admin Account Unlocked & Reset Successfully!\n";
    echo "=======================================================\n";
    echo "  Login URL : /admin/login.php\n";
    echo "  Username  : admin\n";
    echo "  Password  : {$newPassword}\n";
    echo "  Status    : Rate-limit lockouts cleared. You can sign in immediately.\n";
    echo "=======================================================\n\n";
} catch (\Throwable $e) {
    echo "Error resetting admin: " . $e->getMessage() . "\n";
    exit(1);
}
