<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

if (!db_is_installed()) {
    redirect('/setup.php');
}
start_session();
header('Cache-Control: no-store');

if (current_user()) {
    redirect('/admin/');
}

$error = null;
$email = '';
if (is_post()) {
    require_csrf();
    $email = post('email');
    $error = attempt_login($email, (string) ($_POST['password'] ?? ''), isset($_POST['remember']));
    if ($error === null) {
        redirect('/admin/');
    }
}
$flash = take_flash();
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>Inhaber-Login – Pronto Pronto</title>
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="login">
<div class="tricolore" aria-hidden="true"><span></span><span></span><span></span></div>
<div style="padding:12px 16px">
  <a href="<?= e(url('/')) ?>" class="pill-link" style="border:0;padding:0"><?= icon('back') ?>Zurück zur Website</a>
</div>
<main id="inhalt">
  <div class="login__head">
    <span class="login__lock"><?= icon('lock') ?></span>
    <span class="brand__name" style="font-size:22px">Pronto Pronto</span>
    <span class="brand__sub" style="font-size:12px">Area riservata · Inhaberbereich</span>
    <h1>Bentornato!</h1>
    <p class="lead">Melde dich an und pflege Speisekarte, Preise und Öffnungszeiten – in wenigen Minuten, ohne Programmierer.</p>
  </div>

  <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
  <?php if ($flash): ?><div class="alert <?= $flash['type'] === 'ok' ? 'alert--ok' : '' ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>

  <form class="panel" method="post" action="" novalidate>
    <?= csrf_field() ?>
    <div class="field">
      <label for="email">E-Mail-Adresse</label>
      <input class="input" id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>" autofocus>
    </div>
    <div class="field">
      <label for="password">Passwort</label>
      <div class="pw-wrap">
        <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="pw-toggle" type="button" data-pw-toggle="password" aria-label="Passwort anzeigen"><?= icon('eye') ?></button>
      </div>
    </div>
    <label class="checkbox"><input type="checkbox" name="remember" value="1"> Angemeldet bleiben (30 Tage)</label>
    <button class="btn btn--red btn--block" type="submit">Anmelden – andiamo!</button>
    <p class="hint" style="text-align:center">Passwort vergessen? Der Webmaster kann es über <code>setup.php</code> bzw. die Datenbank zurücksetzen.</p>
  </form>

  <p class="hint" style="display:flex;gap:8px;align-items:flex-start"><?= icon('shield') ?>Nur für den Inhaber der Pizzeria Pronto Pronto. Nach <?= (int) config('login_max_attempts', 5) ?> Fehlversuchen wird der Login für <?= (int) config('login_lock_minutes', 15) ?> Minuten gesperrt.</p>
</main>
<div class="check" aria-hidden="true"></div>
<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
</body>
</html>
