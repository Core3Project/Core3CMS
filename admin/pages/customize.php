<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin');

$activeTheme = Setting::get('theme', 'default');
$themeInfo = [];
$jsonPath = C3_ROOT . '/content/themes/' . $activeTheme . '/theme.json';
if (file_exists($jsonPath)) $themeInfo = json_decode(file_get_contents($jsonPath), true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::checkCsrf($_POST['_csrf'] ?? '')) {
    $tab = $_POST['tab'] ?? 'identity';

    if ($tab === 'identity') {
        // Handle logo upload
        if (!empty($_FILES['logo_file']['name'])) {
            $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp'];
            $type = $_FILES['logo_file']['type'];
            if (in_array($type, $allowed)) {
                $ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
                $fname = 'custom-logo-' . time() . '.' . $ext;
                $dest = C3_ROOT . '/content/uploads/' . $fname;
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $dest)) {
                    // Remove old custom logo
                    $old = Setting::get('custom_logo', '');
                    if ($old && file_exists(C3_ROOT . '/content/uploads/' . $old)) {
                        unlink(C3_ROOT . '/content/uploads/' . $old);
                    }
                    Setting::set('custom_logo', $fname);
                }
            } else {
                flash('error', 'Invalid image type. Use PNG, JPG, GIF, SVG, or WebP.');
                header('Location: ' . adm('customize')); exit;
            }
        }
        // Remove logo
        if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
            $old = Setting::get('custom_logo', '');
            if ($old && file_exists(C3_ROOT . '/content/uploads/' . $old)) {
                unlink(C3_ROOT . '/content/uploads/' . $old);
            }
            Setting::set('custom_logo', '');
        }
        // Handle favicon upload
        if (!empty($_FILES['favicon_file']['name'])) {
            $favAllowed = ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml', 'image/gif'];
            $favType = $_FILES['favicon_file']['type'];
            $favExt = strtolower(pathinfo($_FILES['favicon_file']['name'], PATHINFO_EXTENSION));
            if (in_array($favType, $favAllowed) || in_array($favExt, ['ico', 'png', 'svg', 'gif'])) {
                $favName = 'favicon-' . time() . '.' . $favExt;
                $favDest = C3_ROOT . '/content/uploads/' . $favName;
                if (move_uploaded_file($_FILES['favicon_file']['tmp_name'], $favDest)) {
                    $old = Setting::get('custom_favicon', '');
                    if ($old && file_exists(C3_ROOT . '/content/uploads/' . $old)) {
                        unlink(C3_ROOT . '/content/uploads/' . $old);
                    }
                    Setting::set('custom_favicon', $favName);
                }
            } else {
                flash('error', 'Invalid favicon type. Use ICO, PNG, SVG, or GIF.');
                header('Location: ' . adm('customize')); exit;
            }
        }
        if (isset($_POST['remove_favicon']) && $_POST['remove_favicon'] === '1') {
            $old = Setting::get('custom_favicon', '');
            if ($old && file_exists(C3_ROOT . '/content/uploads/' . $old)) {
                unlink(C3_ROOT . '/content/uploads/' . $old);
            }
            Setting::set('custom_favicon', '');
        }
        Setting::set('site_tagline', trim($_POST['site_tagline'] ?? ''));
    }

    if ($tab === 'colors') {
        $accent = trim($_POST['accent_color'] ?? '');
        // Validate it looks like a hex color
        if ($accent && !preg_match('/^#[0-9a-fA-F]{3,8}$/', $accent)) {
            $accent = '';
        }
        Setting::set('accent_color', $accent);
    }

    if ($tab === 'css') {
        Setting::set('custom_css', trim($_POST['custom_css'] ?? ''));
    }

    // Bump cache version so changes show immediately
    $cv = (int) Setting::get('cache_version', '0') + 1;
    Setting::set('cache_version', (string) $cv);

    flash('success', 'Appearance updated.');
    header('Location: ' . adm('customize?tab=' . $tab));
    exit;
}

