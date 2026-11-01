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
    <title>Privacy Policy | FWB Connect</title>
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
            <h1>Privacy Policy</h1>
            <div class="legal-meta">Effective Date: <?= date('F j, Y') ?> &bull; Data Protection & Privacy Compliance</div>

            <div class="legal-content">
                <h2>1. Information We Collect</h2>
                <p>
                    FWB Connect respects your digital privacy. We operate on a data minimization principle. We do not require visitors to register an account or provide names, physical addresses, or financial payment details on this landing page.
                </p>
                <p>When you visit our website, our servers automatically log technical metadata, including:</p>
                <ul>
                    <li><strong>Pseudonymized IP Hashes:</strong> Your IP address is processed using a one-way cryptographic SHA-256 hash combined with a rotating salt to prevent tracking individuals while allowing unique visitor counting.</li>
                    <li><strong>Device & Browser Metadata:</strong> Device type (Desktop, Mobile, Tablet), browser type, and operating system.</li>
                    <li><strong>Attribution Information:</strong> Referring URLs and marketing campaign parameters (e.g. UTM source, medium, or click identifiers).</li>
                    <li><strong>Country Code:</strong> Estimated country origin derived from server routing headers without pinpoint geolocation.</li>
                </ul>

                <h2>2. How We Use This Information</h2>
                <p>We use the collected anonymous data solely to:</p>
                <ul>
                    <li>Measure aggregate landing page visits, click counts, and click-through rates (CTR).</li>
                    <li>Protect our systems against malicious abuse, automated scrapers, and fraudulent traffic.</li>
                    <li>Accurately pass marketing attribution identifiers to affiliate partner endpoints upon user redirection.</li>
                </ul>

                <h2>3. Cookies and Local Storage</h2>
                <p>
                    We use minimal functional cookies and browser storage (such as saving your 18+ age verification confirmation and cookie preferences). We do not sell, rent, or trade your personal information to any third parties.
                </p>

                <h2>4. Third-Party Websites & External Services</h2>
                <p>
                    When you click on an external link or CTA button to Friends With Benefits, you leave our site and enter a website governed by its own independent privacy policies, terms, and data collection practices. We encourage you to review their privacy statements upon arrival.
                </p>

                <h2>5. Your Rights (GDPR & CCPA)</h2>
                <p>
                    Under applicable data protection regulations, you have rights regarding access, deletion, and restriction of personal data. Since we do not retain identifiable personal data or email addresses for visitors, requests for data retrieval or deletion are addressed based on our anonymized system logs.
                </p>
            </div>
        </div>
    </div>
</body>
</html>

