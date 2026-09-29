<?php

declare(strict_types=1);

namespace Training\Exercise;

/** Ruft Adressen für die Linkprüfung ab (austauschbar für Tests). */
interface LinkFetcher
{
    /**
     * Ruft alle Adressen parallel ab (GET, höchstens 64 kB, Timeout je Adresse).
     * @param list<string> $urls
     * @return array<string, int|string> Adresse → HTTP-Status, oder Fehlertext bei Netzfehler/gesperrtem Ziel
     */
    public function fetchAll(array $urls): array;
}
