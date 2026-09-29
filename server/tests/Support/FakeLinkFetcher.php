<?php

declare(strict_types=1);

namespace Training\Tests\Support;

use Training\Exercise\LinkFetcher;

/** Simuliert die Linkprüfung: Antwort je Adresse (Status oder Fehlertext), Standard 200; protokolliert die Abrufe. */
final class FakeLinkFetcher implements LinkFetcher
{
    /** @var list<list<string>> Abrufe je Durchgang */
    public array $calls = [];

    /** @param array<string, int|string> $answers Adresse (oder Teilstring) → Antwort */
    public function __construct(public array $answers = [])
    {
    }

    public function fetchAll(array $urls): array
    {
        $this->calls[] = $urls;
        $out = [];
        foreach ($urls as $url) {
            $out[$url] = 200;
            foreach ($this->answers as $needle => $answer) {
                if (str_contains($url, $needle)) {
                    $out[$url] = $answer;
                }
            }
        }

        return $out;
    }
}
