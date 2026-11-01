-- ========================================================
-- Enterprise Multi-Offer Dating Affiliate Platform Schema
-- MySQL 8.0+ / MariaDB 10.5+
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users & RBAC
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'admin', 'campaign_manager', 'editor', 'viewer') NOT NULL DEFAULT 'admin',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Multi-Domain Management
CREATE TABLE IF NOT EXISTS `domains` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `domain_name` VARCHAR(150) NOT NULL UNIQUE,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `ssl_active` TINYINT(1) NOT NULL DEFAULT 1,
    `notes` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Landing Page Templates
CREATE TABLE IF NOT EXISTS `landing_page_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `color_palette` VARCHAR(50) NOT NULL DEFAULT 'dark-rose',
    `font_family` VARCHAR(50) NOT NULL DEFAULT 'Plus Jakarta Sans',
    `default_sections_json` MEDIUMTEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Centralized Offers
CREATE TABLE IF NOT EXISTS `offers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `offer_code` VARCHAR(50) NOT NULL UNIQUE,
    `destination_website` VARCHAR(255) NOT NULL,
    `affiliate_tracking_url` VARCHAR(2048) NOT NULL,
    `description` TEXT NULL,
    `image_url` VARCHAR(255) NULL,
    `cta_text` VARCHAR(50) NOT NULL DEFAULT 'Explore Now',
    `geo_targets` VARCHAR(255) NULL DEFAULT 'ALL',
    `device_targets` VARCHAR(50) NOT NULL DEFAULT 'ALL',
    `priority` INT NOT NULL DEFAULT 10,
    `status` ENUM('active', 'inactive', 'paused') NOT NULL DEFAULT 'active',
    `total_clicks` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `start_date` DATE NULL,
    `expiration_date` DATE NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_offers_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Campaigns
CREATE TABLE IF NOT EXISTS `campaigns` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `campaign_code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `target_url` VARCHAR(2048) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Unlimited Landing Pages
CREATE TABLE IF NOT EXISTS `landing_pages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `domain_id` INT UNSIGNED NULL,
    `campaign_id` INT UNSIGNED NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `target_dating_website` VARCHAR(255) NULL,
    `template_id` INT UNSIGNED NULL,
    `status` ENUM('published', 'draft', 'paused') NOT NULL DEFAULT 'published',
    `page_title` VARCHAR(200) NOT NULL,
    `meta_description` TEXT NULL,
    `hero_headline` VARCHAR(200) NOT NULL,
    `hero_subtitle` TEXT NULL,
    `hero_cta_text` VARCHAR(50) NOT NULL DEFAULT 'EXPLORE NOW',
    `hero_image` VARCHAR(255) NULL,
    `badge_text` VARCHAR(100) NULL DEFAULT '18+ Adults Only',
    `theme_color_primary` VARCHAR(20) NOT NULL DEFAULT '#f43f5e',
    `theme_color_accent` VARCHAR(20) NOT NULL DEFAULT '#8b5cf6',
    `theme_mode` VARCHAR(20) NOT NULL DEFAULT 'dark',
    `custom_css` TEXT NULL,
    `custom_header_scripts` TEXT NULL,
    `custom_footer_scripts` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_lp_slug` (`slug`),
    INDEX `idx_lp_status` (`status`),
    CONSTRAINT `fk_lp_domain` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_lp_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_lp_template` FOREIGN KEY (`template_id`) REFERENCES `landing_page_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Landing Page Sections (Dynamic Visual Content Builder)
CREATE TABLE IF NOT EXISTS `landing_page_sections` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `landing_page_id` INT UNSIGNED NOT NULL,
    `section_type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(200) NULL,
    `subtitle` TEXT NULL,
    `content_json` MEDIUMTEXT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
    INDEX `idx_lps_page` (`landing_page_id`),
    CONSTRAINT `fk_lps_page` FOREIGN KEY (`landing_page_id`) REFERENCES `landing_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Landing Page Offer Associations (Multiple Offers per Page)
CREATE TABLE IF NOT EXISTS `landing_page_offers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `landing_page_id` INT UNSIGNED NOT NULL,
    `offer_id` INT UNSIGNED NOT NULL,
    `display_order` INT NOT NULL DEFAULT 0,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `custom_cta_text` VARCHAR(50) NULL,
    `custom_badge` VARCHAR(50) NULL,
    UNIQUE KEY `uk_lp_offer` (`landing_page_id`, `offer_id`),
    CONSTRAINT `fk_lpo_lp` FOREIGN KEY (`landing_page_id`) REFERENCES `landing_pages` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lpo_offer` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Unique Short Tracking Links
CREATE TABLE IF NOT EXISTS `short_links` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `short_code` VARCHAR(50) NOT NULL UNIQUE,
    `landing_page_id` INT UNSIGNED NOT NULL,
    `campaign_id` INT UNSIGNED NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `clicks_count` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sl_code` (`short_code`),
    CONSTRAINT `fk_sl_lp` FOREIGN KEY (`landing_page_id`) REFERENCES `landing_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Secondary/Alternative Tracking URLs for Offers
CREATE TABLE IF NOT EXISTS `affiliate_tracking_urls` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `offer_id` INT UNSIGNED NOT NULL,
    `label` VARCHAR(100) NOT NULL,
    `url` VARCHAR(2048) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `notes` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_atu_offer` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Media Library
