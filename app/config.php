<?php
/**
 * Standard-Konfiguration. NICHT hier Zugangsdaten eintragen,
 * sondern in app/config.local.php (siehe config.local.example.php).
 */
declare(strict_types=1);

$config = [
    // Datenbank: 'mysql' (Netcup/Plesk) oder 'sqlite' (lokal testen)
    'db_driver'   => 'sqlite',
    'db_host'     => 'localhost',
    'db_name'     => 'pronto_pronto',
    'db_user'     => '',
    'db_pass'     => '',
    'db_sqlite'   => __DIR__ . '/../storage/database.sqlite',

    // Basis-Pfad, falls die Seite in einem Unterordner liegt (z. B. '/pizzeria'). Leer = Domain-Root.
    'base_path'   => '',

    // Einmal-Code für die Ersteinrichtung (public/setup.php). Nach der Einrichtung egal.
    'setup_token' => '',

    // Login-Schutz
    'login_max_attempts'   => 5,
    'login_lock_minutes'   => 15,
    'session_idle_minutes' => 480,

    // Uploads
    'upload_max_mb' => 10,

    // Stammdaten der Pizzeria
    'site' => [
        'name'      => 'Pronto Pronto',
        'tagline'   => 'Pizzeria · Vohenstrauß',
        'phone'     => '09651 / 9246800',
        'phone_e164'=> '+4996519246800',
        'street'    => 'Marktplatz 6',
        'zip_city'  => '92648 Vohenstrauß',
        'maps_query'=> 'Pizzeria Pronto Pronto, Marktplatz 6, 92648 Vohenstrauß',
    ],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $override = require $local;
    if (is_array($override)) {
        $config = array_replace_recursive($config, $override);
    }
}

return $config;
