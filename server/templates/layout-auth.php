<?php
/** @var \Training\View\View $this */
/** @var string $title */
/** @var string $content */
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex">
<title><?= $this->e($title) ?> – Training</title>
<link rel="icon" href="/assets/lama-kopf.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/ds/styles.css">
<link rel="stylesheet" href="/assets/app.css">
<link rel="stylesheet" href="/css/training.css">
<script src="/js/offline.js?v=<?= $this->e(\Training\App::VERSION) ?>" data-version="<?= $this->e(\Training\App::VERSION) ?>" data-auth defer></script>
</head>
<body class="auth">
<?= $content ?>
</body>
</html>
