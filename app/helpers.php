<?php
declare(strict_types=1);

function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** HTML-Ausgabe immer hierdurch schicken. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    return rtrim((string) config('base_path', ''), '/') . '/' . ltrim($path, '/');
}

/** Asset-URL mit Cache-Buster. */
function asset(string $path): string
{
    $file = PUBLIC_DIR . '/' . ltrim($path, '/');
    $v = is_file($file) ? (string) filemtime($file) : '1';
    return url($path) . '?v=' . $v;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function query_param(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function require_csrf(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Sitzung abgelaufen. Bitte die Seite neu laden und erneut versuchen.');
    }
}

/* ---------- Flash-Meldungen ---------- */

function flash(string $message, string $type = 'ok'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($f) ? $f : null;
}

/* ---------- Formatierung ---------- */

function format_price(?int $cents): string
{
    if ($cents === null) {
        return '';
    }
    return number_format($cents / 100, 2, ',', '.') . ' €';
}

/** "9,50" / "9.50" / "9" -> 950 ; leer -> null */
function parse_price(string $input): ?int
{
    $s = str_replace([' ', '€'], '', $input);
    if ($s === '') {
        return null;
    }
    $s = str_replace(',', '.', $s);
    if (!preg_match('/^\d{1,4}(\.\d{1,2})?$/', $s)) {
        return null;
    }
    return (int) round(((float) $s) * 100);
}

function format_time_short(string $hhmm): string
{
    [$h, $m] = array_map('intval', explode(':', $hhmm) + [0, 0]);
    return $m === 0 ? $h . ' Uhr' : sprintf('%d:%02d Uhr', $h, $m);
}

function valid_time(string $t): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t);
}

function valid_date(string $d): bool
{
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $d);
    return $dt !== false && $dt->format('Y-m-d') === $d;
}

function german_date(string $ymd, bool $withYear = false): string
{
    $dt = new DateTimeImmutable($ymd);
    return $dt->format($withYear ? 'd.m.Y' : 'd.m.');
}

const WEEKDAYS = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 0 => 'Sonntag'];
const MONTHS_SHORT = [1 => 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];

function time_ago(string $datetime): string
{
    $d = new DateTimeImmutable($datetime);
    $today = new DateTimeImmutable('today');
    $diff = (int) $today->diff($d->setTime(0, 0))->format('%r%a');
    return match (true) {
        $diff === 0 => 'heute, ' . $d->format('H:i'),
        $diff === -1 => 'gestern',
        $diff > -7 => WEEKDAYS[(int) $d->format('w')],
        default => $d->format('d.m.Y'),
    };
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}
