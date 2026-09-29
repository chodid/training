<?php
/**
 * S11 Blockseite (AP-15, docs/konzept/blockbilanz.md T5, E-18): nur lesen. Kopf mit Fälligkeiten, Zielklärung (Abschnitte
 * des Schemas als Karten, Entscheidungen als Tabelle mit verworfenen Alternativen), Revisionen als Zeitleiste, Bilanz
 * (Ziel/Soll/Ist/Bewertung, Kennzahlen kompakt), Fassungen je Datensatz aufklappbar, Liste der übrigen Blöcke.
 */
/** @var \Training\View\View $this */
/** @var ?array $block */
/** @var ?string $rest */
/** @var list<array> $faellig */
/** @var ?array $zielklaerung */
/** @var ?array $bilanz */
/** @var list<array> $revisionen */
/** @var array<string, list<array>> $fassungen */
/** @var list<array> $others */
use Training\Review\Faelligkeit;
use Training\View\Labels;

$d = static fn (?string $date): string => $date === null || $date === '' ? '–' : (new DateTimeImmutable($date))->format('d.m.Y');
$statusBadge = function (array $r): string {
    $html = $r['status'] === 'entwurf'
        ? '<span class="badge badge-warning">Entwurf – noch nicht bestätigt</span>'
        : '<span class="badge badge-success">bestätigt</span>';
    if (isset($r['entwurf'])) {
        $html .= ' <span class="badge badge-hint">neuer Entwurf v' . (int) $r['entwurf']['version'] . '</span>';
    }

    return $html;
};
$versions = function (string $key) use ($fassungen, $d): void {
    $list = $fassungen[$key] ?? [];
    if ($list === []) {
        return;
    }
    $last = end($list);
    echo '<details class="more"><summary>' . $this->icon('chevron-right', 'ic ic-sm') . 'Fassungen <span class="hint">' . count($list) . ', zuletzt ' . $this->e($d(substr((string) $last['created_at'], 0, 10))) . '</span></summary><div class="list">';
    foreach (array_reverse($list) as $v) {
        echo '<div class="list-item"><div><span class="t">Fassung ' . (int) $v['version'] . ' · ' . $this->e(Labels::REVIEW_STATUS[$v['status']]) . '</span><div class="s">'
            . $this->e($v['reason'] ?? 'erste Fassung') . '</div></div><span class="hint">' . $this->e($d($v['review_date'])) . '</span></div>';
    }
    echo '</div></details>';
};
$items = function (array $list, string $class = 'bullet'): string {
    if ($list === []) {
        return '<p class="hint">–</p>';
    }
    $out = '<ul class="' . $class . '">';
    foreach ($list as $x) {
        $out .= '<li>' . $this->e($x) . '</li>';
    }

    return $out . '</ul>';
};
?>
<div class="stack-lg block-page">
<?php if ($block === null): ?>
  <div class="page-head"><h1>Blöcke</h1></div>
  <div class="card"><div class="empty"><?= $this->icon('mountain') ?><div><b>Noch kein Trainingsblock.</b></div><div class="small">Blöcke, Zielklärung und Bilanz entstehen im Projekt-Chat.</div></div></div>
<?php else: [$stLabel, $stClass] = Labels::BLOCK_STATUS[$block['status']]; ?>
  <div class="page-head">
    <div class="eyebrow">Block · <span class="badge badge-<?= $stClass ?>"><?= $this->e($stLabel) ?></span></div>
    <h1><?= $this->e($block['name']) ?></h1>
    <p class="muted"><?= $this->e($d($block['start_date'])) ?> – <?= $this->e($d($block['end_date'])) ?> · <?= $this->e((string) $rest) ?></p>
  </div>

<?php foreach ($faellig as $f): ?>
  <div class="alert alert-warning"><?= $this->icon('alert-triangle') ?><div><b><?= $this->e(Labels::REVIEW_KINDS[$f['kind']]) ?> fällig.</b> <span class="body"><?= $this->e(Faelligkeit::text($f)) ?> Im Projekt-Chat erstellen.</span></div></div>
<?php endforeach ?>

  <section id="zielklaerung" class="stack">
    <div class="section-title"><h2>Zielklärung</h2><?php if ($zielklaerung !== null): ?><span class="hint"><?= $this->e($d($zielklaerung['review_date'])) ?> · Fassung <?= (int) $zielklaerung['version'] ?></span><?php endif ?></div>
