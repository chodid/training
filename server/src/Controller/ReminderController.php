<?php

declare(strict_types=1);

namespace Training\Controller;

use Training\Data\SettingsRepository;
use Training\Dates;
use Training\Http\Request;
use Training\Http\Response;
use Training\Review\Faelligkeit;

/**
 * Erinnerung an fällige Blockbilanz und Zielklärung (AP-15, docs/konzept/blockbilanz.md 6.1, E-05/E-14): Quittierung
 * des Overlays per Formular (POST /erinnerung). „Morgen wieder erinnern“ = Ruhe bis morgen, „Diese Woche nicht mehr“
 * = 7 Tage; gespeichert je Art und Block in app_setting erinnerung_<kind>_<block_id> (Datum, ab dem das Overlay
 * wieder erscheint). Ohne JavaScript bedienbar; offline über den Formularpuffer des Service Workers.
 */
final class ReminderController extends AppController
{
    /** Arten mit Overlay (E-07: Revisionen nur als Hinweis) */
    public const KINDS = ['bilanz', 'zielklaerung'];
    public const TITLES = ['bilanz' => 'Blockbilanz fällig', 'zielklaerung' => 'Zielklärung fällig'];

    public function handle(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }
        if (!$this->csrfOk($request)) {
            return self::queued($request) ? new Response(403, '') : Response::error(403, 'Ungültiges Formular.');
        }
        if ($locked = $this->lockedResponse()) {
            return $locked;
        }
        $days = match ($request->post('bis')) {
            'woche' => 7,
            'morgen' => 1,
            default => null,
        };
        $entries = [];
        foreach ($request->postArray('eintrag') as $raw) {
            if (is_string($raw) && preg_match('/^(bilanz|zielklaerung):(\d{1,10})$/', $raw, $m)) {
                $entries[$m[1] . ':' . $m[2]] = [$m[1], (int) $m[2] > 0 ? (int) $m[2] : null];
            }
        }
        if ($days === null || $entries === []) {
            return self::queued($request) ? new Response(422, '') : Response::error(422, 'Unvollständige Quittierung.');
        }
        $until = Dates::addDays($this->today(), $days);
        $settings = new SettingsRepository($this->app->pdo(), $this->app->clock());
        foreach ($entries as [$kind, $blockId]) {
            $settings->set(SettingsRepository::erinnerungKey($kind, $blockId), $until);
            $this->audit()->write('web', 'reminder_ack', 'app_setting', SettingsRepository::erinnerungKey($kind, $blockId), null,
                ($kind === 'bilanz' ? 'Blockbilanz' : 'Zielklärung') . ' Block ' . ($blockId ?? '–') . ': Erinnerung ab ' . $until);
        }

        return self::saved($request, self::back($request->post('zurueck')));
    }

    /**
     * Einträge des Overlays für heute: fällige Bilanz/Zielklärung ohne gültige Quittierung (E-14). Leer, wenn das Overlay
     * in S8 ausgeschaltet ist.
     * @return list<array{kind: string, block_id: ?int, titel: string, text: string, href: string}>
     */
    public static function due(\PDO $pdo, \Training\Clock $clock, string $today): array
    {
        $settings = new SettingsRepository($pdo, $clock);
        if (!$settings->reviewOverlay()) {
            return [];
        }
        $out = [];
        foreach (Faelligkeit::load($pdo, $clock, $today) as $f) {
            if (!in_array($f['kind'], self::KINDS, true)) {
                continue;
            }
            $until = $settings->erinnerungBis($f['kind'], $f['block_id']);
            if ($until !== null && $until > $today) {
                continue;
            }
            $out[] = ['kind' => $f['kind'], 'block_id' => $f['block_id'], 'titel' => self::TITLES[$f['kind']], 'text' => Faelligkeit::text($f),
                'href' => '/block' . ($f['block_id'] !== null ? '?id=' . $f['block_id'] : '')];
        }

        return $out;
    }

    /** Rücksprung nur auf eigene Seiten (kein offener Redirect). */
    private static function back(?string $target): string
    {
        return is_string($target) && preg_match('#^/(?!/)[A-Za-z0-9/_\-]*(\?[A-Za-z0-9=&%_\-.]*)?$#', $target) && !str_starts_with($target, '/erinnerung') ? $target : '/woche';
    }
}
