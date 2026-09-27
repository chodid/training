<?php

declare(strict_types=1);

namespace Training\Intervals;

/** Fehler der Intervals.icu-Anbindung; die Meldung enthält nie den API-Key. */
final class IntervalsException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0)
    {
        parent::__construct($message);
    }
}
