<?php
/** @var \Training\View\View $this */
/** @var ?array $alert */
/** @var string $monday */
/** @var string $today */
/** @var ?array $week */
/** @var array<string, array> $days */
use Training\Dates;
use Training\View\Labels;
?>
  <div class="week-nav">
    <a class="btn btn-icon" href="/woche?start=<?= $prev ?>" aria-label="Vorherige Woche"><?= $this->icon('chevron-left', 'ic ic-lg') ?></a>
    <div class="title">
      <h1><?= $this->e(Dates::weekRange($monday)) ?></h1>
      <div class="muted">KW <?= Dates::isoWeek($monday) ?><?php if ($week !== null): ?> · Block <?= (int) $week['block_no'] ?> „<?= $this->e($week['block_name']) ?>“, Woche <?= (int) $week['week_no'] ?> von <?= (int) $week['week_count'] ?><?php endif ?></div>
    </div>
    <a class="btn btn-icon" href="/woche?start=<?= $next ?>" aria-label="Nächste Woche"><?= $this->icon('chevron-right', 'ic ic-lg') ?></a>
  </div>
<?php if ($monday !== Dates::monday($today)): ?>
  <p class="center small mt-8"><a href="/woche">Zur aktuellen Woche</a></p>
<?php endif ?>
<?php
// Begründung der Woche (AP-13, E-11): Kurzsatz sichtbar, ausführlicher Text hinter „mehr“ (ohne JavaScript)
$kurz = trim((string) ($week['focus'] ?? ''));
$mehr = trim((string) ($week['coach_notes'] ?? ''));
if ($kurz !== '' || $mehr !== ''): ?>
  <div class="card begruendung mt-4">
<?php if ($kurz !== ''): ?>
    <p class="kurz"><?= $this->e($kurz) ?></p>
<?php endif ?>
<?php if ($mehr !== ''): ?>
    <details class="more mehr"><summary><?= $this->icon('chevron-right', 'ic ic-sm') ?><?= $kurz !== '' ? 'mehr' : 'Begründung der Woche' ?></summary><p><?= $this->e($mehr) ?></p></details>
<?php endif ?>
  </div>
<?php endif ?>

<?php if ($alert !== null): ?>
  <div class="mt-4"><?php include __DIR__ . '/_alert.php'; ?></div>
<?php endif ?>

<?php if (!empty($mailError)): ?>
  <div class="alert alert-error mt-4"><?= $this->icon('alert-circle') ?><div><b>Backup per E-Mail fehlgeschlagen.</b> <span class="body"><?= $this->e($mailError) ?> Details unter <a href="/einstellungen">Einstellungen</a>.</span></div></div>
<?php endif ?>
<?php if (!empty($morning)): ?>
  <div class="mt-4<?= $morning['summary'] === null ? ' week-checkin' : '' ?>">
<?php if ($morning['summary'] !== null):
    $summary = $morning['summary']; $editHref = '/checkin'; include __DIR__ . '/_morning_summary.php';
else: ?>
  <div class="section-title"><h2>Morgen-Check-in</h2><span class="hint">vor dem Frühstück</span></div>
<?php extract($morning['form'], EXTR_OVERWRITE); include __DIR__ . '/_checkin_form.php'; ?>
<?php endif ?>
  </div>
<?php endif ?>
<?php if ($hasSessions): ?>
  <div class="week-sum">
    <div class="stat"><div class="l">sRPE bisher</div><div class="v"><?= number_format($srpe, 0, ',', ' ') ?></div></div>
    <div class="stat"><div class="l">Einheiten</div><div class="v"><?= $done ?> <small>von <?= $total ?> erledigt</small></div></div>
    <div class="stat"><div class="l">Check-in</div><div class="v"><?= $checkinCount ?> <small>von <?= $elapsed ?> Tagen</small></div></div>
  </div>

<?php foreach ($openFeedback as $s): ?>
  <div class="alert alert-hint mt-4">
    <?= $this->icon('note') ?>
    <div><b>Feedback offen.</b> <span class="body"><?= $this->e($s['title']) ?> (<?= $this->e(Dates::WEEKDAYS[Dates::weekdayIndex($s['date'])]) ?>) ist erledigt, aber ohne Rückmeldung. <a href="/einheit?id=<?= (int) $s['id'] ?>">Jetzt nachtragen</a></span></div>
  </div>
<?php endforeach ?>
<?php if (!empty($intervalsError)): ?>
  <div class="alert alert-info mt-4"><?= $this->icon('plug-connected') ?><div><b>Intervals.icu nicht erreichbar.</b> <span class="body">Aktivitäten werden gerade nicht angezeigt.</span></div></div>
