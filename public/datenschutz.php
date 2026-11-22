<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$site = config('site');
$pageTitle = 'Datenschutz – Pizzeria Pronto Pronto Vohenstrauß';
$pageDescription = 'Datenschutzerklärung der Pizzeria Pronto Pronto.';
require APP_DIR . '/views/site_header.php';
?>
<section class="page">
  <div class="wrap">
    <span class="kicker">Privacy</span>
    <h1>Datenschutzerklärung</h1>
    <p class="placeholder"><strong>Entwurf – bitte vom Inhaber ergänzen und rechtlich prüfen lassen</strong> (z. B. mit einem Datenschutz-Generator oder durch eine Kanzlei). Die mit [ ] markierten Angaben sind Platzhalter.</p>

    <h2>1. Verantwortlicher</h2>
    <p>Pizzeria Pronto Pronto, [Inhaber], <?= e($site['street']) ?>, <?= e($site['zip_city']) ?>, Telefon <?= e($site['phone']) ?>, E-Mail [E-Mail-Adresse].</p>

    <h2>2. Hosting und Server-Logfiles</h2>
    <p>Diese Website wird bei der netcup GmbH (Karlsruhe) gehostet. Beim Aufruf speichert der Server technisch notwendige Daten (u. a. IP-Adresse, Datum/Uhrzeit, aufgerufene Seite, Browser) in Logfiles. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO (sicherer Betrieb). [Speicherdauer laut Hoster ergänzen]</p>

    <h2>3. Keine Cookies, kein Tracking</h2>
    <p>Die öffentliche Website setzt keine Tracking- oder Werbe-Cookies. Schriftarten werden lokal von unserem Server geladen, nicht von Google. Der Bestellzettel und die Einstellung „Karte immer anzeigen“ werden nur im Speicher deines Browsers (Local Storage) abgelegt und nicht an uns übertragen.</p>

    <h2>4. Google Maps</h2>
    <p>Die Karte wird erst geladen, wenn du auf „Karte anzeigen“ klickst. Dann werden Daten (u. a. deine IP-Adresse) an Google Ireland Limited, Gordon House, Barrow Street, Dublin 4, Irland übertragen und ggf. in die USA übermittelt. Rechtsgrundlage ist deine Einwilligung nach Art. 6 Abs. 1 lit. a DSGVO und § 25 Abs. 1 TDDDG. Du kannst sie jederzeit widerrufen, indem du die Website-Daten in deinem Browser löschst. Mehr: <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">policies.google.com/privacy</a>.</p>

    <h2>5. Telefonische Bestellung</h2>
    <p>Wenn du telefonisch bestellst, verarbeiten wir die dafür nötigen Angaben (z. B. Name, Adresse, Telefonnummer) zur Abwicklung der Bestellung (Art. 6 Abs. 1 lit. b DSGVO).</p>

    <h2>6. Inhaber-Login</h2>
    <p>Für den geschützten Inhaberbereich wird ein technisch notwendiges Sitzungs-Cookie gesetzt. Zum Schutz vor Angriffen speichern wir fehlgeschlagene Anmeldeversuche (IP-Adresse, Zeitpunkt) für höchstens 24 Stunden.</p>

    <h2>7. Deine Rechte</h2>
    <p>Du hast das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit und Widerspruch sowie auf Beschwerde bei einer Datenschutz-Aufsichtsbehörde, z. B. dem Bayerischen Landesamt für Datenschutzaufsicht (BayLDA).</p>

    <p><a href="<?= e(url('/')) ?>">← Zurück zur Startseite</a></p>
  </div>
</section>
<?php require APP_DIR . '/views/site_footer.php'; ?>