<?php if ($zielklaerung === null): ?>
    <div class="card"><p class="muted">Noch keine Zielklärung für diesen Block.</p></div>
<?php else: $z = $zielklaerung['content']; $a = $z['ausgangslage']; ?>
    <div class="card stack">
      <div class="card-head"><h3 class="h-card"><?= $this->e(Labels::PHASES[$z['phase']] ?? $z['phase']) ?></h3><span><?= $statusBadge($zielklaerung) ?></span></div>
      <p class="kurz"><?= $this->e($zielklaerung['summary']) ?></p>
      <p><?= $this->e($z['phase_text']) ?></p>
      <p class="small">Prioritäten: <?php foreach (['t1' => 'Ausdauer', 't2' => 'Kraft/Haltung', 't3' => 'Klettern'] as $k => $l): ?><span class="badge badge-<?= $z['prioritaeten'][$k] === 'A' ? 'brand' : 'neutral' ?>"><?= $l ?> <?= $this->e($z['prioritaeten'][$k]) ?></span> <?php endforeach ?></p>
    </div>
    <div class="card"><div class="card-head"><h3 class="h-card">Ausgangslage</h3></div>
      <dl class="kv">
        <dt>Zeitbudget</dt><dd><?= $this->e($a['zeitbudget']) ?></dd>
<?php foreach (['umstaende' => 'Umstände', 'einschraenkungen' => 'Einschränkungen', 'ausruestung' => 'Ausrüstung'] as $k => $l): if (!empty($a[$k])): ?>
        <dt><?= $l ?></dt><dd><?= $this->e($a[$k]) ?></dd>
<?php endif; endforeach ?>
      </dl>
    </div>
    <div class="card"><div class="card-head"><h3 class="h-card">Ziele</h3></div>
      <div class="list">
<?php foreach ($z['ziele'] as $g): ?>
        <div class="list-item ziel"><div><div class="t"><span class="mono"><?= $this->e($g['id']) ?></span> <?= $this->e($g['ziel']) ?></div><div class="s"><?= $this->e(Labels::BEREICH[$g['bereich']] ?? $g['bereich']) ?> · Messgröße: <?= $this->e($g['messgroesse']) ?> · erreicht, wenn <?= $this->e($g['kriterium']) ?><?= !empty($g['termin']) ? ' · bis ' . $this->e($d($g['termin'])) : '' ?></div></div></div>
<?php endforeach ?>
      </div>
    </div>
<?php if (!empty($z['zielevents'])): ?>
    <div class="card"><div class="card-head"><h3 class="h-card">Zielevents</h3></div>
      <?= $items(array_map(static fn (array $e): string => $d($e['datum']) . ' – ' . $e['name'] . (!empty($e['art']) ? ' (' . $e['art'] . ')' : ''), $z['zielevents'])) ?>
    </div>
<?php endif ?>
    <div class="card"><div class="card-head"><h3 class="h-card">Entscheidungen</h3><span class="hint">mit verworfenen Alternativen</span></div>
      <div class="table-scroll">
        <table class="plan-table table-wide entscheidungen">
          <thead><tr><th>Thema</th><th>Entscheidung</th><th>Begründung</th><th>Verworfen</th></tr></thead>
          <tbody>
<?php foreach ($z['entscheidungen'] as $e): ?>
            <tr><td><?= $this->e($e['thema']) ?></td><td><?= $this->e($e['entscheidung']) ?><?= !empty($e['quelle']) ? '<div class="hint">' . $this->e($e['quelle']) . '</div>' : '' ?></td><td class="pre-line"><?= $this->e($e['rationale']) ?></td><td><?= $items($e['verworfen']) ?></td></tr>
<?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card"><div class="card-head"><h3 class="h-card">Risiken und Abbruchkriterien</h3></div>
      <?= $items(array_map(static fn (array $r): string => $r['risiko'] . ' → ' . $r['regel'], $z['risiken'])) ?>
    </div>
    <div class="card"><div class="card-head"><h3 class="h-card">Ableitung für den Block</h3><span class="hint"><?= (int) $z['block']['dauer_wochen'] ?> Wochen</span></div>
<?php if (!empty($z['block']['phasen'])): ?>
      <?= $items(array_map(static fn (array $p): string => 'Wochen ' . $p['wochen'] . ': ' . $p['name'] . ' – ' . $p['fokus'], $z['block']['phasen'])) ?>
