<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (config('db_driver') === 'mysql') {
        $host = (string) config('db_host');
        $port = null;
        if (str_contains($host, ':')) {
            [$host, $port] = explode(':', $host, 2);
        }
        $dsn = 'mysql:host=' . $host . ($port ? ';port=' . $port : '') . ';dbname=' . config('db_name') . ';charset=utf8mb4';
        $pdo = new PDO($dsn, (string) config('db_user'), (string) config('db_pass'), $options);
    } else {
        $file = (string) config('db_sqlite');
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0775, true);
        }
        $pdo = new PDO('sqlite:' . $file, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}

function db_driver(): string
{
    return config('db_driver') === 'mysql' ? 'mysql' : 'sqlite';
}

/** @return list<array<string,mixed>> */
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

function db_value(string $sql, array $params = []): mixed
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
}

function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

function db_insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
    db_exec($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function db_update(string $table, array $data, string $where, array $whereParams): int
{
    $set = implode(', ', array_map(fn ($c) => $c . ' = ?', array_keys($data)));
    return db_exec('UPDATE ' . $table . ' SET ' . $set . ' WHERE ' . $where, [...array_values($data), ...$whereParams]);
}

function db_is_installed(): bool
{
    try {
        db_value('SELECT COUNT(*) FROM users');
        return true;
    } catch (Throwable) {
        return false;
    }
}

/** Legt die Tabellen an (idempotent). */
function db_install_schema(): void
{
    $file = dirname(__DIR__) . '/database/schema.' . db_driver() . '.sql';
    $sql = (string) file_get_contents($file);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        db()->exec($statement);
    }
}
