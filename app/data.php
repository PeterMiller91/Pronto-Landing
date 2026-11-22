<?php
declare(strict_types=1);

/* ---------- Einstellungen ---------- */

function setting(string $name, ?string $default = null): ?string
{
    static $all = null;
    if ($all === null || isset($GLOBALS['__settings_dirty'])) {
        unset($GLOBALS['__settings_dirty']);
        $all = [];
        foreach (db_all('SELECT name, value FROM settings') as $row) {
            $all[$row['name']] = $row['value'];
        }
    }
    return $all[$name] ?? $default;
}

function set_setting(string $name, ?string $value): void
{
    $exists = db_value('SELECT COUNT(*) FROM settings WHERE name = ?', [$name]);
    if ((int) $exists > 0) {
        db_exec('UPDATE settings SET value = ? WHERE name = ?', [$value, $name]);
    } else {
        db_insert('settings', ['name' => $name, 'value' => $value]);
    }
    $GLOBALS['__settings_dirty'] = true;
}

function log_activity(string $message): void
{
    db_insert('activity_log', ['message' => mb_substr($message, 0, 200), 'created_at' => now()]);
    // Nur die letzten 100 Einträge behalten
    $cut = db_value('SELECT id FROM activity_log ORDER BY id DESC LIMIT 1 OFFSET 99');
    if ($cut !== null) {
        db_exec('DELETE FROM activity_log WHERE id < ?', [$cut]);
    }
}

function recent_activity(int $limit = 5): array
{
    return db_all('SELECT message, created_at FROM activity_log ORDER BY id DESC LIMIT ' . max(1, $limit));
}

/* ---------- Speisekarte ---------- */

function categories(): array
{
    return db_all('SELECT * FROM categories ORDER BY sort, id');
}

function category_by_slug(string $slug): ?array
{
    return db_one('SELECT * FROM categories WHERE slug = ?', [$slug]);
}

function dishes_for_category(int $categoryId): array
{
    return db_all('SELECT * FROM dishes WHERE category_id = ? ORDER BY sort, id', [$categoryId]);
}

/** Speisekarte gruppiert: [ [category, dishes[]], ... ] */
function menu_grouped(): array
{
    $out = [];
    foreach (categories() as $cat) {
        $out[] = ['category' => $cat, 'dishes' => dishes_for_category((int) $cat['id'])];
    }
    return $out;
}

function dish(int $id): ?array
{
    return db_one('SELECT * FROM dishes WHERE id = ?', [$id]);
}

/** Größen-Bezeichnungen einer Kategorie (z. B. "Ø 26 cm" / "Ø 30 cm"). */
function size_labels(?array $cat): array
{
    $small = trim((string) ($cat['size_small'] ?? ''));
    $large = trim((string) ($cat['size_large'] ?? ''));
    return [$small !== '' ? $small : 'klein', $large !== '' ? $large : 'groß'];
}

function dish_price_label(array $d, ?array $cat = null): string
{
    $small = $d['price_cents'] !== null ? (int) $d['price_cents'] : null;
    $large = $d['price_large_cents'] !== null ? (int) $d['price_large_cents'] : null;
    if ($small !== null && $large !== null) {
        [$ls, $ll] = size_labels($cat);
        return $ls . ' ' . format_price($small) . ' · ' . $ll . ' ' . format_price($large);
    }
    return format_price($small ?? $large);
}

