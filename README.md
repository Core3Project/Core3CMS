<p align="center">
  <img src="assets/images/logo.svg" alt="Core 3 CMS" height="80">
</p>

<p align="center">
  A lightweight, self-hosted blog CMS built with PHP and MySQL.<br>
  No frameworks, no Composer, no build tools. Upload and run.
</p>

<p align="center">
  <a href="#installation">Installation</a> •
  <a href="#features">Features</a> •
  <a href="#bundled-modules">Modules</a> •
  <a href="HOOKS.md">Developer Docs</a> •
  <a href="#contributing">Contributing</a>
</p>

---

# Core 3 CMS

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

## Modules & Themes

Core 3 ships with a collection of built-in modules (SEO, search, analytics, contact form, backups, and more) that can be enabled from the admin panel. Two themes are included out of the box — a clean light default and a dark variant.

Modules and themes are installed as simple ZIP files. Building your own is straightforward — see the developer docs below.

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

Core 3 CMS was originally created in 2008 by **William Tayeb** as a lightweight alternative to the bloated CMS landscape of the time. After years of dormancy, the project was picked up and rebuilt from the ground up by **Zubair Fazal**, who has been actively developing and maintaining it since.

### Contributors

<table>
  <tr>
    <td align="center">
      <a href="https://github.com/williamtayeb">
        <img src="https://github.com/williamtayeb.png" width="80" height="80" style="border-radius:50%" alt="williamtayeb"><br>
        <sub><b>William Tayeb</b></sub>
      </a><br>
      <sub>Creator</sub>
    </td>
    <td align="center">
      <a href="https://github.com/zubairfazal">
        <img src="https://github.com/zubairfazal.png" width="80" height="80" style="border-radius:50%" alt="zubairfazal"><br>
        <sub><b>Zubair Fazal</b></sub>
      </a><br>
      <sub>Lead Developer</sub>
    </td>
    <td align="center">
      <a href="https://github.com/theadriann">
        <img src="https://github.com/theadriann.png" width="80" height="80" style="border-radius:50%" alt="theadriann"><br>
        <sub><b>Adrian</b></sub>
      </a><br>
      <sub>Contributor</sub>
    </td>
  </tr>
</table>

