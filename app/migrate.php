<?php
declare(strict_types=1);

const SCHEMA_VERSION = 2;

function column_exists(string $table, string $column): bool
{
    try {
        db_value('SELECT ' . $column . ' FROM ' . $table . ' LIMIT 1');
        return true;
    } catch (Throwable) {
        return false;
    }
}

/**
 * Bringt bestehende Installationen auf den aktuellen Stand (läuft automatisch, nur einmal).
 * Version 2: Größen/Hinweise je Kategorie, Zusatzstoffe je Gericht, echte Speisekarte.
 */
function migrate(): void
{
    $version = (int) (setting('schema_version') ?? '1');
    if ($version >= SCHEMA_VERSION) {
        return;
    }
    if ($version < 2) {
        $text = db_driver() === 'mysql' ? "VARCHAR(40) NOT NULL DEFAULT ''" : "TEXT NOT NULL DEFAULT ''";
        $long = db_driver() === 'mysql' ? "VARCHAR(400) NOT NULL DEFAULT ''" : "TEXT NOT NULL DEFAULT ''";
        foreach (['size_small' => $text, 'size_large' => $text, 'note' => $long] as $col => $type) {
            if (!column_exists('categories', $col)) {
                db()->exec('ALTER TABLE categories ADD COLUMN ' . $col . ' ' . $type);
            }
        }
        if (!column_exists('dishes', 'additives')) {
            db()->exec('ALTER TABLE dishes ADD COLUMN additives ' . $text);
        }
        import_real_menu();
        // Die alte Pizza der Woche verweist auf ein gelöschtes Gericht
        set_setting('special_active', '0');
        log_activity('Echte Speisekarte übernommen');
    }
    set_setting('schema_version', (string) SCHEMA_VERSION);
}
