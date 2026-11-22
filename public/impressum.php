<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$site = config('site');
$pageTitle = 'Impressum – Pizzeria Pronto Pronto Vohenstrauß';
$pageDescription = 'Impressum der Pizzeria Pronto Pronto, Marktplatz 6, 92648 Vohenstrauß.';
require APP_DIR . '/views/site_header.php';
?>
<section class="page">
  <div class="wrap">
    <span class="kicker">Impressum</span>
    <h1>Angaben gemäß § 5 DDG</h1>
    <p class="placeholder"><strong>Bitte vom Inhaber ergänzen und rechtlich prüfen lassen.</strong> Die mit [ ] markierten Angaben sind Platzhalter.</p>
    <p>Pizzeria Pronto Pronto<br>Inhaber: [Vor- und Nachname]<br><?= e($site['street']) ?><br><?= e($site['zip_city']) ?></p>
    <h2>Kontakt</h2>
    <p>Telefon: <a href="tel:<?= e($site['phone_e164']) ?>"><?= e($site['phone']) ?></a><br>E-Mail: [E-Mail-Adresse]</p>
    <h2>Umsatzsteuer-ID</h2>
    <p>Umsatzsteuer-Identifikationsnummer gemäß § 27 a UStG: [falls vorhanden]</p>
    <h2>Verbraucherstreitbeilegung</h2>
    <p>Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>
    <p><a href="<?= e(url('/')) ?>">← Zurück zur Startseite</a></p>
  </div>
</section>
<?php require APP_DIR . '/views/site_footer.php'; ?>
