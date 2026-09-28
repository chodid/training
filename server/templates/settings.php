<?php
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var string $csrf */
/** @var string $login */
$fmt = static fn (?int $t, string $tz): string => $t === null ? '–' : (new DateTimeImmutable('@' . $t))->setTimezone(new DateTimeZone($tz))->format('d.m.Y');
$fmtDb = static fn (?string $v, string $tz): string => $v === null ? '–' : (new DateTimeImmutable($v . ' UTC'))->setTimezone(new DateTimeZone($tz))->format('d.m.Y, H:i') . ' Uhr';
$scopeText = static fn (string $s): string => str_contains($s, 'training:write') ? 'Lesen und Schreiben' : 'Nur lesen';
?>
<div class="stack-lg">
<?php if ($alert !== null) { include __DIR__ . '/_alert.php'; } ?>
<?php if ($schemaDb !== null && $schemaDb !== $schemaCode): ?>
  <div class="alert alert-warning"><?= $this->icon('alert-triangle') ?><div><b>Update erforderlich.</b> <span class="body">Der Code erwartet Schemastand <?= $schemaCode ?>, die Datenbank steht auf <?= $schemaDb ?>. Das Deployment führt die Migration normalerweise selbst aus.</span></div></div>
<?php endif ?>

  <section>
    <div class="section-title"><h2>Konto</h2></div>
    <div class="card list">
      <div class="list-item"><div><div class="t">Angemeldet als <?= $this->e($login) ?></div><div class="s">Seit <?= $this->e($fmt($since, $tz)) ?> auf diesem Gerät · Sitzung endet nach 30 Tagen ohne Nutzung</div></div>
        <form method="post" action="/logout"><input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>"><button class="btn btn-secondary" type="submit"><?= $this->icon('logout') ?>Abmelden</button></form></div>
      <div class="list-item"><div><div class="t">Zeitzone</div><div class="s mono"><?= $this->e($tz) ?></div></div><a class="btn btn-ghost" href="/einstellungen?bereich=zeitzone">Ändern</a></div>
      <div class="list-item"><div><div class="t">Passwort</div><div class="s">Mindestens 12 Zeichen</div></div><a class="btn btn-ghost" href="/einstellungen?bereich=passwort">Ändern</a></div>
    </div>
  </section>

  <section>
    <div class="section-title"><h2>Backup</h2><span class="hint">folgt mit AP-10</span></div>
    <div class="card list">
      <div class="list-item"><div><div class="t">Backup herunterladen</div><div class="s">SQL-Dump, gzip, verschlüsselt mit dem Backup-Passwort aus der <span class="mono">.env</span></div></div><span class="badge badge-neutral">noch nicht verfügbar</span></div>
      <div class="list-item"><div><div class="t">Backup per E-Mail</div><div class="s">Wöchentlich, zeitgesteuert über Lima-City</div></div><span class="badge badge-neutral">noch nicht eingerichtet</span></div>
    </div>
  </section>

  <section>
    <div class="section-title"><h2>Update</h2></div>
    <div class="card list">
      <div class="list-item"><div><div class="t">Schemastand</div><div class="s">Code <?= $schemaCode ?> · Datenbank <?= $schemaDb ?? '?' ?><?= $schemaDb === $schemaCode ? ' · alles aktuell' : ' · Migration ausstehend' ?></div></div>
        <?php if ($schemaDb === $schemaCode): ?><span class="badge badge-success"><?= $this->icon('check') ?>aktuell</span><?php else: ?><span class="badge badge-warning">ausstehend</span><?php endif ?></div>
      <div class="list-item"><div><div class="t">Migration ausführen</div><div class="s">Über die Webseite mit vorherigem Backup ab AP-10; bis dahin durch das Deployment.</div></div><button class="btn btn-secondary" type="button" disabled><?= $this->icon('refresh') ?>Migrieren</button></div>
      <div class="list-item"><div><div class="t">Version</div><div class="s mono"><?= $this->e($version) ?></div></div></div>
    </div>
  </section>

  <section>
    <div class="section-title"><h2>Verbindungen</h2></div>
    <div class="card list">
      <div class="list-item"><div><div class="t">Intervals.icu</div><div class="s"><?= $intervals !== null ? 'Athlet ' . $this->e($intervals) . ' · <a href="/intervals">Verbindung prüfen</a>' : 'Nicht eingerichtet: INTERVALS_API_KEY und INTERVALS_ATHLETE_ID in der <span class="mono">.env</span>' ?></div></div>
        <?php if ($intervals !== null): ?><span class="badge badge-success"><?= $this->icon('plug-connected') ?>eingerichtet</span><?php else: ?><span class="badge badge-neutral">aus</span><?php endif ?></div>
<?php if ($clients === []): ?>
      <div class="list-item"><div><div class="t">Claude</div><div class="s">Keine aktive Freigabe. Connector-Adresse: <span class="mono"><?= $this->e($mcpUrl) ?></span></div></div><span class="badge badge-neutral">nicht verbunden</span></div>
<?php endif ?>
<?php foreach ($clients as $c):
    $uris = json_decode((string) $c['redirect_uris_json'], true) ?: [];
    $host = (string) parse_url((string) ($uris[0] ?? ''), PHP_URL_HOST);
?>
      <div class="list-item"><div><div class="t"><?= $this->e($c['client_name']) ?><?= $host !== '' ? ' (' . $this->e($host) . ')' : '' ?></div><div class="s">Freigegeben am <?= $this->e($fmtDb((string) $c['since'], $tz)) ?> · <?= $scopeText((string) $c['scope']) ?> · zuletzt erneuert <?= $this->e($fmtDb((string) $c['last_used'], $tz)) ?></div></div>
        <form method="post" action="/einstellungen"><input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>"><input type="hidden" name="action" value="widerrufen"><input type="hidden" name="client_id" value="<?= $this->e($c['client_id']) ?>"><button class="btn btn-ghost danger-text" type="submit">Widerrufen</button></form></div>
<?php endforeach ?>
      <div class="list-item"><div><div class="t">Statisches Token (Fallback)</div><div class="s">Für Claude Desktop, nur aktiv wenn in der <span class="mono">.env</span> eingeschaltet</div></div><span class="badge badge-<?= $staticToken ? 'warning' : 'neutral' ?>"><?= $staticToken ? 'an' : 'aus' ?></span></div>
    </div>
  </section>

  <p class="hint">Audit-Log und Wissensbasis liegen in Datenbank bzw. Repo, nicht in der App.</p>
</div>
