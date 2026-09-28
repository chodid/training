<?php

declare(strict_types=1);

namespace Training;

/**
 * Konfiguration aus der .env-Datei im Subdomain-Ordner (außerhalb des Docroots, D-17).
 */
final class Config
{
    /** Pflichtwerte für den Betrieb; weitere kommen mit AP-02 und AP-10. */
    public const REQUIRED = [
        'APP_URL',
        'DB_HOST',
        'DB_NAME',
        'DB_USER',
        'DB_PASSWORD',
        'MIGRATION_SECRET',
        'OAUTH_JWT_SECRET',
        'BACKUP_PASSWORD',
    ];

    /** Mindestlänge für Secrets (D-32); kürzere Werte gelten als fehlend, der Start wird verweigert. */
    public const MIN_LENGTH = [
        'OAUTH_JWT_SECRET' => 32,
        'BACKUP_PASSWORD' => 16,
    ];

    /** @param array<string, string> $values */
    private function __construct(private readonly array $values)
    {
    }

    /**
     * @param list<string> $required
     * @throws ConfigException wenn die Datei fehlt oder Pflichtwerte leer sind
     */
    public static function fromFile(string $path, array $required = self::REQUIRED): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new ConfigException('Konfigurationsdatei .env fehlt oder ist nicht lesbar.', $required);
        }

        $values = self::parse((string) file_get_contents($path));
        $missing = array_values(array_filter($required, static fn (string $key): bool => ($values[$key] ?? '') === ''));
        if ($missing !== []) {
            throw new ConfigException('Pflichtwerte in .env fehlen: ' . implode(', ', $missing), $missing);
        }
        $tooShort = [];
        foreach (self::MIN_LENGTH as $key => $min) {
            if (in_array($key, $required, true) && strlen($values[$key] ?? '') < $min) {
                $tooShort[] = $key;
            }
        }
        if ($tooShort !== []) {
            throw new ConfigException('Werte in .env zu kurz (OAUTH_JWT_SECRET mind. 32, BACKUP_PASSWORD mind. 16 Zeichen): ' . implode(', ', $tooShort), $tooShort);
        }

        return new self($values);
    }

    /** @param array<string, string> $values */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    /**
     * Liest Zeilen der Form SCHLUESSEL=wert. Leerzeilen und Zeilen mit "#" am Anfang werden ignoriert,
     * Werte in einfachen oder doppelten Anführungszeichen werden unverändert übernommen.
     *
     * @return array<string, string>
     */
    public static function parse(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $values = [];
        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = ltrim(substr($line, 7));
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                continue;
            }
            $value = trim(substr($line, $pos + 1));
            $len = strlen($value);
            if ($len >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[$len - 1] === $value[0]) {
                $value = substr($value, 1, -1);
            } else {
                $value = trim((string) preg_replace('/\s+#.*$/', '', $value));
            }
            $values[$key] = $value;
        }

        return $values;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $value = $this->values[$key] ?? '';

        return $value === '' ? $default : $value;
    }

    public function bool(string $key): bool
    {
        return in_array(strtolower((string) $this->get($key, '')), ['1', 'true', 'yes', 'on'], true);
    }

    public function require(string $key): string
    {
        $value = $this->get($key);
        if ($value === null) {
            throw new ConfigException('Pflichtwert in .env fehlt: ' . $key, [$key]);
        }

        return $value;
    }
}
