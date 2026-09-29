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
  <div class="alert alert-warning"><?= $this->icon('alert-triangle') ?><div><b>Update erforderlich.</b> <span class="body">Der Code erwartet Schemastand <?= $schemaCode ?>, die Datenbank steht auf <?= $schemaDb ?>. Bis zur Migration sind alle Schreibzugriffe (Web und MCP) gesperrt. Vor der Migration wird automatisch ein Backup angelegt.</span></div></div>
<?php endif ?>

  <section>
    <div class="section-title"><h2>Athletenprofil</h2></div>
    <div class="card list">
      <div class="list-item"><div><div class="t">Grundlage für die Planung</div><div class="s"><?= (int) $profile['filled'] ?> von <?= (int) $profile['total'] ?> Abschnitten ausgefüllt<?= $profile['last'] !== null ? ' · zuletzt geändert ' . $this->e($fmtDb((string) $profile['last'], $tz)) : '' ?></div></div>
        <a class="btn btn-ghost" href="/profil"><?= $this->icon('user') ?>Öffnen</a></div>
<?php if ($catalog !== null): ?>
      <div class="list-item"><div><div class="t">Übungskatalog</div><div class="s"><?= (int) $catalog['count'] ?> <?= $catalog['count'] === 1 ? 'Übung' : 'Übungen' ?> mit Ausführung, Fehlerquellen und Videos</div>
<?php if ($catalog['links_pruefen'] > 0): ?>
        <span class="badge badge-warning mt-8"><?= $this->icon('alert-triangle') ?><?= (int) $catalog['links_pruefen'] ?> <?= $catalog['links_pruefen'] === 1 ? 'Übung' : 'Übungen' ?> mit defekten Links</span>
<?php endif ?>
        </div>
        <a class="btn btn-ghost" href="/uebungen"><?= $this->icon('book') ?>Öffnen</a></div>
<?php endif ?>
    </div>
  </section>

  <section>
    <div class="section-title"><h2>Konto</h2></div>
    <div class="card list">
      <div class="list-item"><div><div class="t">Angemeldet als <?= $this->e($login) ?></div><div class="s">Seit <?= $this->e($fmt($since, $tz)) ?> auf diesem Gerät · Sitzung endet nach 30 Tagen ohne Nutzung</div></div>
        <form method="post" action="/logout"><input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>"><button class="btn btn-secondary" type="submit"><?= $this->icon('logout') ?>Abmelden</button></form></div>
      <div class="list-item"><div><div class="t">Morgen-Check-in</div><div class="s">„Hand rechts“ abfragen bis <?= $this->e((new DateTimeImmutable($handBis))->format('d.m.Y')) ?></div></div><a class="btn btn-ghost" href="/einstellungen?bereich=checkin">Ändern</a></div>
      <div class="list-item"><div><div class="t">Zeitzone</div><div class="s mono"><?= $this->e($tz) ?></div></div><a class="btn btn-ghost" href="/einstellungen?bereich=zeitzone">Ändern</a></div>
      <div class="list-item"><div><div class="t">Passwort</div><div class="s">Mindestens 12 Zeichen</div></div><a class="btn btn-ghost" href="/einstellungen?bereich=passwort">Ändern</a></div>
<?php foreach ($passkeyList as $pk): ?>
      <div class="list-item"><div><div class="t">Passkey „<?= $this->e($pk['name']) ?>“</div><div class="s">Angelegt <?= $this->e($fmtDb((string) $pk['created_at'], $tz)) ?><?= $pk['last_used_at'] !== null ? ' · zuletzt genutzt ' . $this->e($fmtDb((string) $pk['last_used_at'], $tz)) : '' ?></div></div>
        <form method="post" action="/einstellungen"><input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>"><input type="hidden" name="action" value="passkey_loeschen"><input type="hidden" name="passkey_id" value="<?= $this->e($pk['id']) ?>"><button class="btn btn-ghost danger-text" type="submit">Entfernen</button></form></div>
<?php endforeach ?>
      <div class="list-item"><div class="stack"><div><div class="t">Passkey hinzufügen</div><div class="s">Anmelden mit Fingerabdruck, Gesicht oder Geräte-PIN; das Passwort bleibt als Rückfallweg.</div></div>
        <input class="input" id="passkey-name" maxlength="100" placeholder="Name, z. B. iPhone" aria-label="Name des Passkeys">
        <div class="alert alert-error" id="passkey-error" role="alert" hidden></div></div>
        <button class="btn btn-secondary" type="button" data-passkey="register" data-csrf="<?= $this->e($csrf) ?>" hidden><?= $this->icon('key') ?>Hinzufügen</button></div>
    </div>
  </section>

  <section>
    <div class="section-title"><h2>Training</h2></div>
    <div class="card list">
      <form class="list-item timer-zeile" method="post" action="/einstellungen" data-timer-ton="<?= $timerTon ? 'an' : 'aus' ?>">
        <input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>">
        <input type="hidden" name="action" value="timer">
        <div><div class="t">Timer-Signale</div><div class="s">Ton und Vibration im geführten Modus (Start, 30 s, 10 s, 3-2-1) · in der Einheit jederzeit umschaltbar</div></div>
        <div class="row timer-ton">
          <div class="seg" role="radiogroup" aria-label="Timer-Signale">
