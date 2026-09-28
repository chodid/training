<?php

declare(strict_types=1);

namespace Training\Auth;

/**
 * Kontosperre nach Fehlversuchen (D-33): ab dem 10. Fehlversuch 5 Minuten, jeder weitere Fehlversuch
 * nach Ablauf der Sperre verdoppelt die Dauer (10, 20, 40 min …), höchstens 24 Stunden.
 * Die Zeitbasis ist für Tests verkürzbar.
 */
final class LoginThrottle
{
    public const MAX_FAILURES = 10;

    public function __construct(
        private readonly int $baseSeconds = 300,
        private readonly int $maxSeconds = 86400,
    ) {
    }

    /** Sperrdauer in Sekunden nach dem Fehlversuch Nummer $failures (0 = keine Sperre). */
    public function lockSeconds(int $failures): int
    {
        if ($failures < self::MAX_FAILURES) {
            return 0;
        }
        $step = $failures - self::MAX_FAILURES;
        if ($step >= 31) {
            return $this->maxSeconds;
        }

        return min($this->maxSeconds, $this->baseSeconds * (2 ** $step));
    }

    /** Verbleibende Versuche bis zur ersten Sperre. */
    public function remaining(int $failures): int
    {
        return max(0, self::MAX_FAILURES - $failures);
    }
}
