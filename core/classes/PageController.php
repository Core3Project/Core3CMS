<?php

defined('C3_ROOT') || exit;

/**
 * Static page controller
 *
 * @package Core3
 */
class PageController
{
    /**
     * Display a published page by its slug
     *
     * If this page is set as the "Posts page" in Settings → Reading,
     * the blog post listing is shown instead of the page content.
     *
     * @param string $slug
     *
     * @return void
     */
    public function show($slug)
    {
        $page = DB::row(
            "SELECT * FROM " . DB::t('pages') . " WHERE slug = ? AND status = 'published'",
            [$slug]
        );

        if ( ! $page) {
            http_response_code(404);
            Theme::render('404', ['pageTitle' => 'Not Found']);
            return;
        }

        // check if this page is the designated blog/posts page
        $blogPageId = (int) Setting::get('blog_page_id', '0');

        if ($blogPageId && (int) $page['id'] === $blogPageId) {
            $blog = new BlogController();
            $blog->blogPage();
            return;
        }

        Theme::render('page', ['page' => $page, 'pageTitle' => $page['title']]);
    }
}
