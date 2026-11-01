<?php
declare(strict_types=1);

/**
 * Modern Dating Affiliate Landing Page
 * Promotes: Friends With Benefits (Dynamic Affiliate Gateway)
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Settings;
use App\Helpers;

Helpers::applySecurityHeaders();

$settingsService = new Settings();
$settings = $settingsService->getAll();

$siteTitle = $settings['site_title'] ?? 'FWB Connect | Premium Dating Gateway';
$gaId = $settings['google_analytics_id'] ?? '';
$metaPixelId = $settings['meta_pixel_id'] ?? '';
$headerScripts = $settings['custom_header_scripts'] ?? '';
$footerScripts = $settings['custom_footer_scripts'] ?? '';
$affiliateDisclaimer = $settings['affiliate_disclosure_short'] ?? 'This site is an independent promotional partner. We receive commission for user referrals at no cost to you.';

// Current campaign query code if specified in URL
$campaignParam = isset($_GET['c']) ? '?c=' . urlencode((string)$_GET['c']) : '';
$baseCtaUrl = '/go' . $campaignParam;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>Friends With Benefits – Your Next Connection Starts Here | FWB Connect</title>
    
    <!-- Primary SEO Meta Tags -->
    <meta name="title" content="Friends With Benefits – Your Next Connection Starts Here">
    <meta name="description" content="Discover new connections, meet interesting people, and explore exciting dating opportunities. Honest intentions, verified profiles, and zero mixed signals. 18+ only.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="http://localhost:8000/">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="http://localhost:8000/">
    <meta property="og:title" content="Friends With Benefits – Your Next Connection Starts Here">
    <meta property="og:description" content="Discover new connections, meet interesting people, and explore exciting dating opportunities. Your next conversation could be just one click away.">
    <meta property="og:image" content="https://images.unsplash.com/photo-1517841905240-472988babdf9?w=1200&auto=format&fit=crop&q=80">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="Friends With Benefits – Your Next Connection Starts Here">
    <meta property="twitter:description" content="Discover new connections, meet interesting people, and explore exciting dating opportunities.">
    <meta property="twitter:image" content="https://images.unsplash.com/photo-1517841905240-472988babdf9?w=1200&auto=format&fit=crop&q=80">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebPage",
      "name": "Friends With Benefits – Independent Dating Gateway",
      "description": "Discover new connections, meet interesting people, and explore exciting dating opportunities.",
      "url": "http://localhost:8000",
      "isFamilyFriendly": false
    }
    </script>

    <?php if (!empty($gaId)): ?>
    <!-- Google Analytics (GA4) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= Helpers::e($gaId) ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?= Helpers::e($gaId) ?>');
    </script>
    <?php endif; ?>

    <?php if (!empty($metaPixelId)): ?>
    <!-- Meta Pixel -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?= Helpers::e($metaPixelId) ?>');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=<?= Helpers::e($metaPixelId) ?>&ev=PageView&noscript=1"/></noscript>
    <?php endif; ?>

    <?= $headerScripts ?>
</head>
<body>

    <!-- Ambient Gradient Glows -->
    <div class="ambient-glow glow-1"></div>
    <div class="ambient-glow glow-2"></div>
    <div class="ambient-glow glow-3"></div>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="/" class="brand-logo">
                <div class="brand-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </div>
                <span>FWB<span class="text-gradient">Connect</span></span>
            </a>

            <ul class="nav-links">
                <li><a href="#why-explore" class="nav-link">Why Explore</a></li>
                <li><a href="#connections" class="nav-link">Discover</a></li>
                <li><a href="#how-it-works" class="nav-link">How It Works</a></li>
                <li><a href="#faq" class="nav-link">FAQ</a></li>
            </ul>

            <div class="nav-cta">
                <span class="badge-18" title="Strictly 18+ Only">18+</span>
                <a href="<?= $baseCtaUrl ?>" class="btn btn-primary cta-btn">
                    <span>EXPLORE NOW</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
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
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                        <span>The Modern Connection Platform</span>
                    </div>

                    <h1 class="hero-headline">
                        Your Next Connection <span class="text-gradient">Starts Here.</span>
                    </h1>

                    <p class="hero-subtitle">
                        Discover new connections, meet interesting people, and explore exciting dating opportunities. Your next conversation could be just one click away.
                    </p>

                    <div class="hero-actions">
                        <a href="<?= $baseCtaUrl ?>" class="btn btn-primary btn-lg cta-btn">
                            <span>EXPLORE NOW</span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                        <a href="#why-explore" class="btn btn-secondary btn-lg">
                            <span>Discover More</span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </a>
                    </div>

                    <div class="hero-badges">
                        <div class="trust-badge-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <span>100% Confidential</span>
                        </div>
                        <div class="trust-badge-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span>Verified Profiles</span>
                        </div>
                        <div class="trust-badge-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span>Fast Direct Chats</span>
                        </div>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="hero-card-stack">
                        <!-- Floating Micro Badges -->
                        <div class="floating-chip chip-1">
                            <div class="chip-dot"></div>
                            <span>Active Members Nearby</span>
                        </div>
                        <div class="floating-chip chip-2">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fb7185" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <span>Instant Private Messaging</span>
                        </div>

                        <!-- Main Showcase Card -->
                        <div class="hero-main-card">
                            <div class="hero-img-wrapper">
                                <img src="/assets/images/hero-couple.jpg" alt="Modern Connections - Beautiful Couple" loading="eager">
                                <div class="hero-img-overlay"></div>
                            </div>
                            <div class="hero-card-info">
                                <span class="hero-card-tag">Match & Connect</span>
                                <div class="hero-card-name">Mutual Chemistry</div>
                                <div class="hero-card-desc">Honest intentions, genuine conversations, and spontaneous vibes.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 1: Why Explore Friends With Benefits? -->
    <section id="why-explore" class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Why It Works</div>
                <h2 class="section-title">Why Explore <span class="text-gradient">Friends With Benefits?</span></h2>
                <p class="section-subtitle">
                    Dating today doesn't have to be complicated or filled with mixed messages. Experience an open, refreshingly candid space designed for people who know what they want.
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </div>
                    <h3 class="feature-title">Unapologetic Transparency</h3>
                    <p class="feature-desc">Skip the games and guessing. Connect with adults who are clear about their expectations, lifestyles, and dating desires from day one.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Total Discretion & Privacy</h3>
                    <p class="feature-desc">Your personal space is sacred. Enjoy robust privacy controls, confidential browsing options, and end-to-end discrete profile protections.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Real Mutual Intentions</h3>
                    <p class="feature-desc">Whether you are looking for casual companionship, exciting nightlife dates, or continuous chemistry, find people aligned with your vibe.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    </div>
                    <h3 class="feature-title">Zero Swiping Fatigue</h3>
                    <p class="feature-desc">Tired of algorithms designed to keep you trapped? Access direct search filters, location-based browsing, and immediate chat opportunities.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: Discover New Connections -->
    <section id="connections" class="section" style="background: rgba(15, 17, 24, 0.5);">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Lifestyle & Chemistry</div>
                <h2 class="section-title">Discover <span class="text-gradient">New Connections</span></h2>
                <p class="section-subtitle">
                    Every connection is unique. Choose the kind of experiences you want to share with someone new.
                </p>
            </div>

            <div class="discovery-grid">
                <div class="discovery-card">
                    <img src="/assets/images/discovery-dates.jpg" alt="Casual Dates & Drinks" loading="lazy">
                    <div class="discovery-overlay">
                        <span class="discovery-badge">Social & Fun</span>
                        <h4 class="discovery-title">Casual Dates & Drinks</h4>
                        <p class="discovery-desc">Meet up for dinner, late-night drinks, or exploring the newest spots in your city without pressure.</p>
                    </div>
                </div>

                <div class="discovery-card">
                    <img src="/assets/images/discovery-chemistry.jpg" alt="No-Strings Chemistry" loading="lazy">
                    <div class="discovery-overlay">
                        <span class="discovery-badge">Excitement</span>
                        <h4 class="discovery-title">No-Strings Chemistry</h4>
                        <p class="discovery-desc">Clear boundaries and magnetic energy. Enjoy fun adult company with complete mutual understanding.</p>
                    </div>
                </div>

                <div class="discovery-card">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=800&auto=format&fit=crop&q=80" alt="Weekend Adventures" loading="lazy" onerror="this.style.display='none';">
                    <div class="discovery-overlay">
                        <span class="discovery-badge">Lifestyle</span>
                        <h4 class="discovery-title">Travel & Getaways</h4>
                        <p class="discovery-desc">Find spontaneous travel companions and companions who love concerts, road trips, and weekend escapes.</p>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 3.5rem;">
                <a href="<?= $baseCtaUrl ?>" class="btn btn-primary btn-lg cta-btn">
                    <span>EXPLORE ALL CONNECTIONS</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Section 3: Modern Dating Experience -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Better by Design</div>
                <h2 class="section-title">The <span class="text-gradient">Modern Dating Experience</span></h2>
                <p class="section-subtitle">
                    How modern adult dating platforms compare to traditional matchmaking apps.
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card" style="border-top: 3px solid #8b5cf6;">
                    <h3 class="feature-title">Smart Boundary Settings</h3>
                    <p class="feature-desc">Set your preferences and expectations clearly on your profile so only people who align with your mindset get in touch.</p>
                </div>
                <div class="feature-card" style="border-top: 3px solid #f43f5e;">
                    <h3 class="feature-title">Real-Time Activity Feeds</h3>
                    <p class="feature-desc">Know who is online right now in your geographic area. No ghost profiles or inactive accounts lingering for months.</p>
                </div>
                <div class="feature-card" style="border-top: 3px solid #10b981;">
                    <h3 class="feature-title">Safe & Discreet Communication</h3>
                    <p class="feature-desc">Share media and chat inside an encrypted environment without handing over your personal telephone number or social handles.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 4: How It Works (3 Simple Steps) -->
    <section id="how-it-works" class="section" style="background: rgba(15, 17, 24, 0.4);">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Get Started</div>
                <h2 class="section-title">How It Works in <span class="text-gradient">3 Simple Steps</span></h2>
                <p class="section-subtitle">
                    Getting connected takes less than two minutes. Here is the straightforward process:
                </p>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-num">01</div>
                    <h3 class="step-title">Create Your Profile</h3>
                    <p class="step-desc">Sign up confidentially. Select what you are looking for, specify your interests, and set your private privacy boundaries.</p>
                </div>

                <div class="step-card">
                    <div class="step-num">02</div>
                    <h3 class="step-title">Browse & Match</h3>
                    <p class="step-desc">Explore verified members who match your preferences nearby. Filter by distance, interests, and mutual intentions.</p>
                </div>

                <div class="step-card">
                    <div class="step-num">03</div>
                    <h3 class="step-title">Connect On Your Terms</h3>
                    <p class="step-desc">Break the ice with direct messages. Start a genuine conversation and arrange a meetup whenever you feel comfortable.</p>
                </div>
            </div>

            <div style="text-align: center; margin-top: 3.5rem;">
                <a href="<?= $baseCtaUrl ?>" class="btn btn-primary btn-lg cta-btn">
                    <span>START CONNECTING NOW</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Section 5: Frequently Asked Questions -->
    <section id="faq" class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Common Questions</div>
                <h2 class="section-title">Frequently Asked <span class="text-gradient">Questions</span></h2>
                <p class="section-subtitle">
                    Everything you need to know before exploring new connections.
                </p>
            </div>

            <div class="faq-container">
                <div class="faq-item">
                    <button type="button" class="faq-question">
                        <span>Is Friends With Benefits free to explore?</span>
                        <div class="faq-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </button>
                    <div class="faq-answer">
                        <p>Yes, initial registration and browsing profiles are generally free. Users can set up a profile, review matching criteria, and view available members in their area before deciding to unlock premium communication features.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question">
                        <span>How is member privacy and discretion protected?</span>
                        <div class="faq-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </button>
                    <div class="faq-answer">
                        <p>Privacy is paramount. You are never required to use your full real name or link external social media accounts. You maintain complete control over who views your private photos and when you respond to messages.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question">
                        <span>What age do I need to be to participate?</span>
                        <div class="faq-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </button>
                    <div class="faq-answer">
                        <p>This service is strictly restricted to adults aged 18 years and older. Age verification checks are enforced to maintain an adult-only dating environment.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question">
                        <span>What kinds of dating connections can I expect?</span>
                        <div class="faq-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </button>
                    <div class="faq-answer">
                        <p>Members join for a variety of adult relationship formats, including casual companionship, no-strings-attached dating, social activities, and open relationships. Intentions are stated upfront so everyone remains on the same page.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question">
                        <span>Is this landing page affiliated with the promoted platform?</span>
                        <div class="faq-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </button>
                    <div class="faq-answer">
                        <p>We are an independent marketing affiliate partner. We review and promote dating services through affiliate referral agreements. The promoted dating service is operated separately by its independent parent company.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 6: Final Conversion CTA -->
    <section class="section">
        <div class="container">
            <div class="cta-banner">
                <div class="cta-banner-content">
                    <div class="section-badge">Ready When You Are</div>
                    <h2 class="cta-banner-title">Ready for a New Kind of <span class="text-gradient">Connection?</span></h2>
                    <p class="cta-banner-desc">
                        Discover new connections, meet interesting people, and explore exciting dating opportunities. Your next conversation could be just one click away.
                    </p>
                    <a href="<?= $baseCtaUrl ?>" class="btn btn-primary btn-lg cta-btn">
                        <span>EXPLORE NOW</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer & Compliance -->
    <footer class="footer">
        <div class="container">
            <div class="footer-disclosure-box">
                <strong>Affiliate & Advertising Disclosure:</strong> This website is an independent promotional and affiliate portal designed to connect adult visitors with third-party dating platforms. We receive referral compensation when visitors click on affiliate links and register or subscribe to services on the destination site. This referral relationship does not influence our objective presentation of services. Friends With Benefits is operated by an independent third party. You must be at least 18 years old to access and participate.
            </div>

            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="/" class="brand-logo">
                        <div class="brand-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </div>
                        <span>FWB<span class="text-gradient">Connect</span></span>
                    </a>
                    <p>
                        Your trusted gateway to transparent, modern dating experiences. Connect on your own terms with verified singles seeking authentic chemistry.
                    </p>
                </div>

                <div>
                    <h4 class="footer-title">Navigation</h4>
                    <ul class="footer-links">
                        <li><a href="#why-explore">Why Explore</a></li>
                        <li><a href="#connections">Discover</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="#faq">Frequently Asked Questions</a></li>
                        <li><a href="<?= $baseCtaUrl ?>" class="cta-btn">Join Now</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="footer-title">Legal & Trust</h4>
                    <ul class="footer-links">
                        <li><a href="/privacy.php">Privacy Policy</a></li>
                        <li><a href="/terms.php">Terms of Service</a></li>
                        <li><a href="/affiliate-disclosure.php">Affiliate Disclosure</a></li>
                        <li><a href="/cookie-policy.php">Cookie Policy</a></li>
                        <li><a href="/admin/login.php">Admin Login</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div>
                    &copy; <?= date('Y') ?> FWB Connect. All rights reserved. Strictly 18+ Only.
                </div>
                <div>
                    Independent Promotional Portal &bull; SSL Secured &bull; Safe Browsing
                </div>
            </div>
        </div>
    </footer>

    <!-- 18+ Age Gate Modal -->
    <div id="ageGateModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-icon-badge">18+</div>
            <h3 class="modal-title">Age Verification Required</h3>
            <p class="modal-desc">
                This platform contains adult dating content and services intended solely for individuals of legal age. By entering, you confirm that you are at least 18 years of age.
            </p>
            <div class="modal-actions">
                <button type="button" id="btnAgeExit" class="btn btn-secondary">I Am Under 18</button>
                <button type="button" id="btnAgeConfirm" class="btn btn-primary">I Am 18 or Older</button>
            </div>
        </div>
    </div>

    <!-- Cookie Consent Banner -->
    <div id="cookieBanner" class="cookie-banner">
        <div class="cookie-text">
            We use essential cookies and anonymous analytics to enhance your browsing experience and ensure accurate referral tracking. See our <a href="/cookie-policy.php">Cookie Policy</a>.
        </div>
        <div>
            <button type="button" id="btnAcceptCookies" class="btn btn-primary" style="padding: 0.5rem 1.25rem; font-size: 0.85rem;">
                Accept
            </button>
        </div>
    </div>

    <!-- Mobile Sticky Floating CTA Bar -->
    <div class="mobile-sticky-cta">
        <div class="mobile-sticky-info">
            <span class="mobile-sticky-dot"></span>
            <span class="mobile-sticky-text">1,400+ Active Nearby</span>
        </div>
        <a href="<?= $baseCtaUrl ?>" class="btn btn-primary cta-btn">
            <span>Explore Now</span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </a>
    </div>

    <!-- Script -->
    <script src="/assets/js/main.js"></script>
    <?= $footerScripts ?>
</body>
</html>

