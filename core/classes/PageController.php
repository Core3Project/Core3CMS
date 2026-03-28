<?php

defined('C3_ROOT') || exit;
class PageController {
    public function show(string $slug): void {
        $page = DB::row("SELECT * FROM " . DB::t('pages') . " WHERE slug=? AND status='published'", [$slug]);
        if ( ! $page) { http_response_code(404); Theme::render('404', ['pageTitle' => 'Not Found']); return; }
        Theme::render('page', compact('page'));
    }
}