/** "2,6,7" -> [2, 6, 7] (nur gültige Nummern) */
function additive_numbers(string $s): array
{
    $nums = array_map('intval', preg_split('/[^0-9]+/', $s, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    $nums = array_values(array_unique(array_filter($nums, fn ($n) => isset(ADDITIVES_LEGEND[$n]))));
    sort($nums);
    return $nums;
}

function count_dishes(): array
{
    return [
        'total' => (int) db_value('SELECT COUNT(*) FROM dishes'),
        'soldout' => (int) db_value('SELECT COUNT(*) FROM dishes WHERE is_available = 0'),
    ];
}

/* ---------- Öffnungszeiten ---------- */

/** @return array<int,array> indexiert nach Wochentag 0=So..6=Sa */
function weekly_hours(): array
{
    $rows = [];
    foreach (db_all('SELECT * FROM opening_hours') as $r) {
        $rows[(int) $r['weekday']] = $r;
    }
    foreach (range(0, 6) as $d) {
        $rows[$d] ??= ['weekday' => $d, 'is_open' => 0, 'open_time' => '17:00', 'close_time' => '23:00', 'open_time2' => null, 'close_time2' => null];
    }
    return $rows;
}

function upcoming_special_days(): array
{
    return db_all('SELECT * FROM special_days WHERE date_to >= ? ORDER BY date_from, id', [date('Y-m-d')]);
}

/* ---------- Fotos & Bewertungen ---------- */

function photos(): array
{
    return db_all('SELECT * FROM photos ORDER BY is_hero DESC, sort, id DESC');
}

function photo_url(array $p, bool $thumb = false): string
{
    $file = (string) $p['filename'];
    if (str_starts_with($file, 'assets/')) {
        // mitgelieferte Bilder
        return url($thumb ? preg_replace('/\.jpg$/', '-thumb.jpg', $file) : $file);
    }
    return url('uploads/' . ($thumb ? 'thumb_' : '') . $file);
}

function hero_photo(): ?array
{
    return db_one('SELECT * FROM photos WHERE is_hero = 1 ORDER BY id DESC LIMIT 1');
}

function visible_reviews(): array
{
    return db_all('SELECT * FROM reviews WHERE is_visible = 1 ORDER BY sort, id');
}

/* ---------- Hinweis-Leiste ---------- */

function active_notice(): ?array
{
    if (setting('notice_active') !== '1') {
        return null;
    }
    $text = trim((string) setting('notice_text', ''));
    if ($text === '') {
        return null;
    }
    $until = (string) setting('notice_until', '');
    if ($until !== '' && valid_date($until) && $until < date('Y-m-d')) {
        return null;
    }
    $color = (string) setting('notice_color', 'red');
    return ['text' => $text, 'color' => in_array($color, ['red', 'green', 'gold'], true) ? $color : 'red'];
}

/* ---------- Pizza der Woche ---------- */

/** @return array{dish: array, text: string, price_cents: ?int, until: string, photo: ?array}|null */
function weekly_special(): ?array
{
    if (setting('special_active') !== '1') {
        return null;
    }
    $dish = dish((int) setting('special_dish_id', '0'));
    if ($dish === null) {
        return null;
    }
    $until = (string) setting('special_until', '');
    if ($until !== '' && (!valid_date($until) || $until < date('Y-m-d'))) {
        return null;
    }
    $price = setting('special_price_cents', '');
    $photoId = (int) setting('special_photo_id', '0');
    return [
        'dish' => $dish,
        'text' => trim((string) setting('special_text', '')),
        'price_cents' => $price !== null && $price !== '' && ctype_digit($price) ? (int) $price : null,
        'until' => $until,
        'photo' => $photoId > 0 ? db_one('SELECT * FROM photos WHERE id = ?', [$photoId]) : null,
    ];
}

/** Nächster Sonntag (bzw. heute, falls Sonntag) als Standard-Enddatum. */
function next_sunday(): string
{
    $d = new DateTimeImmutable('today');
    return (int) $d->format('w') === 0 ? $d->format('Y-m-d') : $d->modify('next sunday')->format('Y-m-d');
}

/* ---------- Erstbefüllung ---------- */

function seed_database(): void
{
    if ((int) db_value('SELECT COUNT(*) FROM categories') > 0) {
        return;
    }
    $now = now();

    import_real_menu();
    set_setting('schema_version', (string) SCHEMA_VERSION);

    foreach (range(0, 6) as $d) {
        db_insert('opening_hours', [
            'weekday' => $d, 'is_open' => $d === 2 ? 0 : 1,
            'open_time' => '17:00', 'close_time' => '23:00', 'open_time2' => null, 'close_time2' => null,
        ]);
    }

    // Echte Google-Bewertungen (gekürzt, Nachnamen abgekürzt)
    $reviews = [
        ['Ela', 'Die beste Pizza in der Umgebung! Super lecker und frisch. Der Chef ist sehr aufmerksam und freundlich. Gerne wieder!', 5],
        ['Thomas B.', 'Alles super frisch und geschmacklich traumhaft. Das Ganze wurde geliefert und war noch richtig heiß. Preis / Leistung / Qualität absolut top.', 5],
        ['Andreas B.', 'Ein absolutes Muss in der Oberpfalz! Ein sehr freundlicher Chef und ein super Team. Die Pizzen sind sehr köstlich und die Weine hervorragend!', 5],
        ['Christian H.', 'Sehr gute Pizza mit Geschmack. Die Preise für die Pizzen sind auch fair. Gerne komme ich immer wieder, um bei euch eine Pizza zu kaufen.', 5],
        ['Pascal S.', 'Ich habe schon öfter hier Pizzas geholt, sie sind geschmacklich sehr gut und auch frisch.', 4],
    ];
    foreach ($reviews as $i => [$author, $text, $rating]) {
        db_insert('reviews', ['author' => $author, 'text' => $text, 'rating' => $rating, 'source' => 'Google', 'is_visible' => 1, 'sort' => $i]);
    }

    db_insert('photos', ['filename' => 'assets/img/margherita.jpg', 'label' => 'Margherita', 'is_hero' => 1, 'sort' => 0, 'created_at' => $now]);

    set_setting('notice_active', '0');
    set_setting('notice_text', '');
    set_setting('notice_color', 'red');
    set_setting('notice_until', '');
    set_setting('closed_today', '');
    set_setting('special_active', '0');
    set_setting('delivery_note', 'Wir liefern in Vohenstrauß und Umgebung – frag am Telefon einfach nach deinem Ort.');

    log_activity('Website eingerichtet');
}
