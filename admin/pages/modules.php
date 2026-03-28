<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin');

// Toggle module
if (isset($_GET['toggle'], $_GET['t']) && Auth::checkCsrf($_GET['t'])) {
    $slug = preg_replace('/[^a-z0-9_-]/', '', $_GET['toggle']);
    $key = "module_{$slug}";
    $wasActive = Setting::get($key, '0') === '1';
    $nowActive = !$wasActive;
    Setting::set($key, $nowActive ? '1' : '0');

    if ($nowActive) {
        // Check if module has settings that need configuring
        $jsonPath = C3_ROOT . '/content/modules/' . $slug . '/module.json';
        if (file_exists($jsonPath)) {
            $info = json_decode(file_get_contents($jsonPath), true);
            if (!empty($info['settings'])) {
                // Check if any required settings are empty
                $needsSetup = false;
                foreach ($info['settings'] as $field) {
                    if (!empty($field['required']) && Setting::get($field['key'], '') === '') {
                        $needsSetup = true;
                        break;
                    }
                }
                if ($needsSetup) {
                    flash('info', 'Module activated! Please configure it below.');
                    header('Location: ' . adm('module-settings?module=' . $slug));
                    exit;
                }
            }
        }
        flash('success', 'Module activated.');
    } else {
        flash('success', 'Module deactivated.');
    }
    header('Location: ' . adm('modules')); exit;
}

// Install from ZIP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['module_zip']['name']) && Auth::checkCsrf($_POST['_csrf'] ?? '')) {
    $result = Modules::installZip($_FILES['module_zip']['tmp_name']);
    flash($result === true ? 'success' : 'error', $result === true ? 'Module installed.' : $result);
    header('Location: ' . adm('modules')); exit;
}

// Get all modules with their settings info
$mods = Modules::all();
foreach ($mods as &$m) {
    $jsonPath = C3_ROOT . '/content/modules/' . $m['slug'] . '/module.json';
    $info = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];
    $m['has_settings'] = !empty($info['settings']);
}
unset($m);

adm_header('Modules');
$csrf = Auth::csrf();
?>
<div class="action-bar"><h2>Modules</h2></div>
<div class="adm-grid"><div>
<div class="panel"><table class="tbl"><thead><tr><th>Module</th><th>Description</th><th>Version</th><th>Status</th><th></th></tr></thead><tbody>
<?php if (!$mods): ?><tr><td colspan="5" style="text-align:center;color:var(--m);padding:24px">No modules installed.</td></tr>
<?php else: foreach ($mods as $m): ?>
<tr>
<td>
    <strong><?= e(isset($m['name']) ? $m['name'] : $m['slug']) ?></strong>
    <?php if ($m['active'] && $m['has_settings']): ?>
        <br><a href="<?= adm('module-settings?module=' . e($m['slug'])) ?>" style="font-size:11px">⚙ Settings</a>
    <?php endif; ?>
</td>
<td style="font-size:12px;color:var(--m)"><?= e(isset($m['description']) ? $m['description'] : '') ?></td>
<td><?= e(isset($m['version']) ? $m['version'] : '?') ?></td>
<td><span class="badge badge-<?= $m['active'] ? 'active' : 'inactive' ?>"><?= $m['active'] ? 'Active' : 'Inactive' ?></span></td>
<td class="acts">
    <a href="<?= adm('modules?toggle=' . e($m['slug']) . '&t=' . $csrf) ?>"><?= $m['active'] ? 'Deactivate' : 'Activate' ?></a>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
</div>
<div>
<div class="panel"><div class="panel-hd">Install Module</div><div class="panel-bd">
<form method="post" enctype="multipart/form-data"><?= Auth::csrfField() ?>
<p style="font-size:12px;color:var(--m);margin-bottom:10px">Upload a .zip containing a module.json</p>
<input type="file" name="module_zip" accept=".zip" class="fc" required>
<button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px">Install</button>
</form></div></div>
</div></div>
<?php adm_footer(); ?>
