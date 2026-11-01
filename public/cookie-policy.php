<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use App\Helpers;
Helpers::applySecurityHeaders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookie Policy | FWB Connect</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .legal-page { padding: 8rem 0 5rem 0; }
        .legal-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: 3rem 2.5rem;
            max-width: 860px;
            margin: 0 auto;
        }
        .legal-card h1 { font-size: 2.2rem; margin-bottom: 0.5rem; }
        .legal-meta { color: var(--text-muted); font-size: 0.88rem; margin-bottom: 2rem; border-bottom: 1px solid var(--card-border); padding-bottom: 1.25rem; }
        .legal-content h2 { font-size: 1.35rem; margin: 1.75rem 0 0.75rem 0; color: #fb7185; }
        .legal-content p { color: var(--text-secondary); margin-bottom: 1.2rem; line-height: 1.7; font-size: 0.96rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="/" class="brand-logo">
                <div class="brand-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </div>
                <span>FWB<span class="text-gradient">Connect</span></span>
            </a>
            <a href="/" class="btn btn-secondary" style="padding: 0.5rem 1.2rem; font-size: 0.85rem;">Back to Home</a>
        </div>
    </nav>

    <div class="container legal-page">
        <div class="legal-card">
            <h1>Cookie Policy</h1>
            <div class="legal-meta">Last Updated: <?= date('F j, Y') ?> &bull; ePrivacy Directive & GDPR</div>

            <div class="legal-content">
                <h2>1. What Are Cookies?</h2>
                <p>
                    Cookies are small text files placed on your computer or mobile device when you browse websites. They are widely used to make websites work efficiently, remember user preferences, and provide anonymous reporting insights.
                </p>

                <h2>2. How We Use Cookies</h2>
                <p>We use cookies and browser local storage for the following essential purposes:</p>
                <p>
                    <strong>Functional & Preference Cookies:</strong> We store your 18+ age verification confirmation and cookie consent selection in your browser so you do not have to confirm them repeatedly on each visit.
                </p>
                <p>
                    <strong>Administrative Session Security:</strong> If you are an administrator logging into the backend dashboard, secure session cookies (HTTPOnly, SameSite) are used to authenticate your session and prevent CSRF attacks.
                </p>

                <h2>3. Managing and Disabling Cookies</h2>
                <p>
                    You can control or delete cookies through your web browser settings. Most browsers allow you to block cookies or receive an alert when a cookie is placed. Please note that disabling essential cookies may impact specific functional aspects of the site.
                </p>
            </div>
        </div>
    </div>
</body>
</html>

