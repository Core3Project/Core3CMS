# Core 3 CMS — Hooks Reference

This document lists all hooks available for module developers. Register callbacks with `Modules::on()` and output content with `Modules::html()`.

## How Hooks Work

```php
// Register a callback (in your module's boot.php)
Modules::on('hook_name', function (&$data) {
    // Modify $data or return HTML
}, 10);  // Priority: lower runs first (default: 10)

// Remove a callback
Modules::off('hook_name', $myCallback);

// Check if any callbacks are registered
if (Modules::has('hook_name')) { ... }
```

There are two types of hooks:

- **Data hooks** — fired with `Modules::hook()`. Callbacks receive a reference to data and can modify it.
- **Output hooks** — fired with `Modules::html()`. Callbacks return HTML strings that are concatenated.

---

## Output Hooks

### `head`
Fires inside `<head>` before the closing tag. Use for injecting meta tags, stylesheets, or scripts.

```php
Modules::on('head', function () {
    return '<meta property="og:type" content="article">';
});
```

### `footer`
Fires before `</body>`. Use for analytics scripts, cookie banners, or deferred JS.

```php
Modules::on('footer', function () {
    return '<script src="/my-script.js"></script>';
});
```

### `post_content_after`
Fires after a post's content on the single post page. Receives the `$post` array.

```php
Modules::on('post_content_after', function (&$post) {
    return '<p>Thanks for reading: ' . htmlspecialchars($post['title']) . '</p>';
});
```

### `comments_replace`
Fires instead of the built-in comment section. If any callback returns non-empty HTML, native comments are completely replaced. Receives the `$post` array.

```php
Modules::on('comments_replace', function (&$post) {
    return '<div id="disqus_thread"></div><script>...</script>';
});
```

### `before_comment_form`
Fires above the native comment form (inside the comments section).

### `comment_form_fields`
Fires inside the comment form, before the submit button. Use for adding extra fields (e.g. CAPTCHA).

### `admin_dashboard_before`
Fires at the top of the admin dashboard, above the stats. Use for update notices or alerts.

### `admin_dashboard_after`
Fires in the right sidebar of the admin dashboard, below the system info panel.

---

## Data Hooks

### `routes`
Fires during router initialisation. Receives the routes array by reference. Add custom URL patterns.

```php
Modules::on('routes', function (&$routes) {
    $routes['/my-page'] = [
        'controller' => 'MyController',
        'action'     => 'index',
    ];
});
```

### `comment_validate`
Fires when a comment is submitted, before it is saved. Set `$error` to a string to reject the comment.

```php
Modules::on('comment_validate', function (&$error) {
    if (!verify_captcha()) {
        $error = 'CAPTCHA verification failed.';
    }
});
```

---

## Module Structure

A module lives in `content/modules/your-module/` and requires:

```
your-module/
├── module.json    # Required: metadata and settings
└── boot.php       # Required: runs when the module is active
```

### module.json

```json
{
    "name": "My Module",
    "version": "1.0.0",
    "description": "What it does.",
    "author": "Your Name",
    "default_enabled": "0",
    "settings": [
        {
            "key": "my_setting",
            "label": "API Key",
            "type": "text",
            "required": true,
            "placeholder": "Enter your key",
            "hint": "Get this from your dashboard."
        }
    ]
}
```

**Setting field types:** `text`, `password`, `textarea`, `select`, `checkbox`

Settings are stored in the `c3_settings` table and accessed with:

```php
$value = Setting::get('my_setting', 'default');
```

### Activation flow

When a module with `required` settings is activated, the admin is redirected to the settings page automatically. The settings form is generated from `module.json` — no admin PHP needed.

---

## Theme Structure

```
your-theme/
├── theme.json
├── assets/
│   └── style.css
└── templates/           # Optional — falls back to default theme
    ├── partials/
    │   ├── header.php
    │   ├── footer.php
    │   └── sidebar.php
    ├── blog/
    │   ├── index.php
    │   ├── single.php
    │   └── category.php
    ├── page.php
    ├── 404.php
    └── ...
```

Themes only need to include the files they want to override. Any missing template falls back to the `default` theme.

---

## Database Migrations

When upgrading Core 3, database changes are handled automatically by the `Migration` class. On each page load, it compares the stored `db_version` setting against `C3_VERSION` and runs any pending migrations.

### Adding a migration (for contributors)

1. Add the version to `Migration::$versions`.
2. Create a method named `migrate_X_Y_Z`.
3. Bump `C3_VERSION` in `core/bootstrap.php`.

```php
// In core/classes/Migration.php
private static $versions = [
    '3.0.0',
    '3.1.0',
    '3.2.0',  // Add your version
];

private static function migrate_3_2_0()
{
    $t = DB::prefix();
    DB::query("ALTER TABLE {$t}posts ADD COLUMN reading_time INT DEFAULT 0 AFTER views");
}
```

The migration runs once and the version is recorded, so it won't run again on subsequent page loads.
