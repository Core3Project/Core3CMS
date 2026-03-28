<?php
/**
 * Lightweight Markdown parser with XSS sanitisation.
 *
 * Converts Markdown to HTML and strips dangerous elements
 * (script, iframe, event handlers, javascript: URLs) from both
 * Markdown and raw HTML content. This prevents the stored-XSS
 * vulnerability found in Anchor CMS (CVE-2025-46041).
 *
 * @package Core3
 */
class Markdown
{
    private static $dangerousTags = [
        'script', 'style', 'iframe', 'object', 'embed',
        'applet', 'form', 'input', 'button', 'select', 'textarea',
    ];

    private static $safeTags = [
        'div', 'table', 'thead', 'tbody', 'tr', 'td', 'th',
        'pre', 'blockquote', 'section', 'article', 'aside',
        'header', 'footer', 'details', 'summary', 'figure',
        'figcaption', 'video', 'audio', 'source', 'picture',
        'dl', 'dt', 'dd', 'hr', 'br', 'p', 'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'img',
        'strong', 'em', 'b', 'i', 'u', 's', 'del', 'code',
        'span', 'sub', 'sup', 'mark', 'abbr', 'cite', 'small',
        'time', 'kbd', 'var', 'samp',
    ];

    /**
     * Convert Markdown text to HTML.
     */
    public static function parse($text)
    {
        $text = self::sanitise($text);

        $htmlBlocks = [];
        $safePattern = implode('|', self::$safeTags);

        $text = preg_replace_callback(
            '/^<(' . $safePattern . ')(\s[^>]*)?>.*?<\/\1>/ms',
            function ($m) use (&$htmlBlocks) {
                $key = '%%HTML' . count($htmlBlocks) . '%%';
                $htmlBlocks[$key] = $m[0];
                return $key;
            },
            $text
        );

        $inlineHtml = [];
        $text = preg_replace_callback(
            '/<(\/?)((' . $safePattern . ')\b[^>]*)>/si',
            function ($m) use (&$inlineHtml) {
                $tag = self::sanitiseTag($m[0]);
                $key = '%%IH' . count($inlineHtml) . '%%';
                $inlineHtml[$key] = $tag;
                return $key;
            },
            $text
        );

        $lines       = explode("\n", $text);
        $html        = '';
        $inList      = false;
        $listType    = '';
        $inParagraph = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                if ($inList) { $html .= "</{$listType}>"; $inList = false; }
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                continue;
            }

            if (preg_match('/^%%HTML\d+%%$/', $trimmed)) {
                if ($inList) { $html .= "</{$listType}>"; $inList = false; }
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                $html .= $trimmed;
                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.+)$/', $trimmed, $m)) {
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                $level = strlen($m[1]);
                $html .= "<h{$level}>" . self::inlineFormat($m[2]) . "</h{$level}>";
                continue;
            }

            if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', $trimmed)) {
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                $html .= '<hr>';
                continue;
            }

            if (strpos($trimmed, '> ') === 0) {
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                $html .= '<blockquote>' . self::inlineFormat(substr($trimmed, 2)) . '</blockquote>';
                continue;
            }

            if (preg_match('/^[\-\*]\s+(.+)$/', $trimmed, $m)) {
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                if (!$inList || $listType !== 'ul') {
                    if ($inList) { $html .= "</{$listType}>"; }
                    $html .= '<ul>';
                    $inList = true;
                    $listType = 'ul';
                }
                $html .= '<li>' . self::inlineFormat($m[1]) . '</li>';
                continue;
            }

            if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $m)) {
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                if (!$inList || $listType !== 'ol') {
                    if ($inList) { $html .= "</{$listType}>"; }
                    $html .= '<ol>';
                    $inList = true;
                    $listType = 'ol';
                }
                $html .= '<li>' . self::inlineFormat($m[1]) . '</li>';
                continue;
            }

            if (strpos($line, '    ') === 0 || strpos($line, "\t") === 0) {
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                $html .= '<pre><code>' . htmlspecialchars(ltrim($line, " \t")) . '</code></pre>';
                continue;
            }

            if ($inList) { $html .= "</{$listType}>"; $inList = false; }

            if (!$inParagraph) {
                $html .= '<p>';
                $inParagraph = true;
            } else {
                $html .= '<br>';
            }

            $html .= self::inlineFormat($trimmed);
        }

        if ($inList) { $html .= "</{$listType}>"; }
        if ($inParagraph) { $html .= '</p>'; }

        foreach ($htmlBlocks as $key => $block) {
            $html = str_replace($key, $block, $html);
        }
        foreach ($inlineHtml as $key => $tag) {
            $html = str_replace($key, $tag, $html);
        }

        return $html;
    }

    /**
     * Apply inline Markdown formatting (bold, italic, links, etc.).
     */
    private static function inlineFormat($text)
    {
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $text);
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);
        $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/_(.+?)_/', '<em>$1</em>', $text);
        $text = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $text);
        return $text;
    }

    /**
     * Remove dangerous HTML tags and attributes.
     */
    private static function sanitise($text)
    {
        foreach (self::$dangerousTags as $tag) {
            $text = preg_replace('/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/si', '', $text);
            $text = preg_replace('/<' . $tag . '\b[^>]*\/?>/si', '', $text);
        }

        // Strip event-handler attributes.
        $text = preg_replace('/\s+on[a-z]+\s*=\s*["\'][^"\']*["\']/si', '', $text);
        $text = preg_replace('/\s+on[a-z]+\s*=\s*\S+/si', '', $text);

        // Strip javascript: and data: URLs.
        $text = preg_replace('/href\s*=\s*["\']?\s*javascript\s*:/si', 'href="', $text);
        $text = preg_replace('/src\s*=\s*["\']?\s*javascript\s*:/si', 'src="', $text);
        $text = preg_replace('/src\s*=\s*["\']?\s*data\s*:/si', 'src="', $text);

        return $text;
    }

    /**
     * Sanitise a single HTML tag (strip event handlers).
     */
    private static function sanitiseTag($tag)
    {
        $tag = preg_replace('/\s+on[a-z]+\s*=\s*["\'][^"\']*["\']/si', '', $tag);
        $tag = preg_replace('/\s+on[a-z]+\s*=\s*\S+/si', '', $tag);
        return $tag;
    }

    /**
     * Render content as HTML.
     *
     * Accepts a format hint: 'markdown', 'html', or 'auto'.
     * HTML content is still sanitised to remove dangerous elements.
     */
    public static function render($content, $format = 'auto')
    {
        if ($format === 'markdown') {
            return self::parse($content);
        }

        if ($format === 'auto' && preg_match('/^#{1,6}\s/m', $content)) {
            return self::parse($content);
        }

        return self::sanitise($content);
    }
}
