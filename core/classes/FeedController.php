<?php
/**
 * Feed controller.
 *
 * Generates the RSS 2.0 feed and XML sitemap for the public site.
 *
 * @package Core3
 */
class FeedController
{
    /**
     * Output the RSS 2.0 feed.
     */
    public function rss()
    {
        $t        = DB::prefix();
        $siteName = Setting::get('site_name', 'Core 3 CMS');
        $siteDesc = Setting::get('site_description', '');

        $posts = DB::rows(
            "SELECT p.*, u.display_name AS author_name "
            . "FROM {$t}posts p "
            . "LEFT JOIN {$t}users u ON p.author_id = u.id "
            . "WHERE p.status = 'published' "
            . "ORDER BY p.published_at DESC LIMIT 20"
        );

        header('Content-Type: application/rss+xml; charset=UTF-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss version="2.0"><channel>';
        echo '<title>' . e($siteName) . '</title>';
        echo '<link>' . SITE_URL . '</link>';
        echo '<description>' . e($siteDesc) . '</description>';
        echo '<language>en</language>';

        foreach ($posts as $p) {
            $link    = Router::url('post/' . $p['slug']);
            $excerpt = $p['excerpt'] ?: mb_substr(strip_tags($p['content']), 0, 300);

            echo '<item>';
            echo '<title>' . e($p['title']) . '</title>';
            echo '<link>' . $link . '</link>';
            echo '<guid>' . $link . '</guid>';
            echo '<pubDate>' . date(DATE_RSS, strtotime($p['published_at'])) . '</pubDate>';
            echo '<dc:creator xmlns:dc="http://purl.org/dc/elements/1.1/">' . e($p['author_name']) . '</dc:creator>';
            echo '<description><![CDATA[' . $excerpt . ']]></description>';
            echo '</item>';
        }

        echo '</channel></rss>';
    }

    /**
     * Output the XML sitemap.
     */
    public function sitemap()
    {
        $t = DB::prefix();

        header('Content-Type: application/xml; charset=UTF-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Homepage.
        echo '<url><loc>' . SITE_URL . '</loc><changefreq>daily</changefreq><priority>1.0</priority></url>';

        // Published posts.
        $posts = DB::rows("SELECT slug, updated_at FROM {$t}posts WHERE status = 'published' ORDER BY published_at DESC");
        foreach ($posts as $p) {
            echo '<url><loc>' . Router::url('post/' . $p['slug']) . '</loc>';
            echo '<lastmod>' . date('Y-m-d', strtotime($p['updated_at'])) . '</lastmod>';
            echo '<changefreq>weekly</changefreq><priority>0.8</priority></url>';
        }

        // Published pages.
        $pages = DB::rows("SELECT slug, updated_at FROM {$t}pages WHERE status = 'published'");
        foreach ($pages as $p) {
            echo '<url><loc>' . Router::url('page/' . $p['slug']) . '</loc>';
            echo '<lastmod>' . date('Y-m-d', strtotime($p['updated_at'])) . '</lastmod>';
            echo '<changefreq>monthly</changefreq><priority>0.6</priority></url>';
        }

        // Categories.
        $cats = DB::rows("SELECT slug FROM {$t}categories");
        foreach ($cats as $c) {
            echo '<url><loc>' . Router::url('category/' . $c['slug']) . '</loc>';
            echo '<changefreq>weekly</changefreq><priority>0.5</priority></url>';
        }

        echo '</urlset>';
    }
}
