<?php
/**
 * Front page template
 *
 * Standalone HTML documents (starting with <!DOCTYPE) are output raw.
 * Everything else is wrapped in the theme header and footer.
 */
$raw = trim($page['content']);

if (stripos($raw, '<!DOCTYPE') === 0 || stripos($raw, '<html') === 0) {
    echo $raw;
} else {
    Theme::partial('header');
    echo $raw;
    Theme::partial('footer');
}
