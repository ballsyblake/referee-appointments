<?php
// Email via PHPMailer. Settings come from NCF_SMTP_* / NCF_MAIL_* (see
// config.example.php). With MAIL_MODE=log (the default) nothing is sent:
// each message is appended to storage/mail.log so you can test locally.

declare(strict_types=1);

require_once NCF_ROOT . '/phpMailer/src/Exception.php';
require_once NCF_ROOT . '/phpMailer/src/PHPMailer.php';
require_once NCF_ROOT . '/phpMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * @param array<string,string> $to  email => display name
 * @param bool $bcc  send to everyone as BCC so recipients don't see each other
 */
function send_mail(string $subject, string $html, string $text, array $to, bool $bcc = false): bool
{
    $to = array_filter($to, fn($name, $email) => filter_var($email, FILTER_VALIDATE_EMAIL), ARRAY_FILTER_USE_BOTH);
    if (!$to) {
        return false;
    }
    $mode = cfg('MAIL_MODE');
    if ($mode === 'off') {
        return true;
    }
    if ($mode !== 'smtp') {
        $dir = NCF_ROOT . '/storage';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $entry = sprintf("=== %s\nTo: %s\nSubject: %s\n\n%s\n\n", date('c'),
            implode(', ', array_keys($to)), $subject, $text);
        return file_put_contents($dir . '/mail.log', $entry, FILE_APPEND | LOCK_EX) !== false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = cfg('SMTP_HOST');
        $mail->Port = (int) cfg('SMTP_PORT');
        $mail->SMTPAuth = true;
        $mail->Username = cfg('SMTP_USER');
        $mail->Password = cfg('SMTP_PASS');
        $mail->SMTPSecure = $mail->Port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->setFrom(cfg('MAIL_FROM') ?: cfg('SMTP_USER'), cfg('MAIL_FROM_NAME'));
        foreach ($to as $email => $name) {
            if ($bcc) {
                $mail->addBCC($email, $name);
            } else {
                $mail->addAddress($email, $name);
            }
        }
        if ($bcc) {
            $mail->addAddress(cfg('MAIL_FROM') ?: cfg('SMTP_USER'), cfg('MAIL_FROM_NAME'));
        }
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = $text;
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('[ncf] mail failed: ' . $mail->ErrorInfo);
        return false;
    }
}

function app_url(): string
{
    $url = cfg('APP_URL');
    if ($url !== '') {
        return rtrim($url, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
