<?php

declare(strict_types=1);

namespace Training\Exercise;

/**
 * Vokabular des Übungskatalogs (AP-16, docs/konzept/uebungskatalog.md 4.1/4.2). Erweiterung braucht eine Code- bzw.
 * Schemaänderung (bewusst, wie die ENUMs in D-38); die Werte entsprechen den ENUM-Spalten der Tabelle exercise.
 */
final class Catalog
{
    public const CATEGORIES = ['kraft', 'haltung', 'mobilitaet', 'hangboard', 'campus', 'zugkraft', 'antagonisten'];

    public const PATTERNS = [
        'druecken_horizontal', 'druecken_vertikal', 'ziehen_horizontal', 'ziehen_vertikal', 'knie_dominant',
        'huefte_dominant', 'rumpf', 'schulter', 'bws_haltung', 'unterarm_finger', 'sprunggelenk_fuss', 'mobilitaet', 'sonstiges',
    ];

    public const EQUIPMENT = [
        'koerpergewicht', 'band', 'kettlebell', 'kurzhantel', 'langhantel', 'klimmzugstange', 'ringe', 'hangboard',
        'campusboard', 'box', 'matte', 'faszienrolle', 'stab', 'gymnastikball', 'gewichtsweste', 'sonstiges',
    ];

    public const STATUSES = ['aktiv', 'links_pruefen', 'archiviert'];

    public const KONFIDENZ = ['hoch', 'mittel', 'niedrig', 'einschaetzung'];

    /** Kletterblöcke mit Katalogeintrag (E-05); die übrigen Arten sind Einheitenformate ohne exercise_id. */
    public const BLOCK_KINDS = ['hangboard', 'campus', 'zugkraft', 'antagonisten'];

    private const ACCENTS = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
        'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
        'ô' => 'o', 'õ' => 'o', 'ø' => 'o', 'œ' => 'oe', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ÿ' => 'y',
    ];

    /** Slug (E-08): ASCII-Kleinbuchstaben, Ziffern, einzelne Bindestriche, 3–60 Zeichen. */
    public static function isSlug(mixed $slug): bool
    {
        return is_string($slug) && strlen($slug) >= 3 && strlen($slug) <= 60 && preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) === 1;
    }

    /**
     * Normalisierte Schreibweise für Duplikatschutz und Suche (E-09): Kleinschreibung, Umlaute ae/oe/ue/ss,
     * Bindestriche, Leerzeichen und übrige Satzzeichen gleich (ein Leerzeichen), außen getrimmt.
     * Beispiel: „Bulgarian-Split Squat“ = „bulgarian split squat“, „Kniebeuge (Rückenlage)“ = „kniebeuge rueckenlage“.
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = strtr($text, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        // übrige Akzente fest auf den Grundbuchstaben (ohne intl, damit die gespeicherte Form überall gleich ist)
        $text = strtr($text, self::ACCENTS);

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $text));
    }

    /**
     * Embed-Adresse eines Videolinks (E-04, 4.3): YouTube (watch, youtu.be, shorts) → youtube-nocookie.com/embed,
     * Vimeo (vimeo.com/<Zahl>) → player.vimeo.com/video; alles andere null (bleibt ein Link).
     */
    public static function embedUrl(string $url): ?string
    {
        $parts = parse_url(trim($url));
        if ($parts === false || strtolower($parts['scheme'] ?? '') !== 'https') {
            return null;
        }
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $id = null;
        if (in_array($host, ['www.youtube.com', 'youtube.com', 'm.youtube.com'], true)) {
            if ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $id = is_string($query['v'] ?? null) ? $query['v'] : null;
            } elseif (preg_match('#^/shorts/([^/]+)/?$#', $path, $m)) {
                $id = $m[1];
            }
        } elseif ($host === 'youtu.be' && preg_match('#^/([^/]+)/?$#', $path, $m)) {
            $id = $m[1];
        } elseif (in_array($host, ['vimeo.com', 'www.vimeo.com'], true) && preg_match('#^/(\d{1,12})/?$#', $path, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return $id !== null && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1 ? 'https://www.youtube-nocookie.com/embed/' . $id : null;
    }
}