<?php endif ?>
<?php if (!empty($z['block']['tests_start'])): ?>
      <p class="small mt-8"><b>Ausgangstests:</b> <?= $this->e(implode(' · ', $z['block']['tests_start'])) ?></p>
<?php endif ?>
    </div>
<?php if (!empty($z['offene_fragen'])): ?>
    <div class="card"><div class="card-head"><h3 class="h-card">Offene Fragen</h3></div><?= $items($z['offene_fragen']) ?></div>
<?php endif ?>
<?php $versions('zielklaerung-1'); ?>
<?php endif ?>
  </section>

  <section id="revisionen" class="stack">
    <div class="section-title"><h2>Revisionen</h2><span class="hint">Belastungssteuerung alle 3–4 Wochen</span></div>
<?php if ($revisionen === []): ?>
    <div class="card"><p class="muted">Noch keine Revision in diesem Block.</p></div>
<?php endif ?>
    <ol class="timeline">
<?php foreach ($revisionen as $r): $c = $r['content']; ?>
      <li class="card stack">
        <div class="card-head"><h3 class="h-card"><?= $this->e($d($r['review_date'])) ?> · <?= $this->e(Labels::ANLASS[$c['anlass']] ?? $c['anlass']) ?></h3><span><?= $statusBadge($r) ?></span></div>
        <p class="kurz"><?= $this->e($r['summary']) ?></p>
        <p><?= $this->e($c['befund']) ?></p>
        <?= $items(array_map(static fn (array $x): string => $x['was'] . ' – ' . $x['warum'] . (!empty($x['bis']) ? ' (bis ' . $d($x['bis']) . ')' : ''), $c['aenderungen'])) ?>
<?php if (!empty($c['wirkung_pruefen'])): ?>
        <p class="small"><b>Wirkung prüfen:</b> <?= $this->e($c['wirkung_pruefen']) ?></p>
<?php endif ?>
<?php $versions('revision-' . $r['sequence']); ?>
      </li>
<?php endforeach ?>
    </ol>
  </section>

  <section id="bilanz" class="stack">
    <div class="section-title"><h2>Blockbilanz</h2><?php if ($bilanz !== null): ?><span class="hint"><?= $this->e($d($bilanz['review_date'])) ?> · Fassung <?= (int) $bilanz['version'] ?></span><?php endif ?></div>
<?php if ($bilanz === null): ?>
    <div class="card"><p class="muted">Noch keine Bilanz – sie entsteht am Blockende im Projekt-Chat.</p></div>
<?php else: $b = $bilanz['content']; $k = $bilanz['kennzahlen']; ?>
    <div class="card stack">
      <div class="card-head"><h3 class="h-card"><?= $this->e($d($b['zeitraum']['von'])) ?> – <?= $this->e($d($b['zeitraum']['bis'])) ?></h3><span><?= $statusBadge($bilanz) ?></span></div>
      <p class="kurz"><?= $this->e($bilanz['summary']) ?></p>
      <div class="table-scroll">
        <table class="plan-table table-wide bilanz-ziele">
          <thead><tr><th>Ziel</th><th>Soll</th><th>Ist</th><th>Bewertung</th></tr></thead>
          <tbody>
<?php foreach ($b['ziele'] as $g): [$bl, $bc] = Labels::BEWERTUNG[$g['bewertung']]; ?>
            <tr><td><?= !empty($g['ziel_id']) ? '<span class="mono">' . $this->e($g['ziel_id']) . '</span> ' : '' ?><?= $this->e($g['ziel']) ?></td><td><?= $this->e($g['soll']) ?></td><td><?= $this->e($g['ist']) ?></td><td><span class="badge badge-<?= $bc ?>"><?= $this->e($bl) ?></span><div class="hint"><?= $this->e($g['grund']) ?></div></td></tr>
<?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="two-col">
      <div class="card"><div class="card-head"><h3 class="h-card">Gelungen</h3></div><?= $items($b['gelungen']) ?></div>
      <div class="card"><div class="card-head"><h3 class="h-card">Nicht gelungen</h3></div><?= $items($b['nicht_gelungen']) ?></div>
    </div>
<?php if (!empty($b['tests'])): ?>
    <div class="card"><div class="card-head"><h3 class="h-card">Tests</h3></div>
      <?= $items(array_map(static fn (array $t): string => $t['name'] . ': ' . ($t['start'] ?? '–') . ' → ' . ($t['ende'] ?? '–') . (!empty($t['bewertung']) ? ' (' . $t['bewertung'] . ')' : ''), $b['tests'])) ?>
    </div>
