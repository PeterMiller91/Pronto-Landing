<?php
/**
 * Kopiere diese Datei nach app/config.local.php und trage die Zugangsdaten ein.
 * config.local.php wird nicht ins Git-Repository übernommen.
 */
return [
    'db_driver' => 'mysql',
    'db_host'   => 'localhost',            // bei Netcup/Plesk meist "localhost" oder "localhost:3306"
    'db_name'   => 'DATENBANKNAME',
    'db_user'   => 'DATENBANKBENUTZER',
    'db_pass'   => 'DATENBANKPASSWORT',

    // Langer Zufallswert, z. B. aus einem Passwort-Generator. Wird nur für public/setup.php gebraucht.
    'setup_token' => 'HIER-EINEN-LANGEN-ZUFALLSWERT-EINTRAGEN',
];
