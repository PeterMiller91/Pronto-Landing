<?php
$site = config('site');
$withActionBar ??= false;
?>
</main>
<div class="check" aria-hidden="true"></div>
<footer class="site-footer">
  <div class="wrap">
    <span class="footer__name"><?= e($site['name']) ?></span>
    <span>Pizzeria · <?= e($site['street']) ?> · <?= e($site['zip_city']) ?></span>
    <span class="footer__ciao">Grazie e buon appetito!</span>
    <nav aria-label="Rechtliches">
      <a href="<?= e(url('impressum.php')) ?>">Impressum</a>
      <a href="<?= e(url('datenschutz.php')) ?>">Datenschutz</a>
      <button class="footer-link" type="button" data-consent-open>Cookie-Einstellungen</button>
      <a href="<?= e(url('admin/')) ?>" rel="nofollow"><?= icon('lock', 'icon') ?>Inhaber-Login</a>
    </nav>
  </div>
</footer>

<?php if ($withActionBar): ?>
<div class="action-bar" data-order-bar>
  <section class="order-sheet" id="order-sheet" aria-label="Dein Bestellzettel" hidden>
    <div class="order-sheet__head">
      <h2>Il tuo ordine</h2>
      <button class="icon-btn" type="button" data-order-close aria-label="Bestellzettel schließen"><?= icon('close') ?></button>
    </div>
    <p>Lies uns am Telefon einfach diese Nummern vor:</p>
    <ul data-order-lines></ul>
    <div class="order-total"><span data-order-total-label>Summe</span><strong data-order-total>0,00 €</strong></div>
    <p class="order-note" data-order-note>Zzgl. eventueller Lieferkosten. Extras berechnen wir am Telefon.</p>
    <a class="btn btn--red btn--block" href="tel:<?= e($site['phone_e164']) ?>"><?= icon('phone') ?>Anrufen &amp; Nummern durchgeben</a>
    <button class="btn-ghost-light" type="button" data-order-clear>Zettel leeren</button>
  </section>
  <button class="order-toggle" type="button" data-order-toggle aria-expanded="false" aria-controls="order-sheet" hidden>
    <span><?= icon('receipt') ?> <span class="sr-only">Bestellzettel: </span><span data-order-count>0 Gerichte</span><span class="order-toggle__sum" data-order-sum></span></span>
    <span data-order-hint>ansehen ↑</span>
  </button>
  <div class="action-bar__row">
    <a class="btn btn--red" href="tel:<?= e($site['phone_e164']) ?>"><?= icon('phone') ?>Jetzt bestellen</a>
    <a class="btn btn--dark" href="#karte"><?= icon('list') ?>Il menù</a>
  </div>
</div>
<?php endif; ?>
<!-- ===== Einwilligung (Cookie-Banner) ===== -->
<section class="consent" data-consent role="dialog" aria-modal="false" aria-labelledby="consent-title" aria-describedby="consent-text" hidden>
  <div class="consent__inner">
    <h2 id="consent-title">Grazie per la visita! Kurz zum Datenschutz</h2>
    <p id="consent-text">Wir setzen <strong>keine Werbe- oder Analyse-Cookies</strong> ein. Technisch notwendige Speicherungen – etwa dein Bestellzettel – sind immer aktiv. Mit deiner Einwilligung laden wir zusätzlich <strong>Google Maps</strong> und speichern die Website für die <strong>Offline-Nutzung</strong> auf deinem Gerät. Deine Wahl kannst du jederzeit unten auf der Seite unter „Cookie-Einstellungen“ ändern.</p>

    <div class="consent__details" data-consent-details hidden>
      <div class="consent__row">
        <div><strong>Notwendig</strong><span>Bestellzettel, Speicherung deiner Auswahl, „Nicht jetzt“-Hinweise. Ohne diese funktioniert die Seite nicht richtig.</span></div>
        <span class="switch is-locked" aria-hidden="true"><input type="checkbox" checked disabled><span></span></span>
        <span class="sr-only">Immer aktiv</span>
      </div>
      <label class="consent__row" for="consent-comfort">
        <div><strong>Komfort: App &amp; offline</strong><span>Speichert die Website im Browser-Speicher, damit sie schneller lädt und die Speisekarte auch ohne Internet funktioniert. Keine Weitergabe an Dritte.</span></div>
        <span class="switch"><input type="checkbox" id="consent-comfort" data-consent-cat="comfort"><span aria-hidden="true"></span></span>
      </label>
      <label class="consent__row" for="consent-maps">
        <div><strong>Externe Medien: Google Maps</strong><span>Zeigt die Karte zur Anfahrt. Dabei werden Daten wie deine IP-Adresse an Google (ggf. auch in die USA) übertragen.</span></div>
        <span class="switch"><input type="checkbox" id="consent-maps" data-consent-cat="maps"><span aria-hidden="true"></span></span>
      </label>
    </div>

    <div class="consent__actions">
      <button class="btn btn--red" type="button" data-consent-all>Alle akzeptieren</button>
      <button class="btn btn--dark" type="button" data-consent-necessary>Nur notwendige</button>
      <button class="btn btn--outline" type="button" data-consent-save hidden>Auswahl speichern</button>
      <button class="btn-link" type="button" data-consent-toggle aria-expanded="false">Einstellungen</button>
    </div>
    <p class="consent__links"><a href="<?= e(url('datenschutz.php')) ?>">Datenschutzerklärung</a> · <a href="<?= e(url('impressum.php')) ?>">Impressum</a></p>
  </div>
</section>

<script src="<?= e(asset('assets/js/site.js')) ?>" defer></script>
</body>
</html>
