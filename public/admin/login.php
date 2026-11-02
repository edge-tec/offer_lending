<?php
declare(strict_types=1);

/**
 * Admin Secure Authentication Gateway
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Auth;
use App\Helpers;

Helpers::applySecurityHeaders();

$auth = new Auth();

// If already authenticated, redirect to dashboard
if ($auth->check()) {
    header('Location: /admin/');
    exit;
}

$errorMessage = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrfToken = $_POST['_csrf_token'] ?? '';
    if (!$auth->validateCsrfToken($csrfToken)) {
        $errorMessage = 'Security validation failed (CSRF). Please refresh and try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $errorMessage = 'Please enter both username and password.';
        } else {
            $result = $auth->login($username, $password);
            if ($result['success']) {
                header('Location: /admin/');
                exit;
            } else {
                $errorMessage = $result['message'];
            }
        }
    }
}

$csrfToken = $auth->getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | FWB Connect Manager</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/assets/css/admin.css?v=<?= filemtime(__DIR__ . '/assets/css/admin.css') ?>">

    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at top right, rgba(244, 63, 94, 0.15), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(139, 92, 246, 0.15), transparent 40%),
                        #08090d;
            padding: 1.5rem;
        }
        .login-box {
            width: 100%;
            max-width: 420px;
            background: #11141e;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-lg);
            padding: 2.5rem 2rem;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 40px rgba(244, 63, 94, 0.15);
        }
        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-size: 1.4rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 2rem;
        }
        .login-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--accent-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 4px 15px rgba(244, 63, 94, 0.4);
        }
        .login-subtitle {
            text-align: center;
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>

    <div class="login-box">
        <div class="login-logo">
            <div class="login-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            </div>
            <span>FWB <strong>Manager</strong></span>
        </div>

        <p class="login-subtitle">Sign in to manage dynamic tracking links & analytics</p>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span><?= Helpers::e($errorMessage) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/login.php">
            <input type="hidden" name="_csrf_token" value="<?= Helpers::e($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label" for="username">Username or Email</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="admin" required autofocus autocomplete="username">
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••••••" required autocomplete="current-password">
                <div class="form-text">Default demo: <code>admin</code> / <code>AdminPassword2026!</code></div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem;">
                Sign In to Dashboard
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem; font-size: 0.8rem; color: var(--text-dim);">
            Protected by Secure Sessions &bull; FWB Connect
        </div>
    </div>

</body>
</html>
