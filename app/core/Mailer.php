<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

/**
 * Envío de correo vía SMTP (PHPMailer).
 * Brevo: smtp-relay.brevo.com:587, SMTP_USER = login @smtp-brevo.com,
 * SMTP_PASS = clave SMTP secreta. SMTP_FROM_EMAIL debe estar verificado en Brevo.
 */
class Mailer
{
    public static function isConfigured(): bool
    {
        return SMTP_HOST !== '' && SMTP_USER !== '' && SMTP_PASS !== '';
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public static function send(string $to, string $subject, string $htmlBody, ?string $replyTo = null): array
    {
        if (!self::isConfigured()) {
            return ['ok' => false, 'error' => 'SMTP no configurado (SMTP_HOST, SMTP_USER, SMTP_PASS).'];
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Correo destinatario inválido.'];
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->Port = SMTP_PORT;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;

            if (SMTP_ENCRYPTION === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif (SMTP_ENCRYPTION === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPAutoTLS = false;
                $mail->SMTPSecure = false;
            }

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            if ($replyTo !== null && $replyTo !== '') {
                $mail->addReplyTo($replyTo);
            }
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            return ['ok' => true];
        } catch (MailerException $e) {
            return ['ok' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
        }
    }
}
