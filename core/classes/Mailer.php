<?php
/**
 * Email delivery.
 *
 * Sends messages via PHP's built-in mail() function or a raw
 * SMTP socket connection, depending on the 'mail_method' setting.
 *
 * @package Core3
 */
class Mailer
{
    /**
     * Send an email message.
     */
    public static function send($to, $subject, $body, $html = true)
    {
        if (Setting::get('mail_method', 'phpmail') === 'smtp') {
            return self::sendSmtp($to, $subject, $body, $html);
        }

        $from = Setting::get('mail_from', 'noreply@' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'));
        $name = Setting::get('site_name', 'Core 3 CMS');

        $headers  = "From: {$name} <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";

        if ($html) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }

        return mail($to, $subject, $body, $headers);
    }

    /**
     * Send a password-reset email with a tokenised link.
     */
    public static function sendPasswordReset($email, $token)
    {
        $siteName = Setting::get('site_name', 'Core 3 CMS');
        $url = Router::url("admin/reset-password?token={$token}");

        $body = "<p>A password reset was requested for your account on <strong>{$siteName}</strong>.</p>"
            . "<p><a href=\"{$url}\">{$url}</a></p>"
            . "<p>This link expires in one hour. If you did not request this, ignore this email.</p>";

        return self::send($email, "Password Reset — {$siteName}", $body);
    }

    /**
     * Deliver a message over a raw SMTP socket.
     */
    private static function sendSmtp($to, $subject, $body, $html)
    {
        $host = Setting::get('smtp_host', '');
        $port = (int) Setting::get('smtp_port', '587');
        $user = Setting::get('smtp_user', '');
        $pass = Setting::get('smtp_pass', '');
        $enc  = Setting::get('smtp_encryption', 'tls');
        $from = Setting::get('mail_from', 'noreply@localhost');
        $name = Setting::get('site_name', 'Core 3 CMS');
        $mime = $html ? 'text/html' : 'text/plain';

        $prefix = ($enc === 'ssl') ? 'ssl://' : '';
        $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 10);

        if (!$socket) {
            return false;
        }

        $read = function () use ($socket) {
            return fgets($socket, 512);
        };

        $write = function ($data) use ($socket) {
            fwrite($socket, $data . "\r\n");
        };

        $read();
        $write("EHLO localhost");
        $read();

        if ($enc === 'tls') {
            $write("STARTTLS");
            $read();
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write("EHLO localhost");
            $read();
        }

        $write("AUTH LOGIN");
        $read();
        $write(base64_encode($user));
        $read();
        $write(base64_encode($pass));
        $read();

        $write("MAIL FROM:<{$from}>");
        $read();
        $write("RCPT TO:<{$to}>");
        $read();
        $write("DATA");
        $read();

        $message = "From: {$name} <{$from}>\r\n"
            . "To: {$to}\r\n"
            . "Subject: {$subject}\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: {$mime}; charset=UTF-8\r\n"
            . "\r\n"
            . $body . "\r\n.\r\n";

        fwrite($socket, $message);
        $read();

        $write("QUIT");
        fclose($socket);

        return true;
    }
}
