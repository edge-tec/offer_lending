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
    <title>Terms of Service | FWB Connect</title>
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
            <h1>Terms of Service</h1>
            <div class="legal-meta">Last Updated: <?= date('F j, Y') ?> &bull; User Agreement</div>

            <div class="legal-content">
                <h2>1. Acceptance of Terms</h2>
                <p>
                    By accessing or using FWB Connect, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use this site.
                </p>

                <h2>2. Age Requirement (Strictly 18+)</h2>
                <p>
                    You must be at least eighteen (18) years of age, or the age of legal majority in your jurisdiction, to browse or interact with this platform. By continuing to use this website, you expressly represent and warrant that you are of legal age.
                </p>

                <h2>3. Informational & Promotional Nature</h2>
                <p>
                    This website serves solely as an informational and promotional landing page for affiliate marketing purposes. We are not a direct dating service or matchmaking agency, and we do not host personal dating accounts or message databases directly on this domain.
                </p>

                <h2>4. Intellectual Property & Brand Clarification</h2>
                <p>
                    All brand assets, custom copy, styling, and graphics created for FWB Connect remain the property of this website. Third-party brand names, trademarks, and references belong to their respective proprietors and are mentioned strictly under nominative fair use for identification and referral purposes.
                </p>

                <h2>5. Limitation of Liability</h2>
                <p>
                    FWB Connect provides all content on an "as is" and "as available" basis without warranties of any kind. Under no circumstances shall FWB Connect or its operators be liable for any direct, indirect, incidental, or consequential damages resulting from your use of this site or third-party services accessed through our referral links.
                </p>
            </div>
        </div>
    </div>
</body>
</html>

