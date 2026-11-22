<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

if (!db_is_installed()) {
    redirect('/setup.php');
}
$user = require_login();

if (is_post()) {
    require_csrf();
    $current = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['new'] ?? '');
    $repeat = (string) ($_POST['repeat'] ?? '');
    $hash = (string) db_value('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
    if (!password_verify($current, $hash)) {
        flash('Das aktuelle Passwort stimmt nicht.', 'error');
    } elseif ($err = validate_new_password($new, $repeat)) {
        flash($err, 'error');
    } else {
        db_update('users', ['password_hash' => password_hash($new, PASSWORD_DEFAULT)], 'id = ?', [$user['id']]);
        session_regenerate_id(true);
        log_activity('Passwort geändert');
        flash('Neues Passwort gespeichert.');
        redirect('/admin/');
    }
    redirect('/admin/account.php');
}

$adminTitle = 'Passwort ändern';
$adminKicker = 'Il tuo account';
$active = 'dashboard';
require APP_DIR . '/views/admin_header.php';
?>
<form class="panel" method="post" action="">
  <?= csrf_field() ?>
  <p class="hint">Angemeldet als <strong><?= e($user['email']) ?></strong></p>
  <div class="field"><label for="current">Aktuelles Passwort</label><input class="input" type="password" id="current" name="current" autocomplete="current-password" required></div>
  <div class="field"><label for="new">Neues Passwort <small>(mind. 10 Zeichen)</small></label><input class="input" type="password" id="new" name="new" autocomplete="new-password" minlength="10" required></div>
  <div class="field"><label for="repeat">Neues Passwort wiederholen</label><input class="input" type="password" id="repeat" name="repeat" autocomplete="new-password" minlength="10" required></div>
  <button class="btn btn--green btn--block" type="submit">Passwort speichern</button>
</form>
<?php require APP_DIR . '/views/admin_footer.php'; ?>
