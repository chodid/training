<?php
/** @var \Training\View\View $this */
/** @var array{type: string, icon: string, title: string, text: string} $alert */
?>
  <div class="alert alert-<?= $this->e($alert['type']) ?>" role="alert">
    <?= $this->icon($alert['icon']) ?>
    <div><b><?= $this->e($alert['title']) ?></b> <span class="body"><?= $this->e($alert['text']) ?></span></div>
  </div>
