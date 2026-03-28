<?php

defined('C3_ROOT') || exit;
/**
 * Core 3 CMS - Widget System
 * Built-in widgets: Custom HTML, Recent Posts, Categories, Recent Comments, Search
 * Zones: sidebar, footer
 */
class Widget {
    private static array $builtIn = [
        'recent_posts' => 'Recent Posts',
        'categories' => 'Categories',
        'recent_comments' => 'Recent Comments',
        'search' => 'Search',
        'custom_html' => 'Custom HTML',
    ];

    /** Get all widgets assigned to a zone */
    public static function zone(string $zone): string {
        $t = DB::t('widgets');
        try {
            $widgets = DB::rows("SELECT * FROM $t WHERE zone=? AND active=1 ORDER BY sort_order", [$zone]);
        } catch (\Exception $e) {
            return '';
        }

        $html = '';
        foreach ($widgets as $w) {
            $html .= self::renderWidget($w);
        }
        return $html;
    }

    /** Render a single widget */
    public static function renderWidget(array $w): string {
        $config = json_decode($w['config'] ?? '{}', true) ?: [];
        $title = $w['title'] ?? '';
        $out = '';

        switch ($w['type']) {
            case 'custom_html':
                $out = $config['html'] ?? '';
                break;

            case 'recent_posts':
                $limit = (int)($config['limit'] ?? 5);
                $posts = DB::rows("SELECT title,slug FROM " . DB::t('posts') . " WHERE status='published' ORDER BY published_at DESC LIMIT $limit");
                $out = '<ul>';
                foreach ($posts as $p) $out .= '<li><a href="' . url('post/' . $p['slug']) . '">' . e($p['title']) . '</a></li>';
                $out .= '</ul>';
                break;

            case 'categories':
                $cats = DB::rows("SELECT c.name,c.slug,(SELECT COUNT(*) FROM " . DB::t('posts') . " WHERE category_id=c.id AND status='published') as pc FROM " . DB::t('categories') . " c ORDER BY c.name");
                $out = '<ul>';
                foreach ($cats as $c) $out .= '<li><a href="' . url('category/' . $c['slug']) . '">' . e($c['name']) . '</a> <span style="color:var(--m);font-size:12px">(' . $c['pc'] . ')</span></li>';
                $out .= '</ul>';
                break;

            case 'recent_comments':
                $limit = (int)($config['limit'] ?? 5);
                $coms = DB::rows("SELECT c.author_name,c.content,p.slug,p.title as pt FROM " . DB::t('comments') . " c LEFT JOIN " . DB::t('posts') . " p ON c.post_id=p.id WHERE c.status='approved' ORDER BY c.created_at DESC LIMIT $limit");
                foreach ($coms as $c) {
                    $out .= '<div style="padding:5px 0;border-bottom:1px solid #f0f0f0;font-size:12px"><strong>' . e($c['author_name']) . '</strong> on <a href="' . url('post/' . $c['slug']) . '">' . e(excerpt($c['pt'] ?? '', 30)) . '</a></div>';
                }
                break;

            case 'search':
                $out = '<form action="' . url() . '" method="get" style="display:flex;gap:6px"><input type="text" name="q" placeholder="Search…" style="flex:1;padding:8px 10px;border:1px solid var(--b);border-radius:6px;font-size:13px"><button type="submit" style="padding:8px 12px;background:var(--accent);color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px">Go</button></form>';
                break;

            default:
                // Check if a module provides this widget type
                $moduleOut = '';
                Modules::hook('render_widget_' . $w['type'], $moduleOut, $w, $config);
                $out = $moduleOut;
                break;
        }

        if ( ! $out) return '';

        $h = '<div class="widget">';
        if ($title) $h .= '<h3>' . e($title) . '</h3>';
        $h .= $out . '</div>';
        return $h;
    }

    /** Get available widget types */
    public static function types(): array {
        $types = self::$builtIn;
        Modules::hook('widget_types', $types);
        return $types;
    }

    /** Get all widgets for admin */
    public static function all(): array {
        try {
            return DB::rows("SELECT * FROM " . DB::t('widgets') . " ORDER BY zone, sort_order");
        } catch (\Exception $e) {
            return [];
        }
    }
}
