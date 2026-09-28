<?php

declare(strict_types=1);

namespace Training\Data;

use PDO;
use Training\Clock;
use Training\Db;

/** Audit-Log aller Schreibzugriffe (Abschnitt 12.4); payload_hash statt Inhalt, damit keine Gesundheitsdaten doppelt liegen. */
final class AuditLog
{
    public function __construct(private readonly PDO $pdo, private readonly Clock $clock)
    {
    }

    public function write(string $actor, string $action, string $entity, int|string|null $entityId, mixed $payload, string $summary): void
    {
        $hash = $payload === null ? null : hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $this->pdo->prepare('INSERT INTO audit_log (ts, actor, action, entity, entity_id, payload_hash, summary) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([Db::ts($this->clock->now()), $actor, $action, $entity, $entityId === null ? null : (string) $entityId, $hash, mb_substr($summary, 0, 500)]);
    }
}
