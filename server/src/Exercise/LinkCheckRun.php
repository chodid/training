<?php

declare(strict_types=1);

namespace Training\Exercise;

use PDO;
use Training\Clock;
use Training\Data\AuditLog;
use Training\Data\ExerciseRepository;
use Training\Data\SettingsRepository;

/**
 * Wöchentliche Linkprüfung im stündlichen Cron (AP-16 Teil D, docs/konzept/uebungskatalog.md 7): einmal je 7 Tage
 * (Marke linkcheck_zuletzt in app_setting) die Links nicht archivierter Übungen prüfen, höchstens 50 je Lauf, die am
 * längsten ungeprüften zuerst (der Rest folgt im nächsten Wochenlauf). Ein defekter Link setzt die Übung auf
 * links_pruefen, ohne defekten Link wird sie wieder aktiv. Keine neue Fassung (nur Serverfelder). Audit exercise_linkcheck.
 */
final class LinkCheckRun
{
    public const SETTING = 'linkcheck_zuletzt';
    public const INTERVAL_S = 7 * 86400;
    public const MAX_LINKS = 50;

    public function __construct(private readonly PDO $pdo, private readonly Clock $clock, private readonly LinkChecker $checker)
    {
    }

    /** @return ?array<string, int> Zusammenfassung, null wenn noch nicht fällig */
    public function runIfDue(): ?array
    {
        $settings = new SettingsRepository($this->pdo, $this->clock);
        $last = (int) $settings->get(self::SETTING, '0');
        $now = $this->clock->now();
        if ($last > 0 && $now - $last < self::INTERVAL_S) {
            return null;
        }
        $result = $this->run();
        $settings->set(self::SETTING, (string) $now);

        return $result;
    }

    /** @return array<string, int> */
    public function run(): array
    {
        $repo = new ExerciseRepository($this->pdo, $this->clock);
        $exercises = [];
        $queue = []; // [geprueft_am, Übungs-ID, Linkindex]
        foreach ($repo->withLinks() as $e) {
            $exercises[$e['id']] = $e;
            foreach ($e['content']['links'] as $i => $link) {
                $queue[] = [(string) ($link['geprueft_am'] ?? ''), $e['id'], $i];
            }
        }
        usort($queue, static fn (array $a, array $b): int => $a <=> $b);
        $picked = array_slice($queue, 0, self::MAX_LINKS);

        $lists = [];
        $previous = [];
        foreach ($picked as [, $id, $i]) {
            $lists[$id][$i] = $exercises[$id]['content']['links'][$i];
            $previous[$id][] = $exercises[$id]['content']['links'][$i];
        }
        $checked = $this->checker->checkMany(array_map(array_values(...), $lists), $previous);
        $today = gmdate('Y-m-d', $this->clock->now());

        $sum = ['links' => count($picked), 'offen' => count($queue) - count($picked), 'ok' => 0, 'defekt' => 0, 'ungeprueft' => 0, 'uebungen' => count($lists),
            'links_pruefen' => 0, 'wieder_aktiv' => 0];
        foreach ($lists as $id => $byIndex) {
            $content = $exercises[$id]['content'];
            foreach (array_keys($byIndex) as $n => $i) {
                $content['links'][$i] = $checked[$id][$n];
                // Ergebnis dieses Laufs: ohne heutiges Prüfdatum war der Link nicht prüfbar (früheres Ergebnis bleibt stehen)
                $sum[$checked[$id][$n]['geprueft_am'] === $today ? $checked[$id][$n]['status'] : 'ungeprueft']++;
            }
            $before = $exercises[$id]['status'];
            $status = LinkChecker::exerciseStatus($content['links']);
            $repo->updateLinkState($id, $content, $status);
            if ($before !== $status) {
                $sum[$status === 'aktiv' ? 'wieder_aktiv' : 'links_pruefen']++;
            }
        }
        if ($picked === []) {
            return $sum; // nichts zu prüfen: kein Audit-Eintrag
        }
        (new AuditLog($this->pdo, $this->clock))->write('cron', 'exercise_linkcheck', 'exercise', null, $sum, sprintf(
            'Linkprüfung: %d Links in %d Übungen (ok %d, defekt %d, nicht prüfbar %d), %d neu „Links prüfen“, %d wieder aktiv, %d für den nächsten Lauf',
            $sum['links'], $sum['uebungen'], $sum['ok'], $sum['defekt'], $sum['ungeprueft'], $sum['links_pruefen'], $sum['wieder_aktiv'], $sum['offen']));

        return $sum;
    }
}
