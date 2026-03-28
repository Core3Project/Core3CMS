<?php

defined('C3_ROOT') || exit;
class Mailer {
    public static function send(string $to, string $subject, string $body, bool $html = true): bool {
        if (Setting::get('mail_method', 'phpmail') === 'smtp') return self::smtp($to, $subject, $body, $html);
        return self::phpmail($to, $subject, $body, $html);
    }

    private static function phpmail(string $to, string $subject, string $body, bool $html): bool {
        $from = Setting::get('mail_from', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $name = Setting::get('site_name', 'Core 3 CMS');
        $h = "From: {$name} <{$from}>
Reply-To: {$from}
";
        if ($html) $h .= "MIME-Version: 1.0
Content-Type: text/html; charset=UTF-8
";
        return @mail($to, $subject, $body, $h);
    }

    private static function smtp(string $to, string $subject, string $body, bool $html): bool {
        $host = Setting::get('smtp_host'); $port = (int) Setting::get('smtp_port', '587');
        $user = Setting::get('smtp_user'); $pass = Setting::get('smtp_pass');
        $enc = Setting::get('smtp_encryption', 'tls');
        $from = Setting::get('mail_from', $user); $name = Setting::get('site_name', 'Core 3 CMS');
        if ( ! $host || !$user) return self::phpmail($to, $subject, $body, $html);

        try {
            $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
            $s = stream_socket_client(($enc === 'ssl' ? 'ssl://' : 'tcp://') . "$host:$port", $en, $es, 30, STREAM_CLIENT_CONNECT, $ctx);
            if ( ! $s) return false;
            self::r($s); self::c($s, "EHLO " . gethostname());
            if ($enc === 'tls') { self::c($s, "STARTTLS"); stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT); self::c($s, "EHLO " . gethostname()); }
            self::c($s, "AUTH LOGIN"); self::c($s, base64_encode($user)); self::c($s, base64_encode($pass));
            self::c($s, "MAIL FROM:<$from>"); self::c($s, "RCPT TO:<$to>"); self::c($s, "DATA");
            $mime = $html ? "text/html" : "text/plain";
            fwrite($s, "From: $name <$from>
To: $to
Subject: $subject
MIME-Version: 1.0
Content-Type: $mime; charset=UTF-8

$body
.
");
            self::r($s); self::c($s, "QUIT"); fclose($s);
            return true;
        } catch (\Exception $e) { return false; }
    }

    private static function c($s, $cmd) { fwrite($s, "$cmd
"); return self::r($s); }
    private static function r($s) { $d = ''; while ($l = fgets($s, 515)) { $d .= $l; if (($l[3] ?? '') === ' ') break; } return $d; }

    public static function sendPasswordReset(string $email, string $token): bool {
        $name = Setting::get('site_name', 'Core 3 CMS');
        $url = Router::url("admin/reset-password?token=$token");
        $body = "<div style='font-family:sans-serif;max-width:500px;margin:0 auto'><h2>{$name}</h2><p>A password reset was requested.</p><p><a href='{$url}' style='display:inline-block;padding:12px 24px;background:#111;color:#fff;text-decoration:none;border-radius:6px'>Reset Password</a></p><p style='font-size:12px;color:#999'>Expires in 1 hour.</p></div>";
        return self::send($email, "Password Reset - $name", $body);
    }
}
