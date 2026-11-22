<?php
/** @var string $adminTitle */
/** @var string $adminKicker */
/** @var string $active  dashboard|menu|hours|notice|account */
/** @var bool $showBack */
$showBack ??= true;
$flash = take_flash();
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title><?= e($adminTitle) ?> – Pronto Pronto Backend</title>
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin">
<div class="tricolore" aria-hidden="true"><span></span><span></span><span></span></div>
<header class="admin-header">
  <?php if ($showBack): ?>
  <a class="icon-link" href="<?= e(url('admin/')) ?>" aria-label="Zurück zur Übersicht"><?= icon('back') ?></a>
  <?php endif; ?>
  <div class="admin-header__title">
    <span class="admin-kicker"><?= e($adminKicker) ?></span>
    <span class="admin-h"><?= e($adminTitle) ?></span>
  </div>
  <a class="pill-link" href="<?= e(url('admin/logout.php')) ?>"><?= icon('logout') ?>Abmelden</a>
</header>
<?php if ($flash): ?>
<div class="toast toast--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endif; ?>
<main class="admin-main" id="inhalt">
