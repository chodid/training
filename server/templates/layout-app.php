<?php
/** @var \Training\View\View $this */
/** @var string $title */
/** @var string $content */
/** @var string $nav aktiver Bereich: woche|checkin|verlauf|einstellungen */
/** @var string $login */
/** @var ?string $navFoot */
/** @var ?string $topAction HTML für die Kopfzeile (Tablet/Desktop) */
$items = [
    'woche' => ['/woche', 'calendar-week', 'Woche'],
    'checkin' => ['/checkin', 'checkup-list', 'Check-in'],
    'verlauf' => ['/verlauf', 'chart-line', 'Verlauf'],
    'einstellungen' => ['/einstellungen', 'settings', 'Einstellungen'],
];
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#7A5C94">
<title><?= $this->e($title) ?> – Training</title>
<link rel="icon" href="/assets/lama-kopf.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="stylesheet" href="/assets/ds/styles.css">
<link rel="stylesheet" href="/assets/app.css">
<link rel="stylesheet" href="/css/training.css">
</head>
<body class="app">
<header class="topbar">
<?php if (!empty($backHref)): ?>
  <a class="btn btn-icon" href="<?= $this->e($backHref) ?>" aria-label="<?= $this->e($backLabel ?? 'Zurück') ?>"><?= $this->icon('arrow-left', 'ic ic-lg') ?></a>
  <h1><?= $this->e($title) ?></h1>
<?php else: ?>
  <a class="brand" href="/woche"><img src="/assets/lama-kopf.svg" alt=""><span>Training</span></a>
  <h1 class="hide-mobile"><?= $this->e($title) ?></h1>
<?php endif ?>
  <div class="topbar-actions"><?= $topAction ?? '' ?></div>
</header>

<nav class="nav" aria-label="Hauptnavigation">
  <a class="nav-brand" href="/woche"><img src="/assets/lama-kopf.svg" alt=""><span class="brand"><span>Training</span></span></a>
<?php foreach ($items as $key => [$href, $icon, $label]): ?>
  <a href="<?= $href ?>"<?= ($nav ?? '') === $key ? ' aria-current="page"' : '' ?>><?= $this->icon($icon) ?><?= $label ?></a>
<?php endforeach ?>
  <div class="nav-foot"><?= $this->e($login) ?><?= !empty($navFoot) ? ' · ' . $this->e($navFoot) : '' ?></div>
</nav>

<main class="main<?= !empty($wide) ? ' wide' : '' ?>">
<?= $content ?>
</main>
</body>
</html>
