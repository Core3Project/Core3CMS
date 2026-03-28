<?php
/**
 * Widget system.
 *
 * Widgets are small content blocks placed in zones (sidebar, footer).
 * Built-in types include recent posts, categories, search, custom HTML,
 * and recent comments. Modules can register additional types.
 *
 * @package Core3
 */
class Widget
{
    /**
     * Available widget type labels.
     */
    public static function types()
    {
        return [
            'recent_posts'    => 'Recent Posts',
            'categories'      => 'Categories',
            'search'          => 'Search',
            'custom_html'     => 'Custom HTML',
            'recent_comments' => 'Recent Comments',
        ];
    }

    /**
     * Fetch all widgets ordered by sort_order.
     */
    public static function all()
    {
        return DB::rows(
            'SELECT * FROM ' . DB::t('widgets') . ' ORDER BY sort_order ASC'
        );
    }

    /**
     * Render all active widgets for a given zone.
     */
    public static function zone($zone)
    {
        $widgets = DB::rows(
            'SELECT * FROM ' . DB::t('widgets') . ' WHERE zone = ? AND active = 1 ORDER BY sort_order ASC',
            [$zone]
        );

        $output = '';

        foreach ($widgets as $w) {
            $config = json_decode($w['config'], true);
            if (!is_array($config)) {
                $config = [];
            }

            $title  = $w['title'] ? '<h3>' . e($w['title']) . '</h3>' : '';
            $body   = self::renderType($w['type'], $config);

            if ($body) {
                $output .= '<div class="widget">' . $title . $body . '</div>';
            }
        }

        return $output;
    }

    /**
     * Render the content for a specific widget type.
     */
    private static function renderType($type, $config)
    {
        $t     = DB::prefix();
        $limit = isset($config['limit']) ? max(1, (int) $config['limit']) : 5;

        switch ($type) {
            case 'recent_posts':
                $posts = DB::rows(
                    "SELECT title, slug FROM {$t}posts WHERE status = 'published' ORDER BY published_at DESC LIMIT {$limit}"
                );
                if (!$posts) return '';
                $html = '<ul>';
                foreach ($posts as $p) {
                    $html .= '<li><a href="' . url('post/' . $p['slug']) . '">' . e($p['title']) . '</a></li>';
                }
                return $html . '</ul>';

            case 'categories':
                $cats = DB::rows("SELECT name, slug FROM {$t}categories ORDER BY name");
                if (!$cats) return '';
                $html = '<ul>';
                foreach ($cats as $c) {
                    $html .= '<li><a href="' . url('category/' . $c['slug']) . '">' . e($c['name']) . '</a></li>';
                }
                return $html . '</ul>';

            case 'search':
                return '<form action="' . url('search') . '" method="get">'
                    . '<input type="text" name="q" placeholder="Search&hellip;" '
                    . 'style="width:100%;padding:8px 10px;border:1px solid var(--b,#e5e7eb);border-radius:4px;font-size:14px;font-family:inherit;background:var(--surface,#f9fafb);color:var(--c,#1e1e1e)">'
                    . '</form>';

            case 'custom_html':
                return isset($config['html']) ? $config['html'] : '';

            case 'recent_comments':
                $comments = DB::rows(
                    "SELECT c.author_name, c.content, p.slug, p.title AS post_title "
                    . "FROM {$t}comments c LEFT JOIN {$t}posts p ON c.post_id = p.id "
                    . "WHERE c.status = 'approved' ORDER BY c.created_at DESC LIMIT {$limit}"
                );
                if (!$comments) return '';
                $html = '<ul>';
                foreach ($comments as $c) {
                    $snippet = mb_substr(strip_tags($c['content']), 0, 60) . '&hellip;';
                    $html .= '<li><strong>' . e($c['author_name']) . '</strong> on '
                        . '<a href="' . url('post/' . $c['slug']) . '">' . e($c['post_title']) . '</a>'
                        . '<br><small style="color:var(--m,#6b7280)">' . $snippet . '</small></li>';
                }
                return $html . '</ul>';

            default:
                return '';
        }
    }
}
