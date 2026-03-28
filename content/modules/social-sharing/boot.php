<?php
// Social Sharing Module
Modules::on('post_content_after', function(&$post) {
    $networks = array_map('trim', explode(',', Setting::get('share_networks', 'twitter,facebook,linkedin,email')));
    $url = urlencode(Router::url('post/' . $post['slug']));
    $title = urlencode($post['title']);
    $links = [];

    foreach ($networks as $n) {
        switch ($n) {
            case 'twitter':
                $links[] = '<a href="https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title . '" target="_blank" rel="noopener" class="share-btn share-twitter" title="Share on X">𝕏</a>';
                break;
            case 'facebook':
                $links[] = '<a href="https://www.facebook.com/sharer/sharer.php?u=' . $url . '" target="_blank" rel="noopener" class="share-btn share-facebook" title="Share on Facebook">f</a>';
                break;
            case 'linkedin':
                $links[] = '<a href="https://www.linkedin.com/sharing/share-offsite/?url=' . $url . '" target="_blank" rel="noopener" class="share-btn share-linkedin" title="Share on LinkedIn">in</a>';
                break;
            case 'reddit':
                $links[] = '<a href="https://reddit.com/submit?url=' . $url . '&title=' . $title . '" target="_blank" rel="noopener" class="share-btn share-reddit" title="Share on Reddit">r/</a>';
                break;
            case 'whatsapp':
                $links[] = '<a href="https://wa.me/?text=' . $title . '%20' . $url . '" target="_blank" rel="noopener" class="share-btn share-whatsapp" title="Share on WhatsApp">wa</a>';
                break;
            case 'email':
                $links[] = '<a href="mailto:?subject=' . $title . '&body=' . $url . '" class="share-btn share-email" title="Share via Email">✉</a>';
                break;
        }
    }

    if (!$links) return '';
    $html = '<div class="share-buttons"><span class="share-label">Share this post:</span>';
    $html .= implode('', $links);
    $html .= '</div>';
    $html .= '<style>.share-buttons{display:flex;align-items:center;gap:8px;margin-top:24px;padding-top:16px;border-top:1px solid var(--b,#e5e7eb);flex-wrap:wrap}.share-label{font-size:13px;font-weight:600;color:var(--m,#6b7280)}.share-btn{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;font-size:14px;font-weight:700;text-decoration:none;color:#fff;transition:.15s}.share-btn:hover{opacity:.85;text-decoration:none;transform:scale(1.1)}.share-twitter{background:#000}.share-facebook{background:#1877f2}.share-linkedin{background:#0a66c2}.share-reddit{background:#ff4500}.share-whatsapp{background:#25d366}.share-email{background:var(--m,#6b7280)}</style>';
    return $html;
});
