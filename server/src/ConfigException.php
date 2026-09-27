<?php

declare(strict_types=1);

namespace Training;

final class ConfigException extends \RuntimeException
{
    /** @param list<string> $missingKeys nur Schlüsselnamen, nie Werte */
    public function __construct(string $message, public readonly array $missingKeys = [])
    {
        parent::__construct($message);
    }
}