$tab = $_GET['tab'] ?? 'identity';
$customLogo = Setting::get('custom_logo', '');
$customFavicon = Setting::get('custom_favicon', '');
$accentColor = Setting::get('accent_color', '');
$customCss = Setting::get('custom_css', '');
$tagline = Setting::get('site_tagline', '');

adm_header('Customize');
?>
<div class="action-bar">
    <h1>Customize — <?= e($themeInfo['name'] ?? ucfirst($activeTheme)) ?></h1>
    <a href="<?= adm('themes') ?>" class="btn btn-outline btn-sm">← Themes</a>
</div>

<div class="tabs">
    <a href="<?= adm('customize?tab=identity') ?>" class="<?= $tab === 'identity' ? 'active' : '' ?>">Site Identity</a>
    <a href="<?= adm('customize?tab=colors') ?>" class="<?= $tab === 'colors' ? 'active' : '' ?>">Colors</a>
    <a href="<?= adm('customize?tab=css') ?>" class="<?= $tab === 'css' ? 'active' : '' ?>">Additional CSS</a>
</div>

<?php if ($tab === 'identity'): ?>
<div class="adm-grid">
<div>
<form method="post" enctype="multipart/form-data">
<?= Auth::csrfField() ?>
<input type="hidden" name="tab" value="identity">
<div class="panel"><div class="panel-hd">Logo</div><div class="panel-bd">
    <?php if ($customLogo): ?>
    <div style="margin-bottom:16px;padding:16px;background:#f6f7f7;border-radius:var(--radius);text-align:center">
        <img src="<?= url('content/uploads/' . e($customLogo)) ?>" alt="Current logo" style="max-height:60px;max-width:100%">
    </div>
    <div class="check-row" style="margin-bottom:12px">
        <input type="checkbox" name="remove_logo" value="1" id="rm-logo">
        <label for="rm-logo">Remove current logo (revert to default)</label>
    </div>
    <?php else: ?>
    <div style="margin-bottom:16px;padding:16px;background:#f6f7f7;border-radius:var(--radius);text-align:center">
        <img src="<?= url('assets/images/logo.svg') ?>" alt="Default logo" style="max-height:60px;max-width:100%">
        <div style="font-size:12px;color:var(--muted);margin-top:6px">Default logo</div>
    </div>
    <?php endif; ?>
    <div class="fg">
        <label>Upload New Logo</label>
        <input type="file" name="logo_file" accept="image/*,.svg" class="fc" style="padding:6px 8px;height:auto">
        <p class="hint">Recommended: SVG or PNG. Max height displayed: 30px on site, 28px in admin.</p>
    </div>
</div></div>

<div class="panel"><div class="panel-hd">Tagline</div><div class="panel-bd">
    <div class="fg">
        <label>Site Tagline</label>
        <input type="text" name="site_tagline" class="fc" value="<?= e($tagline) ?>" placeholder="A short description of your site">
        <p class="hint">Displayed next to your logo in the header.</p>
    </div>
</div></div>

<div class="panel"><div class="panel-hd">Favicon</div><div class="panel-bd">
    <?php if ($customFavicon): ?>
    <div style="margin-bottom:12px;display:flex;align-items:center;gap:12px">
        <img src="<?= url('content/uploads/' . e($customFavicon)) ?>" alt="Current favicon" style="width:32px;height:32px;border:1px solid var(--border,#c3c4c7);border-radius:2px">
        <span style="font-size:13px;color:var(--muted)"><?= e($customFavicon) ?></span>
    </div>
    <div class="check-row" style="margin-bottom:12px">
        <input type="checkbox" name="remove_favicon" value="1" id="rm-fav">
        <label for="rm-fav">Remove favicon (revert to browser default)</label>
    </div>
    <?php else: ?>
    <p style="font-size:13px;color:var(--muted);margin-bottom:12px">No custom favicon set. Browsers will use their default icon.</p>
    <?php endif; ?>
    <div class="fg">
        <label>Upload Favicon</label>
        <input type="file" name="favicon_file" accept=".ico,.png,.svg,.gif,image/x-icon,image/png,image/svg+xml" class="fc" style="padding:6px 8px;height:auto">
        <p class="hint">Recommended: 32x32px PNG or ICO file. SVG also works in modern browsers.</p>
    </div>
