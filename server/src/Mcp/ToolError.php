<?php

declare(strict_types=1);

namespace Training\Mcp;

/** Fachlicher Fehler eines Tools; wird als Tool-Ergebnis mit isError an Claude gemeldet. */
final class ToolError extends \RuntimeException
{
    /** @param list<string> $details */
    public function __construct(string $message, public readonly array $details = [])
    {
        parent::__construct($message);
    }
}
