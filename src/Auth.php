<?php
declare(strict_types=1);

namespace App;

/**
 * Admin Authentication, Sessions, CSRF & Rate-Limiting
 */
class Auth
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->startSecureSession();
    }

    /**
     * Start secure HTTPOnly session
     */
    private function startSecureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            session_set_cookie_params([
                'lifetime' => 7200,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        } elseif (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    /**
     * Authenticate user with rate-limiting & password verification
     */
    public function login(string $username, string $password): array
    {
        $ip = Helpers::getClientIp();

        // 1. Check Rate Limiting
        if ($this->isRateLimited($ip)) {
            return [
                'success' => false,
                'message' => 'Too many failed login attempts. Please wait 15 minutes before trying again.'
            ];
        }

        // 2. Fetch User
        $stmt = $this->db->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordLoginAttempt($ip, $username, false);
            return [
                'success' => false,
                'message' => 'Invalid username or password credentials.'
            ];
        }

        // 3. Login Success - Regenerate Session ID to prevent session fixation
        $this->recordLoginAttempt($ip, $username, true);
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['auth_user_id'] = (int)$user['id'];
        $_SESSION['auth_username'] = $user['username'];
        $_SESSION['auth_role'] = $user['role'];
        $_SESSION['auth_logged_at'] = time();

        return [
            'success' => true,
            'message' => 'Login successful.'
        ];
    }

    /**
     * Check if currently logged in
     */
    public function check(): bool
    {
        if (empty($_SESSION['auth_user_id'])) {
            return false;
        }

        // Session timeout check (2 hours)
        $loggedAt = $_SESSION['auth_logged_at'] ?? 0;
        if (time() - $loggedAt > 7200) {
            $this->logout();
            return false;
        }

        return true;
    }

    /**
     * Require authentication or redirect
     */
    public function requireLogin(): void
    {
        if (!$this->check()) {
            header('Location: /admin/login.php');
            exit;
        }
    }

    /**
     * Require Admin role
     */
    public function requireAdmin(): void
    {
        $this->requireLogin();
        if (($_SESSION['auth_role'] ?? '') !== 'admin') {
            http_response_code(403);
            die("Access Denied: Administrator privilege required.");
        }
    }

    /**
     * Get Current Logged-in User
     */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return [
            'id' => $_SESSION['auth_user_id'],
            'username' => $_SESSION['auth_username'],
            'role' => $_SESSION['auth_role']
        ];
    }

    /**
     * Terminate Session
     */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Generate or return existing CSRF token
     */
    public function getCsrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /**
     * Validate incoming CSRF token
     */
    public function validateCsrfToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['_csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }

    /**
     * Check if IP is currently rate limited (Disabled to prevent lockouts)
     */
    private function isRateLimited(string $ip): bool
    {
        return false; // Rate-limiting disabled per administrator request
    }

    /**
     * Record login attempt
     */
    private function recordLoginAttempt(string $ip, string $username, bool $success): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (ip_address, username, success, attempted_at)
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$ip, substr($username, 0, 50), $success ? 1 : 0]);
    }

    /**
     * Change user password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters.'];
        }

        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !password_verify($currentPassword, (string)$hash)) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $this->db->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update->execute([$newHash, $userId]);

        return ['success' => true, 'message' => 'Password updated successfully.'];
    }
}