CREATE TABLE IF NOT EXISTS `media_library` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `file_name` VARCHAR(150) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_url` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(50) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
    `width` INT UNSIGNED NULL,
    `height` INT UNSIGNED NULL,
    `folder` VARCHAR(50) NOT NULL DEFAULT 'general',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Landing Page Visitors
CREATE TABLE IF NOT EXISTS `landing_page_visitors` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `landing_page_id` INT UNSIGNED NOT NULL,
    `short_link_id` INT UNSIGNED NULL,
    `ip_hash` VARCHAR(64) NOT NULL,
    `user_agent` VARCHAR(500) NULL,
    `device_type` VARCHAR(20) NOT NULL DEFAULT 'desktop',
    `browser` VARCHAR(50) NULL,
    `os` VARCHAR(50) NULL,
    `country` VARCHAR(5) NOT NULL DEFAULT 'XX',
    `referer` VARCHAR(1000) NULL,
    `utm_source` VARCHAR(100) NULL,
    `utm_medium` VARCHAR(100) NULL,
    `utm_campaign` VARCHAR(100) NULL,
    `utm_content` VARCHAR(100) NULL,
    `utm_term` VARCHAR(100) NULL,
    `subid` VARCHAR(100) NULL,
    `clickid` VARCHAR(100) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_lpv_page` (`landing_page_id`),
    INDEX `idx_lpv_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Offer Clicks (CTA Outgoing Redirects)
CREATE TABLE IF NOT EXISTS `offer_clicks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `offer_id` INT UNSIGNED NOT NULL,
    `landing_page_id` INT UNSIGNED NULL,
    `short_link_id` INT UNSIGNED NULL,
    `click_id` VARCHAR(100) NOT NULL UNIQUE,
    `subid` VARCHAR(100) NULL,
    `ip_hash` VARCHAR(64) NOT NULL,
    `user_agent` VARCHAR(500) NULL,
    `device_type` VARCHAR(20) NOT NULL DEFAULT 'desktop',
    `country` VARCHAR(5) NOT NULL DEFAULT 'XX',
    `referer` VARCHAR(1000) NULL,
    `target_url_resolved` VARCHAR(2048) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_oc_offer` (`offer_id`),
    INDEX `idx_oc_page` (`landing_page_id`),
    INDEX `idx_oc_click_id` (`click_id`),
    INDEX `idx_oc_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Conversions (Postback Verified)
CREATE TABLE IF NOT EXISTS `conversions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `click_id` VARCHAR(100) NOT NULL,
    `offer_id` INT UNSIGNED NULL,
    `landing_page_id` INT UNSIGNED NULL,
    `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
    `payout` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
    `status` VARCHAR(50) NOT NULL DEFAULT 'approved',
    `ip_address` VARCHAR(45) NULL,
    `converted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_conv_click` (`click_id`),
    INDEX `idx_conv_offer` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Postback Raw Logs
CREATE TABLE IF NOT EXISTS `postback_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `request_method` VARCHAR(10) NOT NULL,
    `request_uri` VARCHAR(1000) NOT NULL,
    `request_body` TEXT NULL,
    `query_params` TEXT NULL,
    `response_status` INT NOT NULL DEFAULT 200,
    `response_message` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Audit Logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT UNSIGNED NULL,
    `old_value` TEXT NULL,
    `new_value` TEXT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Settings
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Legacy Compatibility Tables
CREATE TABLE IF NOT EXISTS `clicks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT UNSIGNED NOT NULL,
    `ip_hash` VARCHAR(64) NOT NULL,
    `user_agent` VARCHAR(500) NULL,
    `device_type` VARCHAR(20) NOT NULL DEFAULT 'desktop',
    `browser` VARCHAR(50) NULL,
    `os` VARCHAR(50) NULL,
    `country` VARCHAR(5) NOT NULL DEFAULT 'XX',
    `referer` VARCHAR(1000) NULL,
    `subid` VARCHAR(100) NULL,
    `clickid` VARCHAR(100) NULL,
    `utm_source` VARCHAR(100) NULL,
    `utm_medium` VARCHAR(100) NULL,
    `utm_campaign` VARCHAR(100) NULL,
    `utm_content` VARCHAR(100) NULL,
    `utm_term` VARCHAR(100) NULL,
    `query_params` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pageviews` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_hash` VARCHAR(64) NOT NULL,
    `campaign_code` VARCHAR(50) NULL,
    `device_type` VARCHAR(20) NOT NULL DEFAULT 'desktop',
    `country` VARCHAR(5) NOT NULL DEFAULT 'XX',
    `referer` VARCHAR(1000) NULL,
    `utm_source` VARCHAR(100) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
