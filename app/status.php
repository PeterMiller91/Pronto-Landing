<?php
declare(strict_types=1);

/**
 * Öffnungszeiten für ein Datum.
 * @return array{intervals: list<array{0:string,1:string}>, reason: ?string, special: bool}
 */
function hours_for_date(DateTimeImmutable $date, ?array $weekly = null, ?array $specials = null): array
{
    $ymd = $date->format('Y-m-d');

    if (setting('closed_today') === $ymd) {
        return ['intervals' => [], 'reason' => 'Heute geschlossen', 'special' => true];
    }

    $specials ??= db_all('SELECT * FROM special_days WHERE date_from <= ? AND date_to >= ? ORDER BY id DESC', [$ymd, $ymd]);
    foreach ($specials as $s) {
        if ($s['date_from'] <= $ymd && $s['date_to'] >= $ymd) {
            if ((int) $s['is_closed'] === 1 || !$s['open_time'] || !$s['close_time']) {
                return ['intervals' => [], 'reason' => (string) $s['title'], 'special' => true];
            }
            return ['intervals' => [[(string) $s['open_time'], (string) $s['close_time']]], 'reason' => (string) $s['title'], 'special' => true];
        }
    }

    $weekly ??= weekly_hours();
    $row = $weekly[(int) $date->format('w')] ?? null;
    if (!$row || (int) $row['is_open'] !== 1) {
        return ['intervals' => [], 'reason' => 'Ruhetag', 'special' => false];
    }
    $intervals = [[(string) $row['open_time'], (string) $row['close_time']]];
    if (!empty($row['open_time2']) && !empty($row['close_time2'])) {
        $intervals[] = [(string) $row['open_time2'], (string) $row['close_time2']];
    }
    usort($intervals, fn ($a, $b) => strcmp($a[0], $b[0]));
    return ['intervals' => $intervals, 'reason' => null, 'special' => false];
}

function minutes_of(string $hhmm): int
{
    [$h, $m] = array_map('intval', explode(':', $hhmm) + [0, 0]);
    return $h * 60 + $m;
}

/**
 * Aktueller Status für die Website.
 * @return array{open: bool, text: string}
 */
function opening_status(?DateTimeImmutable $now = null): array
{
    $now ??= new DateTimeImmutable('now');
    $weekly = weekly_hours();
    $specials = db_all('SELECT * FROM special_days WHERE date_to >= ? AND date_from <= ?', [$now->format('Y-m-d'), $now->modify('+21 days')->format('Y-m-d')]);
    $today = hours_for_date($now, $weekly, $specials);
    $mins = (int) $now->format('G') * 60 + (int) $now->format('i');

    foreach ($today['intervals'] as [$from, $to]) {
        $f = minutes_of($from);
        $t = minutes_of($to);
        if ($t <= $f) {
            $t += 24 * 60; // Schließt nach Mitternacht
        }
        if ($mins >= $f && $mins < $t) {
            return ['open' => true, 'text' => 'Aperto! Jetzt geöffnet bis ' . format_time_short($to)];
        }
        if ($mins < $f) {
            return ['open' => false, 'text' => 'Heute ab ' . format_time_short($from) . ' für dich da'];
        }
    }

    // Nächsten Öffnungstag suchen
    $prefix = match (true) {
        $today['intervals'] === [] && $today['reason'] === 'Ruhetag' => 'Heute Ruhetag',
        $today['intervals'] === [] && $today['reason'] !== null => $today['reason'],
        default => 'Geschlossen',
    };
    for ($i = 1; $i <= 21; $i++) {
        $day = $now->modify('+' . $i . ' days');
        $h = hours_for_date($day, $weekly, $specials);
        if ($h['intervals'] !== []) {
            $when = $i === 1 ? 'morgen' : WEEKDAYS[(int) $day->format('w')];
            if ($i > 6) {
                $when = 'ab ' . $day->format('d.m.') . ',';
                return ['open' => false, 'text' => $prefix . ' – wieder da ' . $when . ' ' . format_time_short($h['intervals'][0][0])];
            }
            return ['open' => false, 'text' => $prefix . ' – ' . $when . ' ab ' . format_time_short($h['intervals'][0][0])];
        }
    }
    return ['open' => false, 'text' => $prefix];
}

/** Wochenplan für die Anzeige (Mo–So) inkl. Markierung "heute". */
function hours_table(?DateTimeImmutable $now = null): array
{
    $now ??= new DateTimeImmutable('now');
    $weekly = weekly_hours();
    $todayW = (int) $now->format('w');
    $rows = [];
    foreach (WEEKDAYS as $w => $name) {
        $r = $weekly[$w];
        $open = (int) $r['is_open'] === 1;
        $times = $open ? $r['open_time'] . ' – ' . $r['close_time'] : 'Ruhetag';
        if ($open && !empty($r['open_time2']) && !empty($r['close_time2'])) {
            $times .= ' · ' . $r['open_time2'] . ' – ' . $r['close_time2'];
        }
        $rows[] = ['name' => $name, 'times' => $times, 'open' => $open, 'today' => $w === $todayW];
    }
    return $rows;
}
