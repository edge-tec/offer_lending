<?php
declare(strict_types=1);

/**
 * Enterprise Admin Navigation Header
 */

use App\Helpers;

/** @var \App\Auth $auth */
/** @var string $currentPage */
/** @var string $pageTitle */

$user = $auth->user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($pageTitle ?? 'Admin Dashboard') ?> | Enterprise Affiliate Platform</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../assets/css/admin.css') ?>">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        .nav-section-title {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            padding: 0.75rem 1rem 0.25rem 1rem;
            font-weight: 800;
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <!-- Mobile sidebar drawer backdrop -->
    <div id="adminSidebarBackdrop" class="admin-sidebar-backdrop"></div>

    <!-- Enterprise Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="/admin/" class="admin-brand">
                <div class="brand-icon-mini">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </div>
                <span>Affiliate<strong>Hub</strong></span>
            </a>
            <button type="button" id="adminSidebarClose" class="sidebar-close-btn" aria-label="Close navigation menu">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <ul class="admin-nav">
            <li class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <a href="/admin/">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <span>Dashboard</span>
                </a>
            </li>

            <div class="nav-section-title">Landing Pages & Offers</div>

            <li class="nav-item <?= ($currentPage ?? '') === 'page-builder' ? 'active' : '' ?>">
                <a href="/admin/page-edit.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>+ New Landing Page</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentPage ?? '') === 'pages' ? 'active' : '' ?>">
                <a href="/admin/pages.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                    <span>Manage Pages (9)</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentPage ?? '') === 'offers' ? 'active' : '' ?>">
                <a href="/admin/offers.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    <span>Offer Management</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentPage ?? '') === 'short-links' ? 'active' : '' ?>">
                <a href="/admin/short-links.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                    <span>Short Link Generator</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentPage ?? '') === 'media' ? 'active' : '' ?>">
                <a href="/admin/media.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    <span>Media Library</span>
                </a>
            </li>

            <div class="nav-section-title">Tracking & Postbacks</div>

            <li class="nav-item <?= ($currentPage ?? '') === 'campaigns' ? 'active' : '' ?>">
                <a href="/admin/campaigns.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                    <span>Campaign Tracking</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentPage ?? '') === 'clicks' ? 'active' : '' ?>">
                <a href="/admin/clicks.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                    <span>Click Logs</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentPage ?? '') === 'postback' ? 'active' : '' ?>">
                <a href="/admin/postback.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    <span>Postback Conversions</span>
                </a>
            </li>

            <div class="nav-section-title">Administration</div>

            <li class="nav-item <?= ($currentPage ?? '') === 'domains' ? 'active' : '' ?>">
                <a href="/admin/domains.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    <span>Domains</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentPage ?? '') === 'users' ? 'active' : '' ?>">
                <a href="/admin/users.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>User Roles</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentPage ?? '') === 'settings' ? 'active' : '' ?>">
                <a href="/admin/settings.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span>System Settings</span>
                </a>
            </li>

            <li class="nav-item <?= ($currentPage ?? '') === 'audit' ? 'active' : '' ?>">
                <a href="/admin/audit.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                    <span>Audit Trail</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="/" target="_blank" class="btn btn-secondary btn-sm" style="width: 100%; margin-bottom: 0.75rem;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                <span>Live Landing Pages</span>
            </a>
            <a href="/admin/logout.php" class="btn btn-danger btn-sm" style="width: 100%;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button type="button" id="adminSidebarToggle" class="btn-sidebar-toggle" aria-label="Open navigation menu">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <div class="mobile-topbar-brand">
                    <div class="brand-icon-mini">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    </div>
                    <span>Affiliate<strong>Hub</strong></span>
                </div>
                <h1 class="topbar-title"><?= Helpers::e($pageTitle ?? 'Dashboard') ?></h1>
            </div>
            <div class="topbar-actions">
                <div class="user-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span><?= Helpers::e($user['username'] ?? 'Admin') ?> (<?= Helpers::e($user['role'] ?? 'admin') ?>)</span>
                </div>
            </div>
        </header>

        <main class="admin-content">
