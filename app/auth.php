<?php
declare(strict_types=1);

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) (60 * 60 * 24 * 30));
    session_name('pp_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => url('/'),
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function current_user(): ?array
{
    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id)) {
        return null;
    }

    // Leerlauf-Timeout (außer "Angemeldet bleiben")
    $last = (int) ($_SESSION['last_activity'] ?? 0);
    $idle = (int) config('session_idle_minutes', 480) * 60;
    if (empty($_SESSION['remember']) && $last > 0 && time() - $last > $idle) {
        logout();
        return null;
    }
    $_SESSION['last_activity'] = time();

    static $cache = [];
    if (!array_key_exists($id, $cache)) {
        $cache[$id] = db_one('SELECT id, email, name FROM users WHERE id = ?', [$id]);
    }
    return $cache[$id];
}

function require_login(): array
{
    start_session();
    $user = current_user();
    if ($user === null) {
        redirect('/admin/login.php');
    }
    header('Cache-Control: no-store');
    return $user;
}

function login_is_locked(string $ip): bool
{
    $since = date('Y-m-d H:i:s', time() - (int) config('login_lock_minutes', 15) * 60);
    $count = (int) db_value('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?', [$ip, $since]);
    return $count >= (int) config('login_max_attempts', 5);
}

/** @return string|null Fehlermeldung oder null bei Erfolg */
function attempt_login(string $email, string $password, bool $remember): ?string
{
    $ip = client_ip();
    if (login_is_locked($ip)) {
        return 'Zu viele Fehlversuche. Bitte warte ' . (int) config('login_lock_minutes', 15) . ' Minuten und versuche es dann erneut.';
    }

    $user = db_one('SELECT * FROM users WHERE email = ?', [mb_strtolower($email)]);
    // Auch bei unbekannter E-Mail einen Hash prüfen, damit die Antwortzeit nichts verrät.
    $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
    $ok = password_verify($password, $hash) && $user !== null;

    if (!$ok) {
        db_insert('login_attempts', ['ip' => $ip, 'email' => mb_substr($email, 0, 190), 'attempted_at' => now()]);
        return 'E-Mail oder Passwort stimmt nicht.';
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        db_update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$user['id']]);
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['remember'] = $remember;
    $_SESSION['last_activity'] = time();
    unset($_SESSION['csrf']);

    if ($remember) {
        $p = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires'  => time() + 60 * 60 * 24 * 30,
            'path'     => $p['path'],
            'secure'   => $p['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    db_exec('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
    db_update('users', ['last_login_at' => now()], 'id = ?', [$user['id']]);
    // Alte Einträge aufräumen
    db_exec('DELETE FROM login_attempts WHERE attempted_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    return null;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function validate_new_password(string $pw, string $repeat): ?string
{
    if (mb_strlen($pw) < 10) {
        return 'Das Passwort muss mindestens 10 Zeichen lang sein.';
    }
    if ($pw !== $repeat) {
        return 'Die beiden Passwörter stimmen nicht überein.';
    }
    return null;
}
