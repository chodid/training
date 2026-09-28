<?php

declare(strict_types=1);

namespace Training\Tests\Support;

use Training\Backup\BackupException;
use Training\Backup\Mailer;

final class FakeMailer implements Mailer
{
    /** @var list<array{to: string, subject: string, body: string, name: string, data: string}> */
    public array $sent = [];
    public ?string $fail = null;

    public function send(string $to, string $subject, string $body, string $attachmentName, string $attachment): void
    {
        if ($this->fail !== null) {
            throw new BackupException($this->fail);
        }
        $this->sent[] = ['to' => $to, 'subject' => $subject, 'body' => $body, 'name' => $attachmentName, 'data' => $attachment];
    }
}
