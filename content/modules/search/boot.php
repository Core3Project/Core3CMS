<?php
// Search Module - adds /search route and search widget
Modules::on('routes', function(&$routes) {
    $routes['/search'] = ['controller' => 'SearchModule', 'action' => 'results'];
});

class SearchModule {
    public function results() {
        $q = trim($_GET['q'] ?? '');
        $results = [];
        $total = 0;

        if (strlen($q) >= 2) {
            $t = DB::prefix();
            $like = '%' . $q . '%';
            $total = DB::count($t . 'posts', "(title LIKE ? OR content LIKE ? OR excerpt LIKE ?) AND status='published'", [$like, $like, $like]);
            $results = DB::rows(
                "SELECT id,title,slug,excerpt,published_at FROM {$t}posts WHERE (title LIKE ? OR content LIKE ? OR excerpt LIKE ?) AND status='published' ORDER BY published_at DESC LIMIT 20",
                [$like, $like, $like]
            );
        }

        Theme::set('pageTitle', 'Search: ' . $q);
        Theme::partial('header');
        echo '<div class="container"><div class="layout-full">';
        echo '<h1 style="font-size:22px;font-weight:700;margin-bottom:20px">Search results for: <em>' . e($q) . '</em></h1>';

        if (!$q || strlen($q) < 2) {
            echo '<p style="color:var(--m)">Enter at least 2 characters to search.</p>';
        } elseif (!$results) {
            echo '<p style="color:var(--m)">No results found for <strong>' . e($q) . '</strong>. Try different keywords.</p>';
        } else {
            echo '<p style="color:var(--m);margin-bottom:20px">' . $total . ' result' . ($total !== 1 ? 's' : '') . ' found</p>';
            foreach ($results as $r) {
                $excerpt = $r['excerpt'] ?: substr(strip_tags($r['content'] ?? ''), 0, 160);
                echo '<div class="post-card"><div class="post-card-body">';
                echo '<h2 style="margin-bottom:4px"><a href="' . url('post/' . $r['slug']) . '">' . e($r['title']) . '</a></h2>';
                echo '<div class="meta">' . formatDate($r['published_at']) . '</div>';
                echo '<p class="post-excerpt">' . e($excerpt) . '</p>';
                echo '</div></div>';
            }
        }

        // Search form
        echo '<form action="' . url('search') . '" method="get" style="margin-top:24px">';
        echo '<div style="display:flex;gap:8px"><input type="text" name="q" value="' . e($q) . '" placeholder="Search..." class="comment-form" style="flex:1;padding:10px 14px;border:1px solid var(--b,#e5e7eb);border-radius:var(--radius,6px);font-size:14px;font-family:inherit;background:var(--surface,#f9fafb);color:var(--c,#1e1e1e)">';
        echo '<button type="submit" class="btn btn-primary">Search</button></div></form>';

        echo '</div></div>';
        Theme::partial('footer');
    }
}
