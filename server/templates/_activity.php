<?php
/** Verknüpfte Aktivität aus Intervals.icu (S3 Ausdauer) */
/** @var \Training\View\View $this */
/** @var ?array $activity */
/** @var string $athleteId */
if ($activity === null): ?>
      <section class="card">
        <div class="card-head"><h2>Verknüpfte Aktivität</h2></div>
        <p class="muted small">Noch keine Aktivität in Intervals.icu zu diesem Tag gefunden.</p>
      </section>
<?php return; endif;
$mt = (int) ($activity['moving_time'] ?? 0);
$time = $mt > 0 ? ($mt >= 3600 ? sprintf('%d:%02d:%02d', intdiv($mt, 3600), intdiv($mt % 3600, 60), $mt % 60) : sprintf('%d:%02d', intdiv($mt, 60), $mt % 60)) : '–';
$dist = isset($activity['distance']) ? number_format($activity['distance'] / 1000, 1, ',', '') : null;
$hr = $activity['average_heartrate'] ?? null;
$speed = (float) ($activity['average_speed'] ?? 0);
$pace = $speed > 0 ? sprintf('%d:%02d', intdiv((int) round(1000 / $speed), 60), (int) round(1000 / $speed) % 60) : null;
$zones = is_array($activity['icu_hr_zone_times'] ?? null) ? array_slice(array_map('intval', $activity['icu_hr_zone_times']), 0, 5) : [];
$zoneTotal = array_sum($zones);
$start = (string) ($activity['start_date_local'] ?? '');
$startLabel = $start !== '' ? (new DateTimeImmutable($start))->format('d.m., H:i') . ' Uhr' : '';
?>
      <section class="card">
        <div class="card-head"><h2>Verknüpfte Aktivität</h2><?php if (isset($activity['id'])): ?><a class="small link-inline" href="https://intervals.icu/activities/<?= $this->e(rawurlencode((string) $activity['id'])) ?>" rel="noopener" target="_blank">Intervals.icu <?= $this->icon('external-link', 'ic ic-sm') ?></a><?php endif ?></div>
        <div class="stack">
          <div class="between"><b><?= $this->e(($activity['name'] ?? $activity['type'] ?? 'Aktivität') . ($startLabel !== '' ? ' · ' . $startLabel : '')) ?></b><span class="badge badge-<?= $activity['match'] === 'intervals' ? 'success' : 'info' ?>"><?= $this->icon('check') ?><?= $activity['match'] === 'intervals' ? 'zugeordnet' : 'gleicher Tag' ?></span></div>
          <div class="activity">
            <div class="stat"><div class="l">Dauer</div><div class="v mono"><?= $this->e($time) ?></div></div>
<?php if ($dist !== null): ?>            <div class="stat"><div class="l">Distanz</div><div class="v mono"><?= $this->e($dist) ?> <small>km</small></div></div><?php endif ?>
<?php if ($hr !== null): ?>            <div class="stat"><div class="l">Ø Herzfrequenz</div><div class="v mono"><?= (int) round((float) $hr) ?></div></div><?php endif ?>
<?php if ($pace !== null): ?>            <div class="stat"><div class="l">Ø Pace</div><div class="v mono"><?= $this->e($pace) ?> <small>/km</small></div></div><?php endif ?>
          </div>
<?php if ($zoneTotal > 0): ?>
          <div>
            <div class="small muted">Zeit in Zonen</div>
            <svg class="zones-svg mt-8" viewBox="0 0 1000 10" preserveAspectRatio="none" role="img" aria-label="Zeit in Herzfrequenzzonen">
<?php $x = 0.0; foreach ($zones as $i => $sec): $w = 1000 * $sec / $zoneTotal; if ($w <= 0) { continue; } ?>
              <rect class="z<?= $i + 1 ?>" x="<?= round($x, 1) ?>" y="0" width="<?= round(max(0, $w - 2), 1) ?>" height="10"><title>Zone <?= $i + 1 ?>: <?= intdiv($sec, 60) ?> min</title></rect>
<?php $x += $w; endforeach ?>
            </svg>
            <div class="legend mt-8"><?php foreach ($zones as $i => $sec): if ($sec <= 0) { continue; } ?><span><i class="z<?= $i + 1 ?>"></i>Z<?= $i + 1 ?> <?= intdiv($sec, 60) ?> min</span><?php endforeach ?></div>
          </div>
<?php endif ?>
        </div>
      </section>
