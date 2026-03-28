<?php
// Related Posts Module
Modules::on('post_content_after', function(&$post) {
    $limit = max(1, min(6, (int) Setting::get('related_count', '3')));
    $t = DB::prefix();

    if (!empty($post['category_id'])) {
        $related = DB::rows(
            "SELECT id,title,slug,published_at FROM {$t}posts WHERE category_id=? AND id!=? AND status='published' ORDER BY published_at DESC LIMIT {$limit}",
            [$post['category_id'], $post['id']]
        );
    } else {
        $related = DB::rows(
            "SELECT id,title,slug,published_at FROM {$t}posts WHERE id!=? AND status='published' ORDER BY published_at DESC LIMIT {$limit}",
            [$post['id']]
        );
    }

    if (!$related) return '';

    $html = '<div class="related-posts" style="margin-top:28px;padding-top:20px;border-top:1px solid var(--b,#e5e7eb)">';
    $html .= '<h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:var(--m,#6b7280);text-transform:uppercase;letter-spacing:.5px;font-size:12px">Related Posts</h3>';
    $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">';

    foreach ($related as $r) {
        $html .= '<a href="' . url('post/' . $r['slug']) . '" style="display:block;padding:14px;background:var(--surface,#f9fafb);border:1px solid var(--b,#e5e7eb);border-radius:6px;text-decoration:none;transition:.15s">';
        $html .= '<strong style="color:var(--c,#1e1e1e);font-size:14px;line-height:1.3;display:block">' . e($r['title']) . '</strong>';
        $html .= '<span style="font-size:12px;color:var(--m,#6b7280);margin-top:4px;display:block">' . formatDate($r['published_at']) . '</span>';
        $html .= '</a>';
    }

    $html .= '</div></div>';
    return $html;
}, 20); // Priority 20 = after social sharing (10)
