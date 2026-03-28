<?php
/**
 * Static page controller.
 *
 * @package Core3
 */
class PageController
{
    /**
     * Display a published page by its slug.
     */
    public function show($slug)
    {
        $page = DB::row(
            "SELECT * FROM " . DB::t('pages') . " WHERE slug = ? AND status = 'published'",
            [$slug]
        );

        if (!$page) {
            http_response_code(404);
            Theme::render('404', ['pageTitle' => 'Not Found']);
            return;
        }

        Theme::render('page', ['page' => $page, 'pageTitle' => $page['title']]);
    }
}
