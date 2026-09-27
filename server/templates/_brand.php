<?php /** @var \Training\View\View $this */ ?>
  <div class="auth-brand">
    <img src="/assets/lama-kopf.svg" alt="">
    <div class="name">Training</div>
<?php if (isset($sub)): ?>
    <div class="sub"><?= $this->e($sub) ?></div>
<?php endif ?>
  </div>