<?php endif ?>

  <div class="days grid7 mt-4">
<?php foreach ($days as $date => $day):
    $isToday = $date === $today;
    $c = $day['checkin'];
?>
    <section class="day<?= $isToday ? ' today' : '' ?>">
      <div class="day-head"><div class="d"><?= Dates::WEEKDAYS[Dates::weekdayIndex($date)] ?><small><?= (new DateTimeImmutable($date))->format('d.m.') ?><?= $isToday ? ' · heute' : '' ?></small></div>
<?php if ($c !== null): ?>
        <a class="checkin done" href="/checkin?datum=<?= $date ?>"><?= $this->icon('circle-check', 'ic ic-sm') ?>Check-in</a>
<?php elseif ($date <= $today): ?>
        <a class="checkin open" href="/checkin?datum=<?= $date ?>"><?= $this->icon('circle-dashed', 'ic ic-sm') ?><?= $isToday ? 'Check-in offen' : 'Check-in fehlt' ?></a>
<?php else: ?>
        <span class="checkin none">–</span>
<?php endif ?>
      </div>
<?php if ($day['sessions'] === []): ?>
      <div class="rest"><?= $this->icon('circle-dashed') ?>Keine Einheit</div>
<?php endif ?>
<?php foreach ($day['sessions'] as $s):
    if ($s['type'] === 'ruhe'): ?>
      <div class="rest"><?= $this->icon('zzz') ?><?= $this->e($s['title'] !== '' ? $s['title'] : 'Ruhetag') ?></div>
<?php continue; endif;
    [$typeLabel, $typeIcon] = Labels::TYPES[$s['type']];
    $feedbackOpen = in_array($s['status'], ['erledigt', 'teilweise'], true) && $s['rpe_cr10'] === null;
    [$statusLabel, $statusClass, $statusIcon] = Labels::STATUS[$s['status']];
    $extra = [];
    if ($s['srpe_load'] !== null) { $extra[] = 'sRPE ' . (int) $s['srpe_load']; }
    elseif ($feedbackOpen) { $extra[] = 'Feedback offen'; }
    elseif ($s['type'] === 'ausdauer' && $s['activity'] !== null) { $extra[] = 'Aktivität vorhanden'; }
    elseif ($s['type'] === 'ausdauer' && $s['intervals_event_id'] !== null) { $extra[] = 'auf der Uhr'; }
    $duration = $s['duration_min'] ?? $s['planned_duration_min'];
?>
      <a class="session<?= in_array($s['status'], ['erledigt', 'teilweise'], true) ? ' done' : '' ?>" href="/einheit?id=<?= (int) $s['id'] ?>">
        <div class="tile"><?= $this->icon($typeIcon) ?></div>
        <div class="grow"><div class="t"><?= $this->e($s['title']) ?><span class="prio"><?= $this->e($s['priority']) ?></span></div><div class="m"><?php if ($duration !== null): ?><span><?= (int) $duration ?> min</span><?php endif ?><span class="typ"> · <?= $typeLabel ?></span><?php foreach ($extra as $x): ?><span class="extra"> · <?= $this->e($x) ?></span><?php endforeach ?></div></div>
<?php if ($feedbackOpen): ?>
        <span class="badge badge-hint status"><?= $this->icon('note') ?>Feedback</span>
<?php else: ?>
        <span class="badge badge-<?= $statusClass ?> status"><?= $statusIcon !== null ? $this->icon($statusIcon) : '' ?><?= $statusLabel ?></span>
<?php endif ?>
        <?= $this->icon('chevron-right', 'ic chev') ?>
      </a>
<?php endforeach ?>
    </section>
<?php endforeach ?>
  </div>
<?php else: ?>
  <div class="card mt-4">
    <div class="empty">
      <?= $this->icon('calendar') ?>
      <div><b>Noch kein Plan für diese Woche.</b></div>
      <div class="small">Der Wochenplan entsteht im Projekt-Chat und wird nach Deiner Bestätigung hier eingetragen. Check-ins kannst Du trotzdem erfassen.</div>
      <a class="btn btn-secondary" href="/checkin">Check-in für heute</a>
    </div>
  </div>
<?php endif ?>
<?php if (!empty($prefetch)): ?>
<div id="offline-prefetch" data-urls="<?= $this->e(json_encode($prefetch, JSON_UNESCAPED_SLASHES)) ?>" hidden></div>
<?php endif ?>
