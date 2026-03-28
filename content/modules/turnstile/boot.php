<?php
/**
 * Cloudflare Turnstile Module
 * Settings: turnstile_site_key, turnstile_secret_key
 */

// Add Turnstile script to head
Modules::on('head', function() {
    $key = Setting::get('turnstile_site_key');
    if (!$key) return '';
    return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
});

// Add widget to comment form
Modules::on('comment_form_fields', function() {
    $key = Setting::get('turnstile_site_key');
    if (!$key) return '';
    return '<div class="cf-turnstile" data-sitekey="' . e($key) . '" style="margin-bottom:12px"></div>';
});

// Add widget to register form
Modules::on('register_form_fields', function() {
    $key = Setting::get('turnstile_site_key');
    if (!$key) return '';
    return '<div class="cf-turnstile" data-sitekey="' . e($key) . '" style="margin-bottom:12px"></div>';
});

// Validate on comment submit
Modules::on('comment_validate', function(&$error) {
    $secret = Setting::get('turnstile_secret_key');
    if (!$secret) return;
    $token = $_POST['cf-turnstile-response'] ?? '';
    if (!$token) { $error = 'Please complete the verification.'; return; }
    $resp = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, stream_context_create([
        'http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''])]
    ]));
    $data = json_decode($resp, true);
    if (empty($data['success'])) $error = 'Verification failed. Please try again.';
});

// Validate on registration
Modules::on('register_validate', function(&$error) {
    $secret = Setting::get('turnstile_secret_key');
    if (!$secret) return;
    $token = $_POST['cf-turnstile-response'] ?? '';
    if (!$token) { $error = 'Please complete the verification.'; return; }
    $resp = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, stream_context_create([
        'http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''])]
    ]));
    $data = json_decode($resp, true);
    if (empty($data['success'])) $error = 'Verification failed.';
});
