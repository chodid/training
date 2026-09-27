<?php

declare(strict_types=1);

namespace Training;

/**
 * Zeitquelle (Unix-Sekunden). In Tests austauschbar, z. B. für Sperrzeiten und Token-Laufzeiten.
 */
interface Clock
{
    public function now(): int;
}
