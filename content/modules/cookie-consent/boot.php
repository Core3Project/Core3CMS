<?php
// Cookie Consent Module - GDPR banner
Modules::on('footer', function() {
    $msg = Setting::get('cookie_message', 'This website uses cookies to ensure you get the best experience.');
    $btn = Setting::get('cookie_button', 'Accept');
    $policyUrl = Setting::get('cookie_policy_url', '');
    $policyLink = $policyUrl ? ' <a href="' . e($policyUrl) . '" style="color:inherit;text-decoration:underline">Learn more</a>' : '';

    return '
<div id="c3-cookie" style="display:none;position:fixed;bottom:0;left:0;right:0;background:rgba(0,0,0,.92);color:#fff;padding:14px 20px;font-size:14px;z-index:9999;backdrop-filter:blur(8px)">
<div style="max-width:960px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
<p style="margin:0;line-height:1.5">' . e($msg) . $policyLink . '</p>
<button onclick="document.getElementById(\'c3-cookie\').style.display=\'none\';document.cookie=\'c3_cookies=1;path=/;max-age=31536000;SameSite=Lax\'" style="background:#fff;color:#000;border:none;padding:8px 20px;border-radius:4px;font-weight:600;cursor:pointer;font-size:14px;font-family:inherit;white-space:nowrap">' . e($btn) . '</button>
</div></div>
<script>if(!document.cookie.match(/c3_cookies=1/))document.getElementById("c3-cookie").style.display="block";</script>';
});
