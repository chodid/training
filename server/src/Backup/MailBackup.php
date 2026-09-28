<?php

declare(strict_types=1);

namespace Training\Backup;

use Training\Clock;

/**
 * Zeitgesteuerter Backup-Versand per E-Mail (D-18 b). Der Lima-City-Cronjob ruft den Endpunkt z. B. täglich auf;
 * versendet wird nur, wenn seit dem letzten erfolgreichen Versand das Intervall abgelaufen ist.
 * Zustand (letzter Versuch, Erfolg, Fehler) liegt in var/backup-mail.json und wird in den Einstellungen angezeigt.
 */
final class MailBackup
{
    public function __construct(
        private readonly BackupService $backups,
        private readonly Mailer $mailer,
        private readonly Clock $clock,
        private readonly string $stateFile,
    ) {
    }

    /** @return array<string, mixed> */
    public function state(): array
    {
        $data = is_file($this->stateFile) ? json_decode((string) file_get_contents($this->stateFile), true) : null;

        return is_array($data) ? $data : [];
    }

    /** @return array{status: string, message: string} status: sent | skipped | error */
    public function run(string $to, int $intervalDays, bool $force = false): array
    {
        $state = $this->state();
        $now = $this->clock->now();
        $last = (int) ($state['last_success'] ?? 0);
        if (!$force && $last > 0 && $now - $last < $intervalDays * 86400 - 3600) {
            return ['status' => 'skipped', 'message' => 'Letzter Versand ' . gmdate('Y-m-d H:i', $last) . ' UTC, Intervall ' . $intervalDays . ' Tage.'];
        }
        $state['last_attempt'] = $now;
        try {
            $backup = $this->backups->create('mail');
            $this->mailer->send(
                $to,
                'Training – Backup ' . gmdate('d.m.Y', $now),
                "Verschlüsseltes Datenbank-Backup (Schemastand {$backup['schema']}).\n\n"
                . "Entschlüsseln: openssl enc -d -aes-256-cbc -pbkdf2 -iter " . Encryptor::ITERATIONS . " -md sha256 -in {$backup['name']} -out backup.sql.gz\n"
                . "Anleitung zur Wiederherstellung: README, Abschnitt Wiederherstellung.\n",
                $backup['name'],
                $backup['data'],
            );
            $state['last_success'] = $now;
            $state['last_size'] = strlen($backup['data']);
            $state['last_name'] = $backup['name'];
            unset($state['error']);
            $result = ['status' => 'sent', 'message' => 'Backup ' . $backup['name'] . ' (' . strlen($backup['data']) . ' Byte) versendet.'];
        } catch (\Throwable $e) {
            $state['error'] = $e->getMessage();
            error_log('[training] Backup-Mail: ' . $e->getMessage());
            $result = ['status' => 'error', 'message' => $e->getMessage()];
        }
        if (!is_dir(dirname($this->stateFile))) {
            @mkdir(dirname($this->stateFile), 0750, true);
        }
        if (@file_put_contents($this->stateFile, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) === false) {
            error_log('[training] Backup-Mail: Zustand nicht speicherbar: ' . $this->stateFile);
        }

        return $result;
    }
}
