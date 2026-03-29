<?php
function adm($path = '') {
    static $base = null;
    if ($base === null) $base = dirname($_SERVER['SCRIPT_NAME']);
    return $base . '/' . ltrim($path, '/');
}

// SVG icon helper - crisp 18px icons matching WordPress admin style
function ico($name) {
    $icons = [
        'dashboard' => '<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/>',
        'posts'     => '<path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
        'pages'     => '<path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
        'categories'=> '<path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>',
        'comments'  => '<path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
        'users'     => '<path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m3-2.803a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'widgets'   => '<path d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>',
        'modules'   => '<path d="M13 10V3L4 14h7v7l9-11h-7z"/>',
        'media'     => '<path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'search'    => '<path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
        'themes'    => '<path d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>',
        'settings'  => '<path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/>',
    ];
    $d = isset($icons[$name]) ? $icons[$name] : '';
    return '<span class="ico"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg></span>';
}

function adm_header($title = '') {
    $me = Auth::user();
    $page = $GLOBALS['_adminPage'] ?? '';
    $pc = DB::count(DB::t('comments'), "status='pending'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $title ? e($title) . ' ‹ ' : '' ?>Core 3 CMS</title>
<link rel="icon" type="image/svg+xml" href="<?= url('assets/images/favicon.svg') ?>">
<link rel="icon" type="image/x-icon" href="<?= url('assets/images/favicon.ico') ?>">
<link rel="stylesheet" href="<?= adm('assets/css/admin.css') ?>?v=<?= e(Setting::get('cache_version', '0')) ?>">
</head>
<body>

<button class="adm-toggle" onclick="document.querySelector('.adm-sidebar').classList.toggle('open')">&#9776;</button>

<aside class="adm-sidebar">
    <a href="<?= adm() ?>" class="logo"><img src="<?= url('assets/images/logo-light.svg') ?>" alt="Core 3 CMS"></a>
    <nav>
        <div class="nav-item"><a href="<?= adm() ?>" class="<?= $page === 'dashboard' ? 'active' : '' ?>"><?= ico('dashboard') ?> Dashboard</a></div>

        <div class="nav-item">
            <a href="<?= adm('posts') ?>" class="<?= strpos($page, 'post') === 0 ? 'active' : '' ?>"><?= ico('posts') ?> Posts</a>
            <div class="sub">
                <a href="<?= adm('posts') ?>">All Posts</a>
                <a href="<?= adm('post-edit') ?>">Add New</a>
            </div>
        </div>

        <div class="nav-item"><a href="<?= adm('media') ?>" class="<?= $page === 'media' ? 'active' : '' ?>"><?= ico('media') ?> Media</a></div>

        <div class="nav-item">
            <a href="<?= adm('pages') ?>" class="<?= strpos($page, 'page') === 0 ? 'active' : '' ?>"><?= ico('pages') ?> Pages</a>
            <div class="sub">
                <a href="<?= adm('pages') ?>">All Pages</a>
                <a href="<?= adm('page-edit') ?>">Add New</a>
            </div>
        </div>

        <div class="nav-item"><a href="<?= adm('categories') ?>" class="<?= $page === 'categories' ? 'active' : '' ?>"><?= ico('categories') ?> Categories</a></div>

        <div class="nav-item">
            <a href="<?= adm('comments') ?>" class="<?= $page === 'comments' ? 'active' : '' ?>"><?= ico('comments') ?> Comments<?php if ($pc): ?><span class="badge-c"><?= $pc ?></span><?php endif; ?></a>
        </div>

        <?php if (Auth::isAdmin()): ?>
        <div class="nav-sep"></div>

        <div class="nav-item"><a href="<?= adm('users') ?>" class="<?= strpos($page, 'user') === 0 ? 'active' : '' ?>"><?= ico('users') ?> Users</a></div>
        <div class="nav-item"><a href="<?= adm('widgets') ?>" class="<?= $page === 'widgets' ? 'active' : '' ?>"><?= ico('widgets') ?> Widgets</a></div>
        <div class="nav-item"><a href="<?= adm('modules') ?>" class="<?= strpos($page, 'module') === 0 ? 'active' : '' ?>"><?= ico('modules') ?> Modules</a></div>
        <div class="nav-item">
            <a href="<?= adm('themes') ?>" class="<?= ($page === 'themes' || $page === 'customize') ? 'active' : '' ?>"><?= ico('themes') ?> Appearance</a>
            <div class="sub">
                <a href="<?= adm('themes') ?>">Themes</a>
                <a href="<?= adm('customize') ?>">Customize</a>
            </div>
        </div>

        <div class="nav-sep"></div>
        <div class="nav-item"><a href="<?= adm('settings') ?>" class="<?= $page === 'settings' ? 'active' : '' ?>"><?= ico('settings') ?> Settings</a></div>
        <?php endif; ?>
    </nav>
</aside>

<div class="adm-topbar">
    <div class="user">
        <?php if (Auth::isAdmin()): ?><a href="<?= adm('clear-cache?t=' . Auth::csrf()) ?>" class="cache-btn" title="Clear all caches">&#x21bb; Clear Cache</a><?php endif; ?>
        <span class="adm-badge <?= e($me['role']) ?>"><?= ucfirst($me['role']) ?></span>
        <?= e($me['display_name'] ?? $me['username']) ?>
        <a href="<?= adm('profile') ?>">Profile</a>
        <a href="<?= url() ?>" target="_blank">Visit Site</a>
        <a href="<?= adm('logout') ?>">Log Out</a>
    </div>
</div>

<div class="adm-content"><div class="adm-wrap">
<?php
    $f = $GLOBALS['_flash'] ?? null;
    if ($f) echo '<div class="alert alert-' . $f['type'] . '">' . e($f['msg']) . '</div>';
}

function adm_footer() { ?>
</div></div>
</body></html>
<?php }
