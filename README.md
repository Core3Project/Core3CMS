# Core 3 CMS

A lightweight, self-hosted blog CMS built with PHP and MySQL. No frameworks, no Composer, no build tools. Upload it to any PHP host and you're running in under a minute.

Core 3 is designed as a simpler alternative to WordPress for developers who want a clean codebase they can understand, extend, and contribute to.

## Features

- **Posts, pages, and categories** with a WYSIWYG editor and Markdown support
- **Theme engine** with template inheritance — child themes only override what they need
- **Module system** with hooks — extend functionality without touching core files
- **User roles** — admin, editor, author, subscriber
- **Comment system** with moderation and honeypot spam protection
- **Widget zones** for sidebar and footer content
- **SEO-friendly URLs** via front-controller routing
- **Password reset** via email (PHP mail or SMTP)
- **Appearance customiser** — upload logos, set accent colours, add custom CSS
- **Database migrations** — safe upgrades between versions
- **Auto-update checker** — notifies admins when a new release is available
- **XSS protection** in the Markdown parser (mitigates CVE-2025-46041 class vulnerabilities)
- **CSRF tokens** on all forms

## Requirements

- PHP 7.4 or later
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` enabled

## Installation

1. Upload the `core3cms` directory to your web server.
2. Visit `https://yoursite.com/core3cms/install/` in your browser.
3. Follow the four-step installer.
4. Delete the `/install` directory when finished.

## Bundled Modules

| Module | Default | Description |
|---|---|---|
| SEO Meta | On | Open Graph tags and meta descriptions |
| Sitemap | On | Auto-generated `/sitemap.xml` |
| Analytics | On | Simple page view tracking |
| Search | On | Site search with results page |
| Auto Updater | On | Checks GitHub for new releases |
| Contact Form | Off | Email contact page at `/contact` |
| Cloudflare Turnstile | Off | Anti-bot verification |
| Social Sharing | Off | Share buttons on posts |
| Cookie Consent | Off | GDPR-compliant dismissable banner |
| Related Posts | Off | Category-based related posts |
| Backup | Off | One-click SQL dump download |
| Media Manager | Off | Upload and browse files |
| Maintenance Mode | Off | "Coming soon" page for visitors |
| Disqus Comments | Off | Replace native comments with Disqus |

## Bundled Themes

- **Default** — clean light theme
- **Dark** — dark colour scheme, inherits templates from Default

## Extending Core 3

See [HOOKS.md](HOOKS.md) for the full developer reference covering:

- All available hooks and how to use them
- Module structure and settings
- Theme structure and template inheritance
- Database migration system for contributors

## Contributing

Contributions are welcome. Please:

1. Fork the repository.
2. Create a feature branch.
3. Follow the existing code style (PSR-adjacent, no frameworks, PHP 7.4 compatible).
4. Test on PHP 7.4 and 8.x.
5. Submit a pull request with a clear description.

If adding database changes, include a migration in `core/classes/Migration.php`.

## Licence

MIT

## Credits

Built by [VexxusArts Ltd](https://vexxusarts.com).
