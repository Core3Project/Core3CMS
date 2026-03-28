<?php
$_siteName = Setting::get('site_name', 'Core 3 CMS');
$_tagline = Setting::get('site_tagline', '');
$_navPages = DB::rows("SELECT title,slug FROM " . DB::t('pages') . " WHERE status='published' AND show_in_nav=1 ORDER BY sort_order,title LIMIT 6");
$_customLogo = Setting::get('custom_logo', '');
// Dark theme uses logo-light.svg by default (readable on dark bg)
$_logoUrl = $_customLogo ? url('content/uploads/' . $_customLogo) : url('assets/images/logo-light.svg');
$_accentColor = Setting::get('accent_color', '');
$_customCss = Setting::get('custom_css', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= e($_siteName) ?></title>
<?php if ($md = Setting::get('meta_description')): ?><meta name="description" content="<?= e($md) ?>"><?php endif; ?>
<link rel="alternate" type="application/rss+xml" title="<?= e($_siteName) ?>" href="<?= url('feed') ?>">
<?php $_favicon = Setting::get('custom_favicon', ''); if ($_favicon): ?>
<link rel="icon" href="<?= url('content/uploads/' . e($_favicon)) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= Theme::asset('style.css') ?>?v=<?= e(Setting::get('cache_version', '0')) ?>">
<?php if ($_accentColor || $_customCss): ?>
<style>
<?php if ($_accentColor): ?>:root{--red:<?= e($_accentColor) ?>;--red-hover:<?= e($_accentColor) ?>}<?php endif; ?>
<?= $_customCss ?>
</style>
<?php endif; ?>
<?= Modules::html('head') ?>
</head>
<body>
<header class="site-header">
<div class="inner">
    <div class="header-left">
        <a href="<?= url() ?>" class="site-logo">
            <img src="<?= e($_logoUrl) ?>" alt="<?= e($_siteName) ?>">
        </a>
        <?php if ($_tagline): ?><span class="tagline"><?= e($_tagline) ?></span><?php endif; ?>
    </div>
    <div style="display:flex;align-items:center;gap:4px">
        <button class="nav-toggle" onclick="document.querySelector('.site-nav').classList.toggle('open')" aria-label="Menu">&#9776;</button>
        <nav class="site-nav">
            <a href="<?= url() ?>">Home</a>
            <?php foreach ($_navPages as $np): ?>
            <a href="<?= url('page/' . $np['slug']) ?>"><?= e($np['title']) ?></a>
            <?php endforeach; ?>
            <?php if (Setting::get('registration_enabled', '0') === '1' && !Auth::check()): ?>
            <a href="<?= url('register') ?>">Register</a>
            <?php endif; ?>
            <?php if (Auth::check()): ?>
            <a href="<?= url('admin') ?>">Dashboard</a>
            <?php endif; ?>
        </nav>
    </div>
</div>
</header>
