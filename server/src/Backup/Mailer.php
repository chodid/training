<?php

declare(strict_types=1);

namespace Training\Backup;

/** Versand einer E-Mail mit Anhang (austauschbar für Tests). */
interface Mailer
{
    /** @throws BackupException */
    public function send(string $to, string $subject, string $body, string $attachmentName, string $attachment): void;
}
