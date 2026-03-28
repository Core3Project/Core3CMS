<?php

defined('C3_ROOT') || exit;
class Markdown {
    /** Dangerous tags that are always stripped */
    private static $dangerousTags = ['script', 'style', 'iframe', 'object', 'embed', 'applet', 'form', 'input', 'button', 'select', 'textarea'];

    /** Safe block-level HTML tags allowed to pass through */
    private static $safeTags = ['div', 'table', 'thead', 'tbody', 'tr', 'td', 'th', 'pre', 'blockquote', 'section', 'article', 'aside', 'header', 'footer', 'details', 'summary', 'figure', 'figcaption', 'video', 'audio', 'source', 'picture', 'dl', 'dt', 'dd', 'hr', 'br', 'p', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'img', 'strong', 'em', 'b', 'i', 'u', 's', 'del', 'code', 'span', 'sub', 'sup', 'mark', 'abbr', 'cite', 'small', 'time', 'kbd', 'var', 'samp'];

    public static function parse($text) {
        // FIRST: strip dangerous HTML before any processing
        $text = self::sanitize($text);

        // Preserve safe HTML blocks
        $htmlBlocks = [];
        $safePattern = implode('|', self::$safeTags);
        $text = preg_replace_callback('/^<(' . $safePattern . ')(\s[^>]*)?>.*?<\/>/ms', function($m) use (&$htmlBlocks) {
            $key = '%%HTML' . count($htmlBlocks) . '%%';
            $htmlBlocks[$key] = $m[0];
            return $key;
        }, $text);

        // Preserve inline HTML tags (only safe ones)
        $inlineHtml = [];
        $text = preg_replace_callback('/<(\/?)((' . $safePattern . ')[^>]*)>/si', function($m) use (&$inlineHtml) {
            $tag = self::sanitizeTag($m[0]);
            $key = '%%IH' . count($inlineHtml) . '%%';
            $inlineHtml[$key] = $tag;
            return $key;
        }, $text);

        $lines = explode("
", $text);
        $html = '';
        $inList = false;
        $listType = '';
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
                $html .= $trimmed; continue;
            }
            if (preg_match('/^(#{1,6})\s+(.+)$/', $trimmed, $m)) {
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                $level = strlen($m[1]);
                $html .= "<h{$level}>" . self::inline($m[2]) . "</h{$level}>"; continue;
            }
            if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', $trimmed)) {
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                $html .= '<hr>'; continue;
            }
            if (strpos($trimmed, '> ') === 0) {
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                $html .= '<blockquote>' . self::inline(substr($trimmed, 2)) . '</blockquote>'; continue;
            }
            if (preg_match('/^[\-\*]\s+(.+)$/', $trimmed, $m)) {
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                if ( ! $inList || $listType !== 'ul') { if ($inList) $html .= "</{$listType}>"; $html .= '<ul>'; $inList = true; $listType = 'ul'; }
                $html .= '<li>' . self::inline($m[1]) . '</li>'; continue;
            }
            if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $m)) {
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                if ( ! $inList || $listType !== 'ol') { if ($inList) $html .= "</{$listType}>"; $html .= '<ol>'; $inList = true; $listType = 'ol'; }
                $html .= '<li>' . self::inline($m[1]) . '</li>'; continue;
            }
            if (strpos($line, '    ') === 0 || strpos($line, "	") === 0) {
                if ($inParagraph) { $html .= '</p>'; $inParagraph = false; }
                $html .= '<pre><code>' . htmlspecialchars(ltrim($line, " 	")) . '</code></pre>'; continue;
            }
            if ($inList) { $html .= "</{$listType}>"; $inList = false; }
            if ( ! $inParagraph) { $html .= '<p>'; $inParagraph = true; } else { $html .= '<br>'; }
            $html .= self::inline($trimmed);
        }
        if ($inList) $html .= "</{$listType}>";
        if ($inParagraph) $html .= '</p>';

        foreach ($htmlBlocks as $key => $block) $html = str_replace($key, $block, $html);
        foreach ($inlineHtml as $key => $tag) $html = str_replace($key, $tag, $html);
        return $html;
    }

    private static function inline($text) {
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

    /** Strip dangerous HTML tags and attributes */
    private static function sanitize($text) {
        // Remove dangerous tags entirely (including content)
        foreach (self::$dangerousTags as $tag) {
            $text = preg_replace('/<' . $tag . '[^>]*>.*?<\/' . $tag . '>/si', '', $text);
            $text = preg_replace('/<' . $tag . '[^>]*\/?>/si', '', $text);
        }
        // Remove event handler attributes (onclick, onerror, onload, etc.)
        $text = preg_replace('/\s+on[a-z]+\s*=\s*["\'][^"\']*["\']/si', '', $text);
        $text = preg_replace('/\s+on[a-z]+\s*=\s*\S+/si', '', $text);
        // Remove javascript: URLs
        $text = preg_replace('/href\s*=\s*["\']?\s*javascript\s*:/si', 'href="', $text);
        $text = preg_replace('/src\s*=\s*["\']?\s*javascript\s*:/si', 'src="', $text);
        // Remove data: URLs in src (can execute JS)
        $text = preg_replace('/src\s*=\s*["\']?\s*data\s*:/si', 'src="', $text);
        return $text;
    }

    /** Sanitize a single HTML tag - remove dangerous attributes */
    private static function sanitizeTag($tag) {
        $tag = preg_replace('/\s+on[a-z]+\s*=\s*["\'][^"\']*["\']/si', '', $tag);
        $tag = preg_replace('/\s+on[a-z]+\s*=\s*\S+/si', '', $tag);
        return $tag;
    }

    public static function render($content, $format = 'auto') {
        if ($format === 'markdown') return self::parse($content);
        if ($format === 'auto' && preg_match('/^#{1,6}\s/m', $content)) return self::parse($content);
        // For HTML format, still sanitize dangerous tags
        return self::sanitize($content);
    }
}
