<?php

declare(strict_types=1);

namespace Training\Exercise;

use Training\Clock;

/**
 * Prüft die Links einer Übung (E-10, E-18) und setzt die Serverfelder status und geprueft_am.
 * Videos mit Embed-Adresse über oEmbed (YouTube/Vimeo antworten auf der Watch-Seite auch bei gelöschten Videos mit
 * 200), übrige Links per GET. Bewertung: 2xx ok; 404/410 und übrige 4xx defekt; 401/403 bei oEmbed defekt (privat),
 * bei Textseiten ungeprueft (Bot-Schutz); 429/5xx, Netzfehler, zu viele Weiterleitungen ungeprueft; gesperrtes Ziel
 * (E-19) defekt. Bei ungeprueft bleibt ein früheres Ergebnis derselben Adresse erhalten.
 */
final class LinkChecker
{
    public function __construct(private readonly LinkFetcher $fetcher, private readonly Clock $clock)
    {
    }

    /**
     * Mehrere Linklisten in einem parallelen Durchgang (Cron: mehrere Übungen).
     * @param array<array-key, list<array<string, mixed>>> $lists Schlüssel → Links (mit url, art, embed)
     * @param array<array-key, list<array<string, mixed>>> $previous Schlüssel → bisherige Links (Ergebnis bei ungeprueft behalten)
     * @return array<array-key, list<array<string, mixed>>> Links mit status und geprueft_am
     */
    public function checkMany(array $lists, array $previous = []): array
    {
        $urls = [];
        foreach ($lists as $links) {
            foreach ($links as $link) {
                $urls[] = self::checkUrl($link);
            }
        }
        $answers = $urls === [] ? [] : $this->fetcher->fetchAll(array_values(array_unique($urls)));
        $today = gmdate('Y-m-d', $this->clock->now());

        $out = [];
        foreach ($lists as $key => $links) {
            $before = [];
            foreach ($previous[$key] ?? [] as $old) {
                if (isset($old['url'])) {
                    $before[(string) $old['url']] = $old;
                }
            }
            foreach ($links as $i => $link) {
                $status = self::rate($answers[self::checkUrl($link)] ?? 'nicht geprüft', $link['embed'] !== null);
                if ($status === 'ungeprueft' && isset($before[$link['url']]) && ($before[$link['url']]['status'] ?? 'ungeprueft') !== 'ungeprueft') {
                    $link['status'] = $before[$link['url']]['status'];
                    $link['geprueft_am'] = $before[$link['url']]['geprueft_am'] ?? null;
                } else {
                    $link['status'] = $status;
                    $link['geprueft_am'] = $status === 'ungeprueft' ? null : $today;
                }
                $links[$i] = $link;
            }
            $out[$key] = $links;
        }

        return $out;
    }

    /**
     * @param list<array<string, mixed>> $links
     * @param list<array<string, mixed>> $previous
     * @return list<array<string, mixed>>
     */
    public function check(array $links, array $previous = []): array
    {
        return $this->checkMany(['x' => $links], ['x' => $previous])['x'];
    }

    /** Status der Übung aus den Links: ein defekter Link → links_pruefen (E-10). @param list<array<string, mixed>> $links */
    public static function exerciseStatus(array $links): string
    {
        foreach ($links as $link) {
            if (($link['status'] ?? null) === 'defekt') {
                return 'links_pruefen';
            }
        }

        return 'aktiv';
    }

    /** Abzurufende Adresse: oEmbed für eingebettete Videos (E-18), sonst der Link selbst. @param array<string, mixed> $link */
    public static function checkUrl(array $link): string
    {
        $url = (string) $link['url'];
        $embed = $link['embed'] ?? null;
        if (is_string($embed) && str_starts_with($embed, 'https://www.youtube-nocookie.com/')) {
            return 'https://www.youtube.com/oembed?format=json&url=' . rawurlencode('https://www.youtube.com/watch?v=' . basename($embed));
        }
        if (is_string($embed) && str_starts_with($embed, 'https://player.vimeo.com/')) {
            return 'https://vimeo.com/api/oembed.json?url=' . rawurlencode('https://vimeo.com/' . basename($embed));
        }

        return $url;
    }

    private static function rate(int|string $answer, bool $oembed): string
    {
        if (is_string($answer)) {
            return str_contains($answer, 'gesperrt') || str_contains($answer, 'nur ') || str_contains($answer, 'Zugangsdaten') ? 'defekt' : 'ungeprueft';
        }

        return match (true) {
            $answer >= 200 && $answer < 300 => 'ok',
            $answer === 401 || $answer === 403 => $oembed ? 'defekt' : 'ungeprueft',
            $answer === 429, $answer >= 500, $answer < 200, $answer >= 300 && $answer < 400 => 'ungeprueft',
            default => 'defekt',
        };
    }
}
