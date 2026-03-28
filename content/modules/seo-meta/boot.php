<?php
// SEO Meta Module - Adds Open Graph and meta tags
Modules::on('head', function() {
    // Get current page context from Theme data
    $out = '';
    $siteName = Setting::get('site_name', 'Core 3 CMS');
    $suffix = Setting::get('seo_title_suffix', '');
    $defaultImg = Setting::get('seo_default_image', '');
    $desc = Setting::get('site_description', '');
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    // Open Graph basics
    $out .= '<meta property="og:site_name" content="' . e($siteName) . '">' . "\n";
    $out .= '<meta property="og:url" content="' . e($url) . '">' . "\n";
    $out .= '<meta property="og:type" content="article">' . "\n";

    // Twitter card
    $out .= '<meta name="twitter:card" content="summary_large_image">' . "\n";

    // Default OG image
    if ($defaultImg) {
        $out .= '<meta property="og:image" content="' . e($defaultImg) . '">' . "\n";
    }

    // Meta description from site settings (per-post handled by theme)
    if ($desc) {
        $out .= '<meta property="og:description" content="' . e($desc) . '">' . "\n";
    }

    return $out;
});