<?php foreach (['an' => ['An', 'volume'], 'aus' => ['Aus', 'volume-off']] as $v => [$label, $ic]): ?>
            <label><input type="radio" name="timer_ton" value="<?= $v ?>"<?= ($timerTon ? 'an' : 'aus') === $v ? ' checked' : '' ?>><?= $this->icon($ic, 'ic ic-sm') ?><span><?= $label ?></span></label>
<?php endforeach ?>
          </div>
          <button class="btn btn-ghost" type="submit">Speichern</button>
        </div>
      </form>
<?php if (!empty($blockReview)): ?>
      <div class="list-item"><div><div class="t">Blockbilanz und Zielklärung</div><div class="s">Erinnerung <?= $blockReview['overlay'] ? 'an' : 'aus' ?> · Vorlauf <?= (int) $blockReview['bilanz'] ?>/<?= (int) $blockReview['zielklaerung'] ?> Tage · Termin <?= $this->e($blockReview['beginn']) ?> Uhr, <?= (int) $blockReview['dauer_min'] ?> min, Erinnerung <?= (int) $blockReview['erinnerung_h'] ?> h vorher</div></div><a class="btn btn-ghost" href="/einstellungen?bereich=blockreview">Ändern</a></div>
<?php endif ?>
    </div>
  </section>

  <section>
    <div class="section-title"><h2>Backup</h2><span class="hint">verschlüsselt, AES-256</span></div>
    <div class="card list">
      <div class="list-item"><div><div class="t">Backup herunterladen</div><div class="s">SQL-Dump, gzip, mit dem Backup-Passwort aus der <span class="mono">.env</span> verschlüsselt</div></div>
        <form method="post" action="/einstellungen"><input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>"><input type="hidden" name="action" value="backup"><button class="btn btn-primary" type="submit"><?= $this->icon('download') ?>Herunterladen</button></form></div>
<?php
$mailText = 'Nicht eingerichtet: BACKUP_MAIL_TO, CRON_SECRET und SMTP_* in der .env, Cronjob bei Lima-City';
$mailBadge = ['neutral', 'aus'];
if (!empty($mail['to']) && !empty($mail['cron'])) {
    $mailText = 'Alle ' . (int) $mail['interval'] . ' Tage an ' . $mail['to'];
    $mailBadge = ['neutral', 'wartet auf Cronjob'];
    if (!empty($mail['last_success'])) {
        $mailText .= ' · zuletzt ' . $fmtDb(gmdate('Y-m-d H:i:s', (int) $mail['last_success']), $tz) . ' · ' . number_format(((int) ($mail['last_size'] ?? 0)) / 1024, 0, ',', '.') . ' KB';
        $mailBadge = ['success', 'zugestellt'];
    }
    if (!empty($mail['error'])) {
        $mailText .= ' · Fehler: ' . $mail['error'];
        $mailBadge = ['error', 'fehlgeschlagen'];
    }
}
?>
      <div class="list-item"><div><div class="t">Daten exportieren (JSON)</div><div class="s">Alle Trainingsdaten lesbar für andere Programme – <b>unverschlüsselt</b>, enthält Gesundheitsdaten</div></div>
        <form method="post" action="/einstellungen"><input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>"><input type="hidden" name="action" value="export"><button class="btn btn-secondary" type="submit"><?= $this->icon('download') ?>Exportieren</button></form></div>
      <div class="list-item"><div><div class="t">Backup per E-Mail</div><div class="s"><?= $this->e($mailText) ?></div></div><span class="badge badge-<?= $mailBadge[0] ?>"><?= $mailBadge[0] === 'success' ? $this->icon('check') : '' ?><?= $this->e($mailBadge[1]) ?></span></div>
      <div class="list-item"><div><div class="t">Vor Migrationen</div><div class="s">Automatisch, die letzten 5 werden aufbewahrt<?= $preMigration['last'] !== null ? ' · zuletzt ' . $this->e($preMigration['last']) : '' ?></div></div><span class="badge badge-neutral"><?= (int) $preMigration['count'] ?> Datei<?= (int) $preMigration['count'] === 1 ? '' : 'en' ?></span></div>
    </div>
  </section>

  <section>
    <div class="section-title"><h2>Update</h2></div>
    <div class="card list">
      <div class="list-item"><div><div class="t">Schemastand</div><div class="s">Code <?= $schemaCode ?> · Datenbank <?= $schemaDb ?? '?' ?><?= $schemaDb === $schemaCode ? ' · alles aktuell' : ' · Migration ausstehend' ?></div></div>
        <?php if ($schemaDb === $schemaCode): ?><span class="badge badge-success"><?= $this->icon('check') ?>aktuell</span><?php else: ?><span class="badge badge-warning">ausstehend</span><?php endif ?></div>
      <div class="list-item"><div><div class="t">Migration ausführen</div><div class="s">Legt zuerst ein Backup an, dann werden die ausstehenden Migrationen der Reihe nach eingespielt.</div></div>
        <form method="post" action="/einstellungen"><input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>"><input type="hidden" name="action" value="migrieren"><button class="btn btn-secondary" type="submit"<?= $schemaDb === $schemaCode ? ' disabled' : '' ?>><?= $this->icon('refresh') ?>Migrieren</button></form></div>
      <div class="list-item"><div><div class="t">Version</div><div class="s mono"><?= $this->e($version) ?></div></div></div>
    </div>
  </section>

  <section>
    <div class="section-title"><h2>Verbindungen</h2></div>
    <div class="card list">
      <div class="list-item"><div><div class="t">Intervals.icu</div><div class="s"><?= $intervals !== null ? 'Athlet ' . $this->e($intervals) . ' · <a href="/intervals">Verbindung prüfen</a>' : 'Nicht eingerichtet: INTERVALS_API_KEY und INTERVALS_ATHLETE_ID in der <span class="mono">.env</span>' ?></div></div>
        <?php if ($intervals !== null): ?><span class="badge badge-success"><?= $this->icon('plug-connected') ?>eingerichtet</span><?php else: ?><span class="badge badge-neutral">aus</span><?php endif ?></div>
