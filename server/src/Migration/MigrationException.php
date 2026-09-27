<?php

declare(strict_types=1);

namespace Training\Migration;

class MigrationException extends \RuntimeException
{
    /** @param list<array{version: int, name: string}> $applied vor dem Fehler erfolgreich ausgeführt */
    public function __construct(string $message, public readonly array $applied = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
