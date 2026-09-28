<?php /** @var \Training\View\View $this */ /** @var string $csrf */ ?>
  <div class="card">
    <div class="empty">
      <?= $this->icon('alert-triangle') ?>
      <div><b>Update erforderlich – nichts gespeichert.</b></div>
      <div class="small">Der Code erwartet einen anderen Datenbankstand. Bis zur Migration sind alle Schreibzugriffe (Webseite und Claude) gesperrt. Vor der Migration wird automatisch ein Backup angelegt.</div>
      <form method="post" action="/einstellungen"><input type="hidden" name="csrf" value="<?= $this->e($csrf) ?>"><input type="hidden" name="action" value="migrieren"><button class="btn btn-primary" type="submit"><?= $this->icon('refresh') ?>Jetzt migrieren</button></form>
    </div>
  </div>
