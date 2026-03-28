<?php

defined('C3_ROOT') || exit;
class FeedController {
    public function rss(): void {
        $t = DB::t('posts'); $tu = DB::t('users');
        $posts = DB::rows("SELECT p.*,u.display_name as author_name FROM $t p LEFT JOIN $tu u ON p.author_id=u.id WHERE p.status='published' ORDER BY p.published_at DESC LIMIT 20");
        $name = e(Setting::get('site_name', 'Core 3 CMS'));
        $desc = e(Setting::get('site_description', ''));
        $url = e(SITE_URL);

        header('Content-Type: application/rss+xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\"><channel>";
        echo "<title>{$name}</title><link>{$url}</link><description>{$desc}</description>";
        echo "<atom:link href=\"{$url}/feed\" rel=\"self\" type=\"application/rss+xml\"/>";
        foreach ($posts as $p) {
            $link = e(Router::url('post/' . $p['slug']));
            $date = date(DATE_RSS, strtotime($p['published_at']));
            $exc = e(excerpt($p['content'], 300));
            echo "<item><title>" . e($p['title']) . "</title><link>{$link}</link><guid>{$link}</guid><pubDate>{$date}</pubDate><description>{$exc}</description></item>";
        }
        echo "</channel></rss>";
    }

    public function sitemap(): void {
        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Homepage
        echo '<url><loc>' . e(SITE_URL) . '</loc><changefreq>daily</changefreq><priority>1.0</priority></url>';

        // Posts
        $posts = DB::rows("SELECT slug, updated_at FROM " . DB::t('posts') . " WHERE status='published' ORDER BY published_at DESC");
        foreach ($posts as $p) {
            echo '<url><loc>' . e(Router::url('post/' . $p['slug'])) . '</loc><lastmod>' . date('Y-m-d', strtotime($p['updated_at'])) . '</lastmod></url>';
        }

        // Pages
        $pages = DB::rows("SELECT slug, updated_at FROM " . DB::t('pages') . " WHERE status='published'");
        foreach ($pages as $p) {
            echo '<url><loc>' . e(Router::url('page/' . $p['slug'])) . '</loc><lastmod>' . date('Y-m-d', strtotime($p['updated_at'])) . '</lastmod></url>';
        }

        // Categories
        $cats = DB::rows("SELECT slug FROM " . DB::t('categories'));
        foreach ($cats as $c) {
            echo '<url><loc>' . e(Router::url('category/' . $c['slug'])) . '</loc><changefreq>weekly</changefreq></url>';
        }

        echo '</urlset>';
    }
}
