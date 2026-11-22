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
<script src="<?= e(asset('assets/js/site.js')) ?>" defer></script>
</body>
</html>
