<p align="center">
  <img src="assets/images/logo.svg" alt="Core 3 CMS" height="80">
</p>

<p align="center">
  A lightweight, open-source PHP CMS and blogging platform.<br>
  No frameworks, no Composer, no build tools — just upload and run.
</p>

<p align="center">
  <a href="#installation">Installation</a> •
  <a href="HOOKS.md">Developer Docs</a> •
  <a href="#contributing">Contributing</a>
</p>

---

## About

Core 3 CMS is a lightweight alternative to WordPress, built with pure PHP and MySQL. The entire CMS weighs in at around 160 KB — roughly 400x smaller than a default WordPress installation. It runs on virtually any PHP host without requiring Composer, Node, or command-line access.

It ships with a theme engine, a hook-based module system, a WordPress-style admin panel, and a collection of built-in modules that can be activated with a single click. Developers can extend it by dropping a folder into the modules directory.

## Requirements

- PHP 7.4 or later
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite`

## Installation

1. Upload the files to your web server.
2. Navigate to `https://yoursite.com/install/` in your browser.
3. Follow the installer.
4. Delete the `/install` directory.

## Documentation

Core 3 has a developer reference covering the hook system, module structure, theme architecture, and database migrations.

**[Read the docs →](HOOKS.md)**

## Contributing

Contributions are welcome. Please follow the existing code style (PSR-adjacent, PHP 7.4 compatible) and test on PHP 7.4 and 8.x. If your changes touch the database schema, include a migration in `core/classes/Migration.php`.

## Licence

MIT

## Credits

Core 3 CMS was originally created in 2008 by **William Tayeb**. The project was later picked up and rebuilt from the ground up by **Zubair Fazal**, who has been actively developing and maintaining it since.

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
