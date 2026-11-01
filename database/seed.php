<?php
declare(strict_types=1);

/**
 * Enterprise Database Seeder
 */

function seedEnterpriseData(\PDO $db): void
{
    // 1. Seed Users (Super Admin, Campaign Manager, Editor)
    $users = [
        ['admin', 'admin@example.com', 'AdminPassword2026!', 'super_admin'],
        ['manager', 'manager@example.com', 'Manager2026!', 'campaign_manager'],
        ['editor', 'editor@example.com', 'Editor2026!', 'editor']
    ];

    $checkUser = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $insertUser = $db->prepare("
        INSERT INTO users (username, email, password_hash, role, created_at, updated_at)
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");

    foreach ($users as [$uname, $email, $pass, $role]) {
        $checkUser->execute([$uname]);
        if ((int)$checkUser->fetchColumn() === 0) {
            $insertUser->execute([$uname, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
        }
    }
    echo "[*] Admin users seeded.\n";

    // 2. Seed Default Domains
    $domains = [
        ['localhost:8000', 1, 'Development local domain'],
        ['datingoffers.com', 0, 'Production primary brand domain'],
        ['datingcampaign1.com', 0, 'Campaign tracking domain']
    ];
    $checkDomain = $db->prepare("SELECT COUNT(*) FROM domains WHERE domain_name = ?");
    $insertDomain = $db->prepare("INSERT INTO domains (domain_name, is_default, notes) VALUES (?, ?, ?)");
    foreach ($domains as [$dname, $isDef, $notes]) {
        $checkDomain->execute([$dname]);
        if ((int)$checkDomain->fetchColumn() === 0) {
            $insertDomain->execute([$dname, $isDef, $notes]);
        }
    }
    echo "[*] Multi-domains initialized.\n";

    // 3. Seed Templates
    $templates = [
        ['fwb-dark-rose', 'Dark Rose Luxury', 'Dark romantic palette with neon rose accents', '#f43f5e', '#8b5cf6'],
        ['instabang-bold-neon', 'Bold Neon High-Contrast', 'High-energy dark mode with bold orange & red neon CTAs', '#ff5722', '#e91e63'],
        ['zoomflirts-vibrant', 'Vibrant Sunset Gradient', 'Energetic gradient aesthetic designed for fast chat dating', '#f59e0b', '#ec4899'],
        ['cindymatches-elegant-gold', 'Champagne & Gold Elegance', 'Sophisticated dating club aesthetic with champagne gold accents', '#eab308', '#d97706'],
        ['premium-club', 'Elite VIP Noir', 'Ultra-clean minimalist luxury club styling', '#a855f7', '#6366f1'],
        ['international-dating', 'Global Passport Blue', 'Vibrant cyan & cobalt blue for cross-border dating', '#06b6d4', '#3b82f6'],
        ['social-connections', 'Nightlife Violet', 'Vibrant cocktail lounge and casual evening vibes', '#d946ef', '#8b5cf6'],
        ['online-chat', 'Direct Messenger Neon', 'High-contrast chat bubble aesthetic', '#10b981', '#06b6d4'],
        ['modern-discovery', 'Crimson Passion', 'Transparent modern relationship discovery layout', '#ef4444', '#f43f5e']
    ];

    $checkTpl = $db->prepare("SELECT COUNT(*) FROM landing_page_templates WHERE slug = ?");
    $insertTpl = $db->prepare("INSERT INTO landing_page_templates (slug, name, description, color_palette, font_family) VALUES (?, ?, ?, ?, 'Plus Jakarta Sans')");
    foreach ($templates as [$slug, $name, $desc, $col1, $col2]) {
        $checkTpl->execute([$slug]);
        if ((int)$checkTpl->fetchColumn() === 0) {
            $insertTpl->execute([$slug, $name, $desc, $col1]);
        }
    }
    echo "[*] Reusable templates seeded.\n";

    // 4. Seed Centralized Offers
    $offers = [
        [
            'name' => 'Friends With Benefits',
            'offer_code' => 'fwb-main',
            'destination_website' => 'https://friends-with-benefits.com',
            'affiliate_tracking_url' => 'https://friends-with-benefits.com/?aff_id=12345',
            'description' => 'Unapologetic transparency and real mutual chemistry for adult casual dating without mixed signals.',
            'image_url' => '/assets/images/hero-couple.jpg',
            'cta_text' => 'Explore Now',
            'priority' => 100
        ],
        [
            'name' => 'InstaBang',
            'offer_code' => 'instabang-main',
            'destination_website' => 'https://www.instabang.com',
            'affiliate_tracking_url' => 'https://www.instabang.com/?aid=67890',
            'description' => 'Direct, bold encounters and immediate hookup connections for open-minded adults.',
            'image_url' => '/assets/images/discovery-chemistry.jpg',
            'cta_text' => 'Get Started',
            'priority' => 90
        ],
        [
            'name' => 'ZoomFlirts',
            'offer_code' => 'zoomflirts-main',
            'destination_website' => 'https://zoomflirts.com',
            'affiliate_tracking_url' => 'https://zoomflirts.com/?ref=zf9988',
            'description' => 'Fast interactive video chats, flirty icebreakers, and live messaging with nearby singles.',
            'image_url' => '/assets/images/discovery-dates.jpg',
            'cta_text' => 'Discover More',
            'priority' => 85
        ],
        [
            'name' => 'CindyMatches',
            'offer_code' => 'cindymatches-main',
            'destination_website' => 'https://cindymatches.com',
            'affiliate_tracking_url' => 'https://cindymatches.com/?aff=cm5544',
            'description' => 'Sophisticated, curated matchmaking for singles seeking genuine romance and companionship.',
            'image_url' => '/assets/images/hero-couple.jpg',
            'cta_text' => 'Explore Matches',
            'priority' => 80
        ],
        [
            'name' => 'Secret Hookups Club',
            'offer_code' => 'secret-hookups',
            'destination_website' => 'https://example.com/hookups',
            'affiliate_tracking_url' => 'https://example.com/hookups?aff=shc1',
            'description' => '100% confidential and discreet late-night casual encounters.',
            'image_url' => '/assets/images/discovery-chemistry.jpg',
            'cta_text' => 'Join Discreetly',
            'priority' => 70
        ],
        [
            'name' => 'Passion Flirt Connect',
            'offer_code' => 'passion-flirt',
            'destination_website' => 'https://example.com/flirt',
            'affiliate_tracking_url' => 'https://example.com/flirt?aff=pfc2',
            'description' => 'Spontaneous cocktail dates, local events, and exciting adult nightlife.',
            'image_url' => '/assets/images/discovery-dates.jpg',
            'cta_text' => 'Connect Tonight',
            'priority' => 65
        ]
    ];

    $checkOffer = $db->prepare("SELECT id FROM offers WHERE offer_code = ? LIMIT 1");
    $insertOffer = $db->prepare("
        INSERT INTO offers (name, offer_code, destination_website, affiliate_tracking_url, description, image_url, cta_text, priority, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");

    $offerIdMap = [];
    foreach ($offers as $off) {
        $checkOffer->execute([$off['offer_code']]);
        $existingId = $checkOffer->fetchColumn();
        if ($existingId) {
            $offerIdMap[$off['offer_code']] = (int)$existingId;
        } else {
            $insertOffer->execute([
                $off['name'],
                $off['offer_code'],
                $off['destination_website'],
                $off['affiliate_tracking_url'],
                $off['description'],
                $off['image_url'],
                $off['cta_text'],
                $off['priority']
            ]);
            $offerIdMap[$off['offer_code']] = (int)$db->lastInsertId();
        }
    }
    echo "[*] Centralized offers initialized.\n";

    // 5. Seed Initial Campaigns
    $checkCamp = $db->prepare("SELECT id FROM campaigns WHERE campaign_code = ?");
    $insertCamp = $db->prepare("INSERT INTO campaigns (campaign_code, name, target_url, is_active, is_default, notes) VALUES (?, ?, ?, 1, ?, ?)");
    
    $checkCamp->execute(['default']);
    $campId = $checkCamp->fetchColumn();
    if (!$campId) {
        $insertCamp->execute(['default', 'Global Dating Campaign', 'https://friends-with-benefits.com/?aff_id=12345', 1, 'Master affiliate campaign']);
        $campId = (int)$db->lastInsertId();
    }

    // 6. Seed Dedicated Landing Pages (4 primary + 5 additional = 9 total)
    $pages = [
        [
            'slug' => 'friends-with-benefits',
            'name' => 'Friends With Benefits - Official Portal',
            'target_dating_website' => 'https://friends-with-benefits.com',
            'page_title' => 'Friends With Benefits – Discover Exciting New Connections',
            'meta_description' => 'Discover exciting new adult connections with verified profiles and zero mixed signals. 18+ only.',
            'hero_headline' => 'Discover Exciting New Connections',
            'hero_subtitle' => 'Skip the endless games. Meet open-minded singles looking for honest intentions, mutual chemistry, and uncomplicated dating.',
            'hero_cta_text' => 'Explore Now',
            'hero_image' => '/assets/images/hero-couple.jpg',
            'theme_color_primary' => '#f43f5e',
            'theme_color_accent' => '#8b5cf6',
            'offers' => ['fwb-main', 'instabang-main', 'zoomflirts-main']
        ],
        [
            'slug' => 'instabang',
            'name' => 'InstaBang - Direct Hookup Gateway',
            'target_dating_website' => 'https://www.instabang.com',
            'page_title' => 'InstaBang – Meet New People and Discover New Connections',
            'meta_description' => 'Fast, bold, and modern adult dating. Find spontaneous encounters nearby tonight.',
            'hero_headline' => 'Meet New People and Discover New Connections',
            'hero_subtitle' => 'Looking for spontaneous dates and immediate chemistry? Connect directly with verified adults who share your exact desires.',
            'hero_cta_text' => 'Get Started',
            'hero_image' => '/assets/images/discovery-chemistry.jpg',
            'theme_color_primary' => '#ff5722',
            'theme_color_accent' => '#e91e63',
            'offers' => ['instabang-main', 'fwb-main', 'cindymatches-main']
        ],
        [
            'slug' => 'zoomflirts',
            'name' => 'ZoomFlirts - Live Interactive Dating',
            'target_dating_website' => 'https://zoomflirts.com',
            'page_title' => 'ZoomFlirts – Start a Conversation. Discover a Connection.',
            'meta_description' => 'Interactive live messaging, instant icebreakers, and webcam flirts with nearby singles.',
            'hero_headline' => 'Start a Conversation. Discover a Connection.',
            'hero_subtitle' => 'Break the ice in seconds with live video flirts and fast private messaging. See who is online and ready to chat right now.',
            'hero_cta_text' => 'Discover More',
            'hero_image' => '/assets/images/discovery-dates.jpg',
            'theme_color_primary' => '#f59e0b',
            'theme_color_accent' => '#ec4899',
            'offers' => ['zoomflirts-main', 'fwb-main', 'instabang-main']
        ],
        [
            'slug' => 'cindymatches',
            'name' => 'CindyMatches - Curated Connections',
            'target_dating_website' => 'https://cindymatches.com',
            'page_title' => 'CindyMatches – Your Next Connection Could Be Closer Than You Think',
            'meta_description' => 'Curated matching and sophisticated relationship discovery for discerning adults.',
            'hero_headline' => 'Your Next Connection Could Be Closer Than You Think',
            'hero_subtitle' => 'Step into an elegant dating space designed for adults who value discretion, emotional intelligence, and natural chemistry.',
            'hero_cta_text' => 'Explore Matches',
            'hero_image' => '/assets/images/hero-couple.jpg',
            'theme_color_primary' => '#eab308',
            'theme_color_accent' => '#d97706',
            'offers' => ['cindymatches-main', 'zoomflirts-main', 'fwb-main']
        ],
        [
            'slug' => 'premium-dating',
            'name' => 'Premium Dating Club - VIP Access',
            'target_dating_website' => 'https://friends-with-benefits.com',
            'page_title' => 'Premium Dating – Elite Singles & Exclusive Adult Connections',
            'meta_description' => 'High-end adult matchmaking with total privacy and concierge matching.',
            'hero_headline' => 'Elite Singles & Curated Adult Connections',
            'hero_subtitle' => 'Access an exclusive community of successful, verified adults who know exactly what they desire.',
            'hero_cta_text' => 'Unlock VIP Access',
            'hero_image' => '/assets/images/hero-couple.jpg',
            'theme_color_primary' => '#a855f7',
            'theme_color_accent' => '#6366f1',
            'offers' => ['fwb-main', 'cindymatches-main', 'secret-hookups']
        ],
        [
            'slug' => 'international-dating',
            'name' => 'International Dating - Global Singles',
            'target_dating_website' => 'https://friends-with-benefits.com',
            'page_title' => 'International Dating – Connect Beyond Borders',
            'meta_description' => 'Meet attractive global singles, travel companions, and international romantic partners.',
            'hero_headline' => 'Connect Beyond Borders. Meet Global Singles.',
            'hero_subtitle' => 'Broaden your romantic horizons. Chat with fascinating people worldwide or find spontaneous travel partners.',
            'hero_cta_text' => 'Discover Worldwide',
            'hero_image' => '/assets/images/discovery-dates.jpg',
            'theme_color_primary' => '#06b6d4',
            'theme_color_accent' => '#3b82f6',
            'offers' => ['fwb-main', 'zoomflirts-main', 'passion-flirt']
        ],
        [
            'slug' => 'social-connections',
            'name' => 'Social Connections - Cocktails & Nights Out',
            'target_dating_website' => 'https://friends-with-benefits.com',
            'page_title' => 'Social Connections – Spontaneous Meetups & Nightlife',
            'meta_description' => 'Find someone new for drinks, rooftop dinners, and exciting weekend plans.',
            'hero_headline' => 'Spontaneous Meetups, Cocktails & Weekend Chemistry',
            'hero_subtitle' => 'Turn boring evenings into unforgettable adventures. Find companions who love trying new spots and having fun.',
            'hero_cta_text' => 'Find Local Singles',
            'hero_image' => '/assets/images/discovery-chemistry.jpg',
            'theme_color_primary' => '#d946ef',
            'theme_color_accent' => '#8b5cf6',
            'offers' => ['passion-flirt', 'fwb-main', 'instabang-main']
        ],
        [
            'slug' => 'online-chat-dating',
            'name' => 'Online Chat & Fast Flirting',
            'target_dating_website' => 'https://zoomflirts.com',
            'page_title' => 'Online Chat & Dating – Real-Time Flirting',
            'meta_description' => 'Instant direct messaging and private flirts with real active members nearby.',
            'hero_headline' => 'Real-Time Flirting & Instant Private Conversations',
            'hero_subtitle' => 'No wait times, no algorithms blocking your messages. Chat directly right now from your phone or desktop.',
            'hero_cta_text' => 'Start Chatting',
            'hero_image' => '/assets/images/discovery-dates.jpg',
            'theme_color_primary' => '#10b981',
            'theme_color_accent' => '#06b6d4',
            'offers' => ['zoomflirts-main', 'instabang-main', 'fwb-main']
        ],
        [
            'slug' => 'modern-relationships',
            'name' => 'Modern Relationship Discovery',
            'target_dating_website' => 'https://friends-with-benefits.com',
            'page_title' => 'Modern Relationships – Dating On Your Own Terms',
            'meta_description' => 'Explore honest casual relationships, open partnerships, and no-strings dating.',
            'hero_headline' => 'Dating on Your Terms with Mutual Intentions',
            'hero_subtitle' => 'Be confident in expressing your desires. Discover like-minded adults who want the same experience as you.',
            'hero_cta_text' => 'Find Your Match',
            'hero_image' => '/assets/images/hero-couple.jpg',
            'theme_color_primary' => '#ef4444',
            'theme_color_accent' => '#f43f5e',
            'offers' => ['fwb-main', 'cindymatches-main', 'instabang-main']
        ]
    ];

    $checkPage = $db->prepare("SELECT id FROM landing_pages WHERE slug = ? LIMIT 1");
    $insertPage = $db->prepare("
        INSERT INTO landing_pages (
            domain_id, campaign_id, slug, name, target_dating_website,
            page_title, meta_description, hero_headline, hero_subtitle,
            hero_cta_text, hero_image, theme_color_primary, theme_color_accent,
            status, created_at, updated_at
        ) VALUES (
            1, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            'published', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
        )
    ");

    $insertLpOffer = $db->prepare("
        INSERT INTO landing_page_offers (landing_page_id, offer_id, display_order, is_featured, custom_cta_text)
        VALUES (?, ?, ?, ?, ?)
        ON CONFLICT(landing_page_id, offer_id) DO NOTHING
    ");

    $insertSection = $db->prepare("
        INSERT INTO landing_page_sections (landing_page_id, section_type, title, subtitle, sort_order, is_visible)
        VALUES (?, ?, ?, ?, ?, 1)
    ");

    // Short links mappings
    $shortLinksMap = [
        'friends-with-benefits' => 'fwb01',
        'instabang' => 'ib02',
        'zoomflirts' => 'zf03',
        'cindymatches' => 'cm04',
        'premium-dating' => 'vip05',
        'international-dating' => 'world06',
        'social-connections' => 'social07',
        'online-chat-dating' => 'chat08',
        'modern-relationships' => 'modern09'
    ];

    $checkShort = $db->prepare("SELECT id FROM short_links WHERE short_code = ? LIMIT 1");
    $insertShort = $db->prepare("INSERT INTO short_links (short_code, landing_page_id, is_active, created_at) VALUES (?, ?, 1, CURRENT_TIMESTAMP)");

    foreach ($pages as $p) {
        $checkPage->execute([$p['slug']]);
        $pageId = $checkPage->fetchColumn();

        if (!$pageId) {
            $insertPage->execute([
                $campId,
                $p['slug'],
                $p['name'],
                $p['target_dating_website'],
                $p['page_title'],
                $p['meta_description'],
                $p['hero_headline'],
                $p['hero_subtitle'],
                $p['hero_cta_text'],
                $p['hero_image'],
                $p['theme_color_primary'],
                $p['theme_color_accent']
            ]);
            $pageId = (int)$db->lastInsertId();

            // Link Multiple Offers
            $order = 1;
            foreach ($p['offers'] as $offCode) {
                if (isset($offerIdMap[$offCode])) {
                    $insertLpOffer->execute([
                        $pageId,
                        $offerIdMap[$offCode],
                        $order,
                        $order === 1 ? 1 : 0,
                        $order === 1 ? $p['hero_cta_text'] : 'Explore Platform'
                    ]);
                    $order++;
                }
            }

            // Create default sections
            $insertSection->execute([$pageId, 'hero', 'Hero Banner', null, 1]);
            $insertSection->execute([$pageId, 'offers_grid', 'Featured Dating Platforms', 'Compare top verified platforms matching your preferences', 2]);
            $insertSection->execute([$pageId, 'why_explore', 'Why It Works', 'Clear intentions, total discretion, and real chemistry', 3]);
            $insertSection->execute([$pageId, 'how_it_works', 'How It Works in 3 Steps', 'Getting connected takes less than two minutes', 4]);
            $insertSection->execute([$pageId, 'faq', 'Frequently Asked Questions', 'Everything you need to know before exploring', 5]);
            $insertSection->execute([$pageId, 'cta_banner', 'Final Call to Action', 'Ready for a new kind of connection?', 6]);
            $insertSection->execute([$pageId, 'footer', 'Compliance & Disclosures', null, 7]);
        }

        // Create Unique Short Tracking Link for this page
        $shortCode = $shortLinksMap[$p['slug']] ?? substr(md5($p['slug']), 0, 5);
        $checkShort->execute([$shortCode]);
        if (!$checkShort->fetchColumn()) {
            $insertShort->execute([$shortCode, $pageId]);
        }
    }
    echo "[*] All 9 dedicated landing pages and unique short tracking links initialized!\n";

    // 7. System Settings
    $settings = [
        'site_title' => 'Enterprise Dating Affiliate Management Hub',
        'allowed_redirect_domains' => "friends-with-benefits.com\n*.friends-with-benefits.com\nwww.instabang.com\ninstabang.com\n*.instabang.com\nzoomflirts.com\n*.zoomflirts.com\ncindymatches.com\n*.cindymatches.com\naffscash.net\n*.affscash.net\nexample.com",
        'enable_open_redirect_protection' => '1',
        'require_age_verification' => '1',
        'affiliate_disclosure_short' => 'This website is an independent promotional affiliate portal. We receive referral compensation when visitors register on third-party dating services at zero additional cost to you.',
        'google_analytics_id' => '',
        'meta_pixel_id' => '',
        'custom_header_scripts' => '',
        'custom_footer_scripts' => '',
    ];

    $checkSetting = $db->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
    $insertSetting = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)");

    foreach ($settings as $key => $val) {
        $checkSetting->execute([$key]);
        if ((int)$checkSetting->fetchColumn() === 0) {
            $insertSetting->execute([$key, $val]);
        } else {
            // Ensure whitelist has all 4 initial domains
            if ($key === 'allowed_redirect_domains') {
                $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?")->execute([$val, $key]);
            }
        }
    }
    echo "[*] Core system & domain security settings synchronized.\n";
}