<?php endif ?>
<?php if (!empty($b['annahmen_geaendert'])): ?>
    <div class="card"><div class="card-head"><h3 class="h-card">Geänderte Annahmen</h3></div>
      <?= $items(array_map(static fn (array $x): string => $x['was'] . ' → ' . $x['neu'] . ' (' . $x['begruendung'] . ')' . ($x['vorschlag_trainerregel'] ? ' · Vorschlag Trainerregel' : ''), $b['annahmen_geaendert'])) ?>
    </div>
<?php endif ?>
    <div class="card"><div class="card-head"><h3 class="h-card">Empfehlung</h3></div><p class="pre-line"><?= $this->e($b['empfehlung']) ?></p>
<?php if (!empty($b['offene_fragen'])): ?>
      <p class="small mt-8"><b>Offene Fragen:</b></p><?= $items($b['offene_fragen']) ?>
<?php endif ?>
    </div>
<?php if (is_array($k)): ?>
    <div class="card"><div class="card-head"><h3 class="h-card">Kennzahlen</h3><span class="hint">vom Server eingefroren, <?= (int) $k['wochen'] ?> Wochen</span></div>
      <dl class="kv">
        <dt>Erledigt</dt><dd><?= $this->e(implode(' · ', array_map(static fn (string $t, array $p): string => (Labels::TYPES[$t][0] ?? $t) . ' ' . ($p['erledigt'] + $p['teilweise']) . '/' . $p['geplant'], array_keys(array_filter($k['plan_erfuellung'], static fn (array $p): bool => $p['geplant'] > 0)), array_filter($k['plan_erfuellung'], static fn (array $p): bool => $p['geplant'] > 0))) ?: '–') ?></dd>
        <dt>sRPE</dt><dd><?= number_format((int) $k['last']['srpe_summe'], 0, ',', ' ') ?> gesamt · je Woche <?= $this->e(implode(', ', $k['last']['srpe_je_woche'])) ?></dd>
<?php if (is_array($k['ausdauer'])): ?>
        <dt>Ausdauer</dt><dd>km je Woche <?= $this->e(implode(', ', array_map(static fn ($v): string => number_format((float) $v, 1, ',', ''), $k['ausdauer']['km_je_woche']))) ?> · Hm <?= $this->e(implode(', ', $k['ausdauer']['hm_je_woche'])) ?></dd>
<?php endif ?>
        <dt>Schmerz</dt><dd><?= $k['schmerz']['je_ort'] === [] ? 'keine Meldung' : $this->e(implode(' · ', array_map(static fn (array $p): string => (Labels::LOCATIONS[$p['ort']] ?? $p['ort']) . ' max ' . $p['max'] . ', ' . $p['anzahl'] . '×, ' . $p['trend'], $k['schmerz']['je_ort']))) ?></dd>
        <dt>Morgentest</dt><dd>links <?= $this->e($k['morgentest']['links_mittel'] ?? '–') ?> · rechts <?= $this->e($k['morgentest']['rechts_mittel'] ?? '–') ?> · rote Tage <?= (int) $k['morgentest']['rot_tage'] ?></dd>
        <dt>Check-in</dt><dd><?= (int) $k['checkin_abdeckung_prozent'] ?> % der Tage</dd>
      </dl>
    </div>
<?php endif ?>
<?php $versions('bilanz-1'); ?>
<?php endif ?>
  </section>
<?php endif ?>

<?php if ($others !== []): ?>
  <section id="weitere">
    <div class="section-title"><h2><?= $block !== null ? 'Weitere Blöcke' : 'Alle Blöcke' ?></h2></div>
    <div class="card list">
<?php foreach ($others as $o): [$oLabel, $oClass] = Labels::BLOCK_STATUS[$o['status']]; ?>
      <a class="list-item link-row" href="/block?id=<?= (int) $o['id'] ?>"><div><div class="t"><?= $this->e($o['name']) ?></div><div class="s"><?= $this->e($d($o['start_date'])) ?> – <?= $this->e($d($o['end_date'])) ?></div></div><span class="badge badge-<?= $oClass ?>"><?= $this->e($oLabel) ?></span></a>
<?php endforeach ?>
    </div>
  </section>
<?php endif ?>
</div>
