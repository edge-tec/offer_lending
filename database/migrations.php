<?php
declare(strict_types=1);

/**
 * Enterprise Database Migration Script
 */

require_once __DIR__ . '/../bootstrap.php';

function runMigrations(): void
{
    echo "========================================================\n";
    echo "  Enterprise Dating Platform - Database Migration\n";
    echo "========================================================\n";

    $db = \App\Database::getInstance()->getConnection();
    $driver = \App\Database::getInstance()->getDriver();

    echo "[*] Connected using driver: {$driver}\n";

    if ($driver === 'sqlite') {
        runSqliteMigrations($db);
    } else {
        runMysqlMigrations($db);
    }

    require_once __DIR__ . '/seed.php';
    seedEnterpriseData($db);

    echo "[✓] Enterprise migration and seeding completed successfully!\n";
}

function runSqliteMigrations(\PDO $db): void
{
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'admin',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS domains (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            domain_name TEXT NOT NULL UNIQUE,
            is_default INTEGER NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1,
            ssl_active INTEGER NOT NULL DEFAULT 1,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS landing_page_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            description TEXT NULL,
            color_palette TEXT NOT NULL DEFAULT 'dark-rose',
            font_family TEXT NOT NULL DEFAULT 'Plus Jakarta Sans',
            default_sections_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS offers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            offer_code TEXT NOT NULL UNIQUE,
            destination_website TEXT NOT NULL,
            affiliate_tracking_url TEXT NOT NULL,
            description TEXT NULL,
            image_url TEXT NULL,
            cta_text TEXT NOT NULL DEFAULT 'Explore Now',
            geo_targets TEXT NULL DEFAULT 'ALL',
            device_targets TEXT NOT NULL DEFAULT 'ALL',
            priority INTEGER NOT NULL DEFAULT 10,
            status TEXT NOT NULL DEFAULT 'active',
            total_clicks INTEGER NOT NULL DEFAULT 0,
            start_date TEXT NULL,
            expiration_date TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS campaigns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            campaign_code TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            target_url TEXT NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            is_default INTEGER NOT NULL DEFAULT 0,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS landing_pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            domain_id INTEGER NULL,
            campaign_id INTEGER NULL,
            slug TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            target_dating_website TEXT NULL,
            template_id INTEGER NULL,
            status TEXT NOT NULL DEFAULT 'published',
            page_title TEXT NOT NULL,
            meta_description TEXT NULL,
            hero_headline TEXT NOT NULL,
            hero_subtitle TEXT NULL,
            hero_cta_text TEXT NOT NULL DEFAULT 'EXPLORE NOW',
            hero_image TEXT NULL,
            badge_text TEXT NULL DEFAULT '18+ Adults Only',
            theme_color_primary TEXT NOT NULL DEFAULT '#f43f5e',
            theme_color_accent TEXT NOT NULL DEFAULT '#8b5cf6',
            theme_mode TEXT NOT NULL DEFAULT 'dark',
            custom_css TEXT NULL,
            custom_header_scripts TEXT NULL,
            custom_footer_scripts TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS landing_page_sections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            landing_page_id INTEGER NOT NULL,
            section_type TEXT NOT NULL,
            title TEXT NULL,
            subtitle TEXT NULL,
            content_json TEXT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            is_visible INTEGER NOT NULL DEFAULT 1,
            FOREIGN KEY (landing_page_id) REFERENCES landing_pages (id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS landing_page_offers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            landing_page_id INTEGER NOT NULL,
            offer_id INTEGER NOT NULL,
            display_order INTEGER NOT NULL DEFAULT 0,
            is_featured INTEGER NOT NULL DEFAULT 0,
            custom_cta_text TEXT NULL,
            custom_badge TEXT NULL,
            UNIQUE(landing_page_id, offer_id),
            FOREIGN KEY (landing_page_id) REFERENCES landing_pages (id) ON DELETE CASCADE,
            FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS short_links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            short_code TEXT NOT NULL UNIQUE,
            landing_page_id INTEGER NOT NULL,
            campaign_id INTEGER NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            clicks_count INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (landing_page_id) REFERENCES landing_pages (id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS affiliate_tracking_urls (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            offer_id INTEGER NOT NULL,
            label TEXT NOT NULL,
            url TEXT NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS media_library (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            file_name TEXT NOT NULL,
            file_path TEXT NOT NULL,
            file_url TEXT NOT NULL,
            file_type TEXT NOT NULL,
            file_size INTEGER NOT NULL DEFAULT 0,
            width INTEGER NULL,
            height INTEGER NULL,
            folder TEXT NOT NULL DEFAULT 'general',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS landing_page_visitors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            landing_page_id INTEGER NOT NULL,
            short_link_id INTEGER NULL,
            ip_hash TEXT NOT NULL,
            user_agent TEXT NULL,
            device_type TEXT NOT NULL DEFAULT 'desktop',
            browser TEXT NULL,
            os TEXT NULL,
            country TEXT NOT NULL DEFAULT 'XX',
            referer TEXT NULL,
            utm_source TEXT NULL,
            utm_medium TEXT NULL,
            utm_campaign TEXT NULL,
            utm_content TEXT NULL,
            utm_term TEXT NULL,
            subid TEXT NULL,
            clickid TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS offer_clicks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            offer_id INTEGER NOT NULL,
            landing_page_id INTEGER NULL,
            short_link_id INTEGER NULL,
            click_id TEXT NOT NULL UNIQUE,
            subid TEXT NULL,
            ip_hash TEXT NOT NULL,
            user_agent TEXT NULL,
            device_type TEXT NOT NULL DEFAULT 'desktop',
            country TEXT NOT NULL DEFAULT 'XX',
            referer TEXT NULL,
            target_url_resolved TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS conversions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            click_id TEXT NOT NULL,
            offer_id INTEGER NULL,
            landing_page_id INTEGER NULL,
            transaction_id TEXT NOT NULL UNIQUE,
            payout REAL NOT NULL DEFAULT 0.0,
            currency TEXT NOT NULL DEFAULT 'USD',
            status TEXT NOT NULL DEFAULT 'approved',
            ip_address TEXT NULL,
            converted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS postback_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            request_method TEXT NOT NULL,
            request_uri TEXT NOT NULL,
            request_body TEXT NULL,
            query_params TEXT NULL,
            response_status INTEGER NOT NULL DEFAULT 200,
            response_message TEXT NULL,
            ip_address TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NULL,
            action TEXT NOT NULL,
            entity_type TEXT NOT NULL,
            entity_id INTEGER NULL,
            old_value TEXT NULL,
            new_value TEXT NULL,
            ip_address TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        // Legacy compatibility
        "CREATE TABLE IF NOT EXISTS clicks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            campaign_id INTEGER NOT NULL,
            ip_hash TEXT NOT NULL,
            user_agent TEXT NULL,
            device_type TEXT NOT NULL DEFAULT 'desktop',
            browser TEXT NULL,
            os TEXT NULL,
            country TEXT NOT NULL DEFAULT 'XX',
            referer TEXT NULL,
            subid TEXT NULL,
            clickid TEXT NULL,
            utm_source TEXT NULL,
            utm_medium TEXT NULL,
            utm_campaign TEXT NULL,
            utm_content TEXT NULL,
            utm_term TEXT NULL,
            query_params TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS pageviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_hash TEXT NOT NULL,
            campaign_code TEXT NULL,
            device_type TEXT NOT NULL DEFAULT 'desktop',
            country TEXT NOT NULL DEFAULT 'XX',
            referer TEXT NULL,
            utm_source TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT NOT NULL,
            username TEXT NOT NULL,
            success INTEGER NOT NULL DEFAULT 0,
            attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($queries as $sql) {
        $db->exec($sql);
    }
    echo "[*] SQLite tables ready.\n";
}

function runMysqlMigrations(\PDO $db): void
{
    $schemaFile = __DIR__ . '/schema.sql';
    $sql = file_get_contents($schemaFile);
    $db->exec($sql);
    echo "[*] MySQL tables created.\n";
}

if (php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    runMigrations();
}
