<?php
declare(strict_types=1);

/**
 * Enterprise Multi-Offer Dating Platform Router
 * Usage: php -S localhost:8000 router.php
 */

require_once __DIR__ . '/bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 1. Static Assets Handler (CSS, JS, Images, Fonts, Uploads)
$publicFile = __DIR__ . '/public' . $uri;
$ext = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));

if ($uri !== '/' && $ext !== 'php' && file_exists($publicFile) && !is_dir($publicFile)) {
    $mimes = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'xml' => 'application/xml',
        'txt' => 'text/plain; charset=UTF-8',
        'json' => 'application/json',
    ];
    if (isset($mimes[$ext]) && !headers_sent()) {
        header('Content-Type: ' . $mimes[$ext]);
    }
    readfile($publicFile);
    return true;
}

// 2. S2S Postback Endpoints
if ($uri === '/postback' || $uri === '/postback/' || $uri === '/api/postback' || $uri === '/api/postback/') {
    require __DIR__ . '/public/api/postback.php';
    return true;
}

// 3. Centralized Offer Redirect Gateway (/click)
if ($uri === '/click' || $uri === '/click/') {
    require __DIR__ . '/public/click.php';
    return true;
}

// 4. Short Tracking Links (/go/{short_code}) & Legacy /go
if ($uri === '/go' || $uri === '/go/') {
    require __DIR__ . '/public/go.php';
    return true;
}

if (str_starts_with($uri, '/go/')) {
    $shortCode = strtolower(trim(substr($uri, 4), '/'));
    $shortLinkService = new \App\ShortLink();
    $shortLink = $shortLinkService->resolve($shortCode);

    if ($shortLink) {
        $shortLinkService->recordClick((int)$shortLink['id']);
        
        // Load the assigned landing page
        $lpService = new \App\LandingPage();
        $landingPage = $lpService->getById((int)$shortLink['landing_page_id']);

        if ($landingPage && $landingPage['status'] === 'published') {
            $tracker = new \App\Tracker();
            $tracker->recordLandingPageVisit((int)$landingPage['id'], (int)$shortLink['id'], $_GET);

            $offers = $lpService->getOffers((int)$landingPage['id']);
            $sections = $lpService->getSections((int)$landingPage['id']);
            require __DIR__ . '/public/render-page.php';
            return true;
        }
    }

    // If short link not found, fallback to standard campaign redirect
    require __DIR__ . '/public/go.php';
    return true;
}

// 5. Track View Beacon
if ($uri === '/track-view' || $uri === '/track-view/') {
    require __DIR__ . '/public/track-view.php';
    return true;
}

// 6. Robots & Sitemap
if ($uri === '/robots.txt') {
    header('Content-Type: text/plain; charset=UTF-8');
    require __DIR__ . '/public/robots.txt';
    return true;
}
if ($uri === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=UTF-8');
    require __DIR__ . '/public/sitemap.xml';
    return true;
}

// 7. Admin Portal
if (str_starts_with($uri, '/admin')) {
    if (is_dir($publicFile)) {
        $publicFile = rtrim($publicFile, '/') . '/index.php';
    }
    if (file_exists($publicFile)) {
        require $publicFile;
        return true;
    }
}

// 8. Explicit PHP Files in public/
if ($ext === 'php' && file_exists($publicFile)) {
    require $publicFile;
    return true;
}
if ($ext === '' && file_exists($publicFile . '.php')) {
    require $publicFile . '.php';
    return true;
}

// 9. Dynamic Landing Pages by Slug (e.g. /friends-with-benefits, /instabang, /zoomflirts, /cindymatches)
$slug = trim($uri, '/');
if ($slug !== '') {
    $lpService = new \App\LandingPage();
    $landingPage = $lpService->getBySlug($slug);

    if ($landingPage && ($landingPage['status'] === 'published' || isset($_GET['preview']))) {
        $tracker = new \App\Tracker();
        $tracker->recordLandingPageVisit((int)$landingPage['id'], null, $_GET);

        $offers = $lpService->getOffers((int)$landingPage['id']);
        $sections = $lpService->getSections((int)$landingPage['id']);
        require __DIR__ . '/public/render-page.php';
        return true;
    }
}

// 10. Fallback to default homepage
require __DIR__ . '/public/index.php';
return true;
