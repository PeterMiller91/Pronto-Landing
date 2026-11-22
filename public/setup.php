<?php
/**
 * Ersteinrichtung & Passwort-Notfall.
 * 1. Datenbank anlegen und Zugangsdaten in app/config.local.php eintragen (inkl. setup_token).
 * 2. Diese Seite aufrufen, Token + Inhaber-Zugang eingeben.
 * 3. Danach den setup_token in config.local.php leeren → Seite ist gesperrt.
 */
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
start_session();
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$token = (string) config('setup_token', '');
$isLocal = in_array(client_ip(), ['127.0.0.1', '::1'], true);
$locked = $token === '' && !$isLocal;

$error = null;
$done = false;
$dbError = null;
try {
    db();
    $installed = db_is_installed();
    $hasUser = $installed && (int) db_value('SELECT COUNT(*) FROM users') > 0;
} catch (Throwable $ex) {
    $dbError = 'Keine Verbindung zur Datenbank. Bitte die Zugangsdaten in app/config.local.php prüfen.';
    $installed = $hasUser = false;
}

if (!$locked && !$dbError && is_post()) {
    require_csrf();
    if ($token !== '' && !hash_equals($token, (string) ($_POST['token'] ?? ''))) {
        $error = 'Der Einrichtungs-Code stimmt nicht.';
        usleep(800000);
    } else {
        $email = mb_strtolower(post('email'));
        $name = mb_substr(post('name'), 0, 100);
        $pw = (string) ($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        } elseif ($err = validate_new_password($pw, (string) ($_POST['repeat'] ?? ''))) {
            $error = $err;
        } else {
            db_install_schema();
            seed_database();
            $existing = db_one('SELECT id FROM users WHERE email = ?', [$email]);
            if ($existing) {
                db_update('users', ['password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'name' => $name], 'id = ?', [$existing['id']]);
                log_activity('Passwort über Einrichtung zurückgesetzt');
            } else {
                db_insert('users', ['email' => $email, 'name' => $name, 'password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'created_at' => now()]);
                log_activity('Inhaber-Zugang angelegt');
            }
            db_exec('DELETE FROM login_attempts');
            $done = true;
        }
    }
}
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Einrichtung – Pronto Pronto</title>
<link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="login">
<div class="tricolore" aria-hidden="true"><span></span><span></span><span></span></div>
<main id="inhalt">
  <div class="login__head">
    <span class="login__lock"><?= icon('key') ?></span>
    <h1><?= $hasUser ? 'Zugang zurücksetzen' : 'Benvenuto! Einrichtung' ?></h1>
    <p class="lead"><?= $hasUser ? 'Hier kannst du ein vergessenes Passwort neu setzen oder einen weiteren Zugang anlegen.' : 'Lege die Datenbank-Tabellen und den Inhaber-Zugang an.' ?></p>
  </div>

  <?php if ($locked): ?>
    <div class="alert" role="alert">Die Einrichtung ist gesperrt. Zum Freischalten in <code>app/config.local.php</code> einen <code>setup_token</code> eintragen.</div>
  <?php elseif ($dbError): ?>
    <div class="alert" role="alert"><?= e($dbError) ?></div>
  <?php elseif ($done): ?>
    <div class="alert alert--ok" role="status">Fatto! Alles ist eingerichtet. <strong>Wichtig:</strong> Leere jetzt den <code>setup_token</code> in <code>app/config.local.php</code>, damit diese Seite gesperrt ist.</div>
    <a class="btn btn--red btn--block" href="<?= e(url('admin/login.php')) ?>">Zum Login</a>
  <?php else: ?>
    <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form class="panel" method="post" action="">
      <?= csrf_field() ?>
      <?php if ($token !== ''): ?>
      <div class="field"><label for="token">Einrichtungs-Code <small>(setup_token aus config.local.php)</small></label><input class="input" id="token" name="token" type="password" required autocomplete="off"></div>
      <?php endif; ?>
      <div class="field"><label for="name">Vorname</label><input class="input" id="name" name="name" maxlength="100" placeholder="z. B. Giuseppe" value="<?= e(post('name')) ?>"></div>
      <div class="field"><label for="email">E-Mail-Adresse (Login)</label><input class="input" id="email" name="email" type="email" required autocomplete="username" value="<?= e(post('email')) ?>"></div>
      <div class="field"><label for="password">Passwort <small>(mind. 10 Zeichen)</small></label><input class="input" id="password" name="password" type="password" required minlength="10" autocomplete="new-password"></div>
      <div class="field"><label for="repeat">Passwort wiederholen</label><input class="input" id="repeat" name="repeat" type="password" required minlength="10" autocomplete="new-password"></div>
      <button class="btn btn--green btn--block" type="submit"><?= $hasUser ? 'Zugang speichern' : 'Einrichten – andiamo!' ?></button>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
