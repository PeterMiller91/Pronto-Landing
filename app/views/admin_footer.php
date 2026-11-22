<?php
/** @var string $active */
$tabs = [
    'dashboard' => ['admin/', 'home', 'Übersicht'],
    'menu'      => ['admin/menu.php', 'list', 'Karte'],
    'hours'     => ['admin/hours.php', 'clock', 'Zeiten'],
    'notice'    => ['admin/notice.php', 'image', 'Hinweis & Fotos'],
];
?>
</main>
<nav class="admin-tabs" aria-label="Backend-Navigation">
  <?php foreach ($tabs as $key => [$href, $ic, $label]): ?>
  <a href="<?= e(url($href)) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span></a>
  <?php endforeach; ?>
</nav>
<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
</body>
</html>