<?php
$mirrorText = (int) $mirror['aktivitaeten'] . ' Aktivitäten, ' . (int) $mirror['wellness_tage'] . ' Wellness-Tage';
if (!empty($mirror['erste'])) { $mirrorText .= ' · ' . $mirror['erste'] . ' bis ' . $mirror['letzte']; }
if (!empty($mirror['last_success'])) { $mirrorText .= ' · Abgleich ' . $fmtDb(gmdate('Y-m-d H:i:s', (int) $mirror['last_success']), $tz); }
if (!empty($mirror['error'])) { $mirrorText .= ' · Fehler: ' . $mirror['error']; }
?>
      <div class="list-item"><div><div class="t">Spiegel Intervals.icu</div><div class="s"><?= $this->e($mirrorText) ?></div></div><span class="badge badge-<?= !empty($mirror['error']) ? 'error' : (!empty($mirror['last_success']) ? 'success' : 'neutral') ?>"><?= !empty($mirror['error']) ? 'Fehler' : (!empty($mirror['last_success']) ? 'aktiv' : 'kein Cronjob') ?></span></div>
<?php
$cal = $calendar;
if ($cal['host'] === null) {
    $calText = 'Nicht eingerichtet: CALDAV_URL, CALDAV_USER und CALDAV_PASSWORD (Nextcloud-App-Passwort) in der .env';
} elseif (!$cal['https']) {
    $calText = 'CALDAV_URL muss mit https:// beginnen – Kalender ist aus.';
} else {
    $calText = $cal['host'] . (!empty($cal['last_success']) ? ' · zuletzt übertragen ' . (new DateTimeImmutable('@' . (int) $cal['last_success']))->setTimezone(new DateTimeZone($tz))->format('d.m.Y, H:i') . ' Uhr' : ' · noch nichts übertragen');
    if (!empty($cal['last_error'])) { $calText .= ' · Fehler: ' . $cal['last_error']; }
}
?>
      <div class="list-item"><div><div class="t">Kalender (CalDAV)</div><div class="s"><?= $this->e($calText) ?></div></div>
<?php if ($cal['host'] === null || !$cal['https']): ?>
        <span class="badge badge-<?= $cal['host'] === null ? 'neutral' : 'error' ?>"><?= $cal['host'] === null ? 'aus' : 'Fehler' ?></span></div>
<?php else: ?>
        <form method="post" action="/einstellungen"><input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>"><input type="hidden" name="action" value="kalender"><button class="btn btn-secondary" type="submit"><?= $this->icon('refresh') ?>Abgleichen</button></form></div>
      <div class="list-item"><div><div class="t">Erinnerung im Kalender</div><div class="s"><?= $cal['reminder'] !== null ? 'Am Trainingstag um ' . $this->e($cal['reminder']) . ' Uhr (Tage mit geplanter oder verschobener Einheit)' : 'Aus' ?></div></div><a class="btn btn-ghost" href="/einstellungen?bereich=erinnerung">Ändern</a></div>
<?php endif ?>
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
<script src="/js/passkey.js" defer></script>
