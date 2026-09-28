<?php

declare(strict_types=1);

namespace Training\Backup;

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;
use Training\Config;

/** SMTP-Versand über das Mailkonto des Hostings (V-10), Zugangsdaten aus .env. */
final class SmtpMailer implements Mailer
{
    public function __construct(private readonly Config $config)
    {
    }

    public function send(string $to, string $subject, string $body, string $attachmentName, string $attachment): void
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->config->require('SMTP_HOST');
            $mail->Port = (int) $this->config->get('SMTP_PORT', '587');
            $secure = strtolower((string) $this->config->get('SMTP_SECURE', 'tls'));
            $mail->SMTPSecure = $secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAuth = true;
            $mail->Username = $this->config->require('SMTP_USER');
            $mail->Password = $this->config->require('SMTP_PASSWORD');
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Timeout = 20;
            $mail->setFrom($this->config->get('SMTP_FROM') ?? $mail->Username, 'Training');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->addStringAttachment($attachment, $attachmentName, PHPMailer::ENCODING_BASE64, 'application/octet-stream');
            $mail->send();
        } catch (MailException $e) {
            throw new BackupException('E-Mail-Versand fehlgeschlagen: ' . $mail->ErrorInfo);
        }
    }
}