</div></div>

<button type="submit" class="btn btn-primary">Save Changes</button>
</form>
</div>
<div>
    <div class="panel"><div class="panel-hd">Preview</div><div class="panel-bd" style="text-align:center;padding:24px">
        <div style="background:#f6f7f7;border-radius:var(--radius);padding:20px;display:inline-flex;align-items:center;gap:12px">
            <img src="<?= $customLogo ? url('content/uploads/' . e($customLogo)) : url('assets/images/logo.svg') ?>" alt="" style="max-height:30px">
            <?php if ($tagline): ?><span style="font-size:13px;color:#6b7280;padding-left:12px;border-left:1px solid #e5e7eb"><?= e($tagline) ?></span><?php endif; ?>
        </div>
        <div style="font-size:11px;color:var(--muted);margin-top:10px">Light background preview</div>
        <div style="background:#1d2327;border-radius:var(--radius);padding:20px;display:inline-flex;align-items:center;gap:12px;margin-top:12px">
            <img src="<?= $customLogo ? url('content/uploads/' . e($customLogo)) : url('assets/images/logo-light.svg') ?>" alt="" style="max-height:30px">
            <?php if ($tagline): ?><span style="font-size:13px;color:#a1a1aa;padding-left:12px;border-left:1px solid #444"><?= e($tagline) ?></span><?php endif; ?>
        </div>
        <div style="font-size:11px;color:var(--muted);margin-top:10px">Dark background preview</div>
    </div></div>
</div>
</div>

<?php elseif ($tab === 'colors'): ?>
<form method="post">
<?= Auth::csrfField() ?>
<input type="hidden" name="tab" value="colors">
<div class="panel" style="max-width:600px"><div class="panel-hd">Theme Colors</div><div class="panel-bd">
    <div class="fg">
        <label>Accent Color</label>
        <div style="display:flex;gap:10px;align-items:center">
            <input type="color" name="accent_color" value="<?= e($accentColor ?: '#b72626') ?>" style="width:50px;height:36px;border:1px solid #8c8f94;border-radius:var(--radius);padding:2px;cursor:pointer">
            <input type="text" name="accent_color" class="fc" value="<?= e($accentColor) ?>" placeholder="#b72626" style="max-width:140px">
        </div>
        <p class="hint">Overrides the theme's default accent color (links, buttons, highlights). Leave empty to use theme default.</p>
    </div>
    <div style="margin-top:12px;padding:12px;background:#f6f7f7;border-radius:var(--radius)">
        <span style="font-size:12px;color:var(--muted)">Preview:</span>
        <a href="#" style="color:<?= e($accentColor ?: '#b72626') ?>;font-weight:600;margin-left:8px" onclick="return false">Sample Link</a>
        <button style="background:<?= e($accentColor ?: '#b72626') ?>;color:#fff;border:none;padding:4px 12px;border-radius:4px;font-size:12px;margin-left:8px;cursor:default">Button</button>
    </div>
</div></div>
<button type="submit" class="btn btn-primary">Save Changes</button>
</form>

<?php elseif ($tab === 'css'): ?>
<form method="post">
<?= Auth::csrfField() ?>
<input type="hidden" name="tab" value="css">
<div class="panel" style="max-width:800px"><div class="panel-hd">Additional CSS</div><div class="panel-bd">
    <div class="fg">
        <label>Custom CSS</label>
        <textarea name="custom_css" class="fc" style="min-height:300px;font-family:var(--mono);font-size:13px;tab-size:2"><?= e($customCss) ?></textarea>
        <p class="hint">Add your own CSS to customize the appearance. This will be loaded after the theme stylesheet.</p>
    </div>
</div></div>
<button type="submit" class="btn btn-primary">Publish</button>
</form>
<?php endif; ?>

<?php adm_footer(); ?>
