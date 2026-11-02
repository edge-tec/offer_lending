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
    <title>Affiliate & Advertising Disclosure | FWB Connect</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
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
        .legal-content ul { color: var(--text-secondary); margin: 0 0 1.2rem 1.5rem; font-size: 0.96rem; }
        .legal-content li { margin-bottom: 0.5rem; }
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
            <h1>Affiliate & Advertising Disclosure</h1>
            <div class="legal-meta">Last Updated: <?= date('F j, Y') ?> &bull; Federal Trade Commission (FTC) & ASA Compliance</div>

            <div class="legal-content">
                <h2>1. Independent Referral Portal</h2>
                <p>
                    FWB Connect (this website) operates as an independent promotional and advertising portal designed to provide information, introductory guides, and access to third-party dating and social interaction services, specifically Friends With Benefits (https://friends-with-benefits.com).
                </p>

                <h2>2. Financial Compensation & Referral Links</h2>
                <p>
                    In compliance with the Federal Trade Commission (FTC) guidelines and international advertising standards, please be advised that links on this website leading to Friends With Benefits are affiliate tracking links. When you click on one of our call-to-action (CTA) buttons and subsequently register, subscribe, or interact on the destination platform, we may receive financial compensation (commission) from the affiliate program or operator of the promoted platform.
                </p>
                <p>
                    This compensation comes at <strong>zero additional expense to you</strong>. Your registration costs, membership fees, or promotional benefits remain identical whether you access the platform through our tracking links or directly.
                </p>

                <h2>3. Third-Party Independence</h2>
                <p>
                    Friends With Benefits is owned and operated by an independent third-party entity. FWB Connect is not owned, operated, or controlled by Friends With Benefits. We do not provide customer support, billing processing, or account administration for the third-party destination service. For inquiries concerning your account on the third-party service, please contact their official customer support directly.
                </p>

                <h2>4. Accuracy and Honest Representation</h2>
                <p>
                    We do not publish fabricated user testimonials, fictitious dating profiles, or deceptive guarantees of meeting specific individuals. Any descriptions regarding the third-party service reflect general public information and independent editorial opinion.
                </p>

                <h2>5. Contact Us</h2>
                <p>
                    If you have questions regarding our affiliate relationships or this disclosure, please contact our administration via the website contact portal.
                </p>
            </div>
        </div>
    </div>
</body>
</html>

