<?php
// Sitemap module: /sitemap.xml is handled by FeedController
// This module just adds the meta link in the head
Modules::on('head', function() {
    return '<link rel="sitemap" type="application/xml" title="Sitemap" href="' . url('sitemap.xml') . '">';
});
