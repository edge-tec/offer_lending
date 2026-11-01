<?php
declare(strict_types=1);

/**
 * Enterprise Dynamic Multi-Offer Landing Page View Renderer
 * 
 * Renders any landing page from the database dynamically based on:
 * - $landingPage (array from landing_pages table)
 * - $offers (array of associated offers from landing_page_offers)
 * - $sections (array of sections from landing_page_sections)
 */

if (!isset($landingPage)) {
    require_once __DIR__ . '/../bootstrap.php';
    $slug = trim((string)($_GET['slug'] ?? 'friends-with-benefits'), '/');
    if ($slug === '' || $slug === 'index.php') {
        $slug = 'friends-with-benefits';
    }
    $db = \App\Database::getInstance();
    $page = $db->fetch("SELECT * FROM landing_pages WHERE slug = ? AND status = 'published'", [$slug]);
    if (!$page) {
        $page = $db->fetch("SELECT * FROM landing_pages WHERE status = 'published' ORDER BY id ASC LIMIT 1");
    }
    if (!$page) {
        http_response_code(404);
        echo "Landing page not found.";
        exit;
    }
    $landingPage = $page;
    $offers = $db->fetchAll("
        SELECT o.*, lpo.display_order, lpo.custom_cta_text, lpo.custom_badge
        FROM landing_page_offers lpo
        JOIN offers o ON lpo.offer_id = o.id
        WHERE lpo.landing_page_id = ? AND o.status = 'active'
        ORDER BY lpo.display_order ASC
    ", [$page['id']]);
    $sections = $db->fetchAll("
        SELECT * FROM landing_page_sections
        WHERE landing_page_id = ? AND is_active = 1
        ORDER BY display_order ASC
    ", [$page['id']]);
}

use App\Helpers;
use App\Settings;

/** @var array $landingPage */
/** @var array $offers */
/** @var array $sections */

$settings = (new Settings())->getAll();
$gaId = $settings['google_analytics_id'] ?? '';
$metaPixelId = $settings['meta_pixel_id'] ?? '';
$primaryColor = $landingPage['theme_color_primary'] ?: '#f43f5e';
$accentColor = $landingPage['theme_color_accent'] ?: '#8b5cf6';
$pageTitle = $landingPage['page_title'] ?: $landingPage['name'];
$heroImage = $landingPage['hero_image'] ?: '/assets/images/hero-couple.jpg';
$badgeText = $landingPage['badge_text'] ?: '18+ Adults Only';

// Determine primary offer
$primaryOffer = !empty($offers) ? $offers[0] : null;
$primaryCtaHref = $primaryOffer ? '/click?offer_id=' . $primaryOffer['id'] . '&page_id=' . $landingPage['id'] : '/go';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($pageTitle) ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= Helpers::e($landingPage['meta_description'] ?? '') ?>">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= Helpers::e($pageTitle) ?>">
    <meta property="og:description" content="<?= Helpers::e($landingPage['meta_description'] ?? '') ?>">
    <meta property="og:image" content="<?= Helpers::e($heroImage) ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- Dynamic Custom Theme Palette -->
    <style>
        :root {
            --accent-rose: <?= Helpers::e($primaryColor) ?> !important;
            --accent-rose-hover: <?= Helpers::e($primaryColor) ?> !important;
            --accent-violet: <?= Helpers::e($accentColor) ?> !important;
            --accent-gradient: linear-gradient(135deg, <?= Helpers::e($primaryColor) ?> 0%, <?= Helpers::e($accentColor) ?> 100%) !important;
            --accent-glow: <?= Helpers::e($primaryColor) ?>40 !important;
        }
        .offer-card-featured {
            border: 2px solid <?= Helpers::e($primaryColor) ?> !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 25px <?= Helpers::e($primaryColor) ?>30;
        }
        <?= $landingPage['custom_css'] ?? '' ?>
    </style>

    <?php if (!empty($gaId)): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= Helpers::e($gaId) ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?= Helpers::e($gaId) ?>');
    </script>
    <?php endif; ?>

    <?= $landingPage['custom_header_scripts'] ?? '' ?>
</head>
<body>

    <!-- Ambient Glows -->
    <div class="ambient-glow glow-1"></div>
    <div class="ambient-glow glow-2"></div>
    <div class="ambient-glow glow-3"></div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="/" class="brand-logo">
                <div class="brand-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </div>
                <span><?= Helpers::e($landingPage['name']) ?></span>
            </a>

            <ul class="nav-links">
                <li><a href="#offers" class="nav-link">Top Offers</a></li>
                <li><a href="#why-explore" class="nav-link">Why Choose</a></li>
                <li><a href="#how-it-works" class="nav-link">How It Works</a></li>
                <li><a href="#faq" class="nav-link">FAQ</a></li>
            </ul>

            <div class="nav-cta">
                <span class="badge-18" title="Strictly 18+ Only">18+</span>
                <a href="<?= $primaryCtaHref ?>" class="btn btn-primary cta-btn">
                    <span><?= Helpers::e($landingPage['hero_cta_text']) ?></span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="section-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <span><?= Helpers::e($badgeText) ?></span>
                    </div>

                    <h1 class="hero-headline">
                        <?= Helpers::e($landingPage['hero_headline']) ?>
                    </h1>

                    <p class="hero-subtitle">
                        <?= Helpers::e($landingPage['hero_subtitle']) ?>
                    </p>

                    <div class="hero-actions">
                        <a href="<?= $primaryCtaHref ?>" class="btn btn-primary btn-lg cta-btn">
                            <span><?= Helpers::e($landingPage['hero_cta_text']) ?></span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </a>
                        <a href="#offers" class="btn btn-secondary btn-lg">
                            <span>View All Offers</span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </a>
                    </div>

                    <div class="hero-badges">
                        <div class="trust-badge-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            <span>100% Confidential</span>
                        </div>
                        <div class="trust-badge-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            <span>Verified Profiles</span>
                        </div>
                        <div class="trust-badge-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <span>Instant Direct Access</span>
                        </div>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="hero-card-stack">
                        <div class="floating-chip chip-1">
                            <div class="chip-dot"></div>
                            <span>Active Members Nearby</span>
                        </div>
                        <div class="floating-chip chip-2">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fb7185" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            <span>Private Discreet Chats</span>
                        </div>

                        <div class="hero-main-card">
                            <div class="hero-img-wrapper">
                                <img src="<?= Helpers::e($heroImage) ?>" alt="<?= Helpers::e($landingPage['name']) ?>" loading="eager">
                                <div class="hero-img-overlay"></div>
                            </div>
                            <div class="hero-card-info">
                                <span class="hero-card-tag">Match & Connect</span>
                                <div class="hero-card-name"><?= Helpers::e($primaryOffer['name'] ?? 'Top Platform') ?></div>
                                <div class="hero-card-desc"><?= Helpers::e($primaryOffer['description'] ?? 'Connect with verified singles.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Multi-Offer Grid Section -->
    <section id="offers" class="section" style="background: rgba(15, 17, 24, 0.6);">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Verified Platforms</div>
                <h2 class="section-title">Featured Dating <span class="text-gradient">Opportunities</span></h2>
                <p class="section-subtitle">
                    Select from our top curated adult connection platforms. Each platform offers unique features tailored to your dating goals.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
                <?php foreach ($offers as $off): ?>
                    <?php 
                        $offerCtaHref = '/click?offer_id=' . $off['id'] . '&page_id=' . $landingPage['id'];
                        $isFeatured = (bool)$off['is_featured'];
                    ?>
                    <div class="feature-card <?= $isFeatured ? 'offer-card-featured' : '' ?>" style="display: flex; flex-direction: column; justify-content: space-between; position: relative;">
                        <?php if ($isFeatured): ?>
                            <div style="position: absolute; top: -12px; right: 20px; background: var(--accent-gradient); color: #fff; padding: 0.25rem 0.85rem; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">
                                Top Recommendation
                            </div>
                        <?php endif; ?>

                        <div>
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.25rem;">
                                <div style="width: 52px; height: 52px; border-radius: 12px; overflow: hidden; background: #222; flex-shrink: 0;">
                                    <img src="<?= Helpers::e($off['image_url'] ?: '/assets/images/hero-couple.jpg') ?>" alt="<?= Helpers::e($off['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div>
                                    <h3 style="font-size: 1.3rem; margin-bottom: 0.2rem;"><?= Helpers::e($off['name']) ?></h3>
                                    <span style="font-size: 0.78rem; color: #fb7185; text-transform: uppercase; font-weight: 700;">Verified Platform</span>
                                </div>
                            </div>

                            <p style="color: var(--text-secondary); font-size: 0.94rem; line-height: 1.6; margin-bottom: 1.5rem;">
                                <?= Helpers::e($off['description']) ?>
                            </p>
                        </div>

                        <div>
                            <a href="<?= $offerCtaHref ?>" class="btn btn-primary cta-btn" style="width: 100%;">
                                <span><?= Helpers::e($off['final_cta_text'] ?: 'Explore Now') ?></span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Why Choose Section -->
    <section id="why-explore" class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Better by Design</div>
                <h2 class="section-title">Why Connect Through <span class="text-gradient"><?= Helpers::e($landingPage['name']) ?></span></h2>
                <p class="section-subtitle">A modern, discreet gateway to authentic connections on your terms.</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    </div>
                    <h3 class="feature-title">Unapologetic Transparency</h3>
                    <p class="feature-desc">Connect with open-minded adults who state their exact relationship desires and intentions upfront.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>
                    <h3 class="feature-title">Complete Discretion</h3>
                    <p class="feature-desc">Enjoy private profiles, blurred photo options, and confidential messaging with zero social media linking.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    </div>
                    <h3 class="feature-title">Zero Algorithmic Swiping</h3>
                    <p class="feature-desc">Skip endless swiping games. Browse active members in your area and begin chatting immediately.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="section" style="background: rgba(15, 17, 24, 0.4);">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Fast & Easy</div>
                <h2 class="section-title">How It Works in <span class="text-gradient">3 Simple Steps</span></h2>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-num">01</div>
                    <h3 class="step-title">Select Your Preferred Platform</h3>
                    <p class="step-desc">Pick an offer aligned with your dating goals—from casual fun to live messaging.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">02</div>
                    <h3 class="step-title">Create Your Discreet Profile</h3>
                    <p class="step-desc">Sign up confidentially without using your real name or external social accounts.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">03</div>
                    <h3 class="step-title">Connect On Your Own Terms</h3>
                    <p class="step-desc">Chat directly with local singles and plan spontaneous meetups whenever you feel ready.</p>
                </div>
            </div>

            <div style="text-align: center; margin-top: 3.5rem;">
                <a href="<?= $primaryCtaHref ?>" class="btn btn-primary btn-lg cta-btn">
                    <span><?= Helpers::e($landingPage['hero_cta_text']) ?></span>
                </a>
            </div>
        </div>
    </section>

    <!-- FAQs -->
    <section id="faq" class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Common Questions</div>
                <h2 class="section-title">Frequently Asked <span class="text-gradient">Questions</span></h2>
            </div>

            <div class="faq-container">
                <div class="faq-item">
                    <button type="button" class="faq-question">
                        <span>Are these platforms free to join?</span>
                        <div class="faq-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
                    </button>
                    <div class="faq-answer"><p>Yes, standard registration and browsing profiles is free across all featured platforms. Premium communication upgrades are available at your discretion.</p></div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question">
                        <span>Is my privacy and identity secure?</span>
                        <div class="faq-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
                    </button>
                    <div class="faq-answer"><p>Yes. Discretion is paramount. You are never required to use your full real name or share personal telephone numbers.</p></div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question">
                        <span>What is the minimum age to register?</span>
                        <div class="faq-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
                    </button>
                    <div class="faq-answer"><p>All featured dating services are strictly restricted to adults aged 18 years and older.</p></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA Banner -->
    <section class="section">
        <div class="container">
            <div class="cta-banner">
                <div class="cta-banner-content">
                    <h2 class="cta-banner-title">Ready for Your Next <span class="text-gradient">Connection?</span></h2>
                    <p class="cta-banner-desc">Join thousands exploring transparent, modern dating without the drama. Your next conversation could be just one click away.</p>
                    <a href="<?= $primaryCtaHref ?>" class="btn btn-primary btn-lg cta-btn">
                        <span><?= Helpers::e($landingPage['hero_cta_text']) ?></span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-disclosure-box">
                <strong>Affiliate & Advertising Disclosure:</strong> This website is an independent promotional and affiliate portal designed to connect adult visitors with third-party dating platforms. We receive referral compensation when visitors click on affiliate links and register or subscribe. The featured services are operated by independent third parties. You must be at least 18 years old to access and participate.
            </div>

            <div class="footer-bottom">
                <div>&copy; <?= date('Y') ?> <?= Helpers::e($landingPage['name']) ?>. Strictly 18+ Only.</div>
                <div><a href="/privacy.php" style="color: #94a3b8;">Privacy Policy</a> &bull; <a href="/terms.php" style="color: #94a3b8;">Terms of Service</a> &bull; <a href="/affiliate-disclosure.php" style="color: #94a3b8;">Affiliate Disclosure</a></div>
            </div>
        </div>
    </footer>

    <!-- Age Verification Modal (18+) -->
    <div id="ageGateModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-icon-badge">18+</div>
            <h3 class="modal-title">Age Verification Required</h3>
            <p class="modal-desc">This platform contains adult dating content and services intended solely for individuals of legal age. By entering, you confirm that you are at least 18 years of age.</p>
            <div class="modal-actions">
                <button type="button" id="btnAgeExit" class="btn btn-secondary">I Am Under 18</button>
                <button type="button" id="btnAgeConfirm" class="btn btn-primary">I Am 18 or Older</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <?= $landingPage['custom_footer_scripts'] ?? '' ?>
</body>
</html>

