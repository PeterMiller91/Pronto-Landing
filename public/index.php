<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

if (!db_is_installed()) {
    http_response_code(503);
    exit('Die Website wird gerade eingerichtet. Bitte public/setup.php aufrufen.');
}

$site = config('site');
$status = opening_status();
$hours = hours_table();
$specials = upcoming_special_days();
$menu = menu_grouped();
$reviews = visible_reviews();
$hero = hero_photo();
$heroUrl = $hero ? photo_url($hero) : url('assets/img/margherita.jpg');
$heroLabel = $hero && $hero['label'] !== '' ? $hero['label'] : 'Margherita';
$mapsQuery = rawurlencode($site['maps_query']);
$routeUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . $mapsQuery;
$mapsSearchUrl = 'https://www.google.com/maps/search/?api=1&query=' . $mapsQuery;
$mapsEmbedUrl = 'https://www.google.com/maps?q=' . $mapsQuery . '&output=embed&hl=de';
$topReview = $reviews[0] ?? null;
$special = weekly_special();
$specialId = $special ? (int) $special['dish']['id'] : 0;
$cents = fn ($v) => $v === null ? '' : (string) (int) $v;

$pageTitle = 'Pizzeria Pronto Pronto Vohenstrauß – italienische Pizza am Marktplatz, Lieferservice';
$pageDescription = 'Pronto Pronto: italienische Pizza, Pasta und Dolci vom Marktplatz Vohenstrauß. Anrufen, bestellen, genießen – Lieferservice oder Abholung, täglich ab 17 Uhr außer dienstags.';
$isHome = true;
$withActionBar = true;

$dayCodes = [1 => 'Mo', 2 => 'Tu', 3 => 'We', 4 => 'Th', 5 => 'Fr', 6 => 'Sa', 0 => 'Su'];
$openingSpec = [];
foreach (weekly_hours() as $w => $r) {
    if ((int) $r['is_open'] === 1) {
        $openingSpec[] = $dayCodes[$w] . ' ' . $r['open_time'] . '-' . $r['close_time'];
    }
}
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'Restaurant',
    'name' => 'Pizzeria Pronto Pronto',
    'servesCuisine' => ['Italienisch', 'Pizza', 'Pasta'],
    'telephone' => $site['phone_e164'],
    'image' => url('assets/img/margherita.jpg'),
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $site['street'],
        'postalCode' => '92648',
        'addressLocality' => 'Vohenstrauß',
        'addressCountry' => 'DE',
    ],
    'openingHours' => $openingSpec,
];

require APP_DIR . '/views/site_header.php';
?>

<!-- ===== Hero ===== -->
<section class="hero" id="top">
  <div class="wrap">
    <a class="status <?= $status['open'] ? 'status--open' : 'status--closed' ?> anim-in" href="#zeiten">
      <span class="status__dot" aria-hidden="true"></span><?= e($status['text']) ?>
    </a>

    <figure class="hero__media anim-in d1">
      <div class="plate">
        <svg class="steam" viewBox="0 0 22 46" fill="none" stroke="#B8A58A" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M11 44c-8-8 8-14 0-22s8-14 0-20"/></svg>
        <svg class="steam" viewBox="0 0 22 46" fill="none" stroke="#B8A58A" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M11 44c-8-8 8-14 0-22s8-14 0-20"/></svg>
        <svg class="steam" viewBox="0 0 22 46" fill="none" stroke="#B8A58A" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M11 44c-8-8 8-14 0-22s8-14 0-20"/></svg>
        <div class="plate__ring" aria-hidden="true"></div>
        <div class="plate__img">
          <img src="<?= e($heroUrl) ?>" width="1200" height="1200" alt="Pizza <?= e($heroLabel) ?> von Pronto Pronto" fetchpriority="high">
        </div>
        <svg class="deco deco--1" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20C4 10 10 4 20 4c0 10-6 16-16 16z" fill="#2E6B3F"/><path d="M4 20L14 10" stroke="#BFE0C8" stroke-width="1.2"/></svg>
        <svg class="deco deco--2" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20C4 10 10 4 20 4c0 10-6 16-16 16z" fill="#3E8A52"/><path d="M4 20L14 10" stroke="#BFE0C8" stroke-width="1.2"/></svg>
        <svg class="deco deco--3" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="#C8352C"/><circle cx="9" cy="9" r="2" fill="#E87A6F"/></svg>
        <div class="badge-pronto"><strong>Pronto, pronto!</strong><span>Italienisch für: fertig – und schon unterwegs</span></div>
      </div>
      <figcaption class="hero__caption">Frisch aus dem Ofen: über 50 Pizzen von Natura bis Nudelpizza – auch als Familienpizza bis 60 × 40 cm.</figcaption>
    </figure>

    <span class="kicker anim-in d2">Benvenuti a Vohenstrauß!</span>
    <h1 class="anim-in d3">Pronto Pronto: italienische Pizza vom Marktplatz – heiß bei dir zu Hause</h1>
    <p class="lead anim-in d4">Ein Anruf genügt. Wir backen deine Pizza frisch und bringen sie dir nach Hause. Oder du holst sie ab, am <?= e($site['street']) ?>. Jeden Abend ab 17 Uhr – nur dienstags nicht.</p>
    <div class="hero__actions anim-in d5">
      <a class="btn btn--red" href="tel:<?= e($site['phone_e164']) ?>"><?= icon('phone') ?>Jetzt anrufen &amp; bestellen</a>
      <a class="btn btn--outline" href="#karte">Speisekarte ansehen</a>
    </div>
    <?php if ($topReview): ?>
    <a class="hero__proof anim-in d5" href="#stimmen" style="text-decoration:none;color:inherit">
      <?= stars((int) $topReview['rating']) ?>
      <q><?= e(strtok($topReview['text'], '!') . '!') ?></q>
      <small><?= e($topReview['author']) ?> auf <?= e($topReview['source']) ?></small>
    </a>
    <?php endif; ?>
  </div>
</section>

<div class="quick card">
  <div>
    <span class="quick__label"><?= icon('clock') ?>Aperto</span>
    <strong>täglich ab 17 Uhr</strong>
    <small>Dienstag Ruhetag</small>
  </div>
  <div>
    <span class="quick__label"><?= icon('truck') ?>Consegna</span>
    <strong>Wir liefern</strong>
    <small>oder du holst ab</small>
  </div>
</div>

<?php if ($special):
    $sd = $special['dish'];
    $sPhoto = $special['photo'] ? photo_url($special['photo']) : $heroUrl;
    $sNormal = $sd['price_cents'] !== null ? (int) $sd['price_cents'] : ($sd['price_large_cents'] !== null ? (int) $sd['price_large_cents'] : null);
    $sPrice = $special['price_cents'] ?? $sNormal;
    $sText = $special['text'] !== '' ? $special['text'] : (string) $sd['description'];
    $sAvailable = (int) $sd['is_available'] === 1; ?>
<!-- ===== Pizza der Woche ===== -->
<section class="special" id="pizza-der-woche" aria-labelledby="special-title">
  <div class="wrap">
    <article class="special__card">
      <div class="special__media">
        <img src="<?= e($sPhoto) ?>" alt="<?= e($sd['name']) ?> – Pizza der Woche bei Pronto Pronto" loading="lazy" width="800" height="800">
        <span class="special__stamp" aria-hidden="true">Solo questa settimana!</span>
      </div>
      <div class="special__body">
        <span class="kicker kicker--gold">Pizza della settimana</span>
        <h2 id="special-title"><span class="special__label">Pizza der Woche:</span> <?= e($sd['name']) ?></h2>
        <?php if ($sText !== ''): ?><p><?= e($sText) ?></p><?php endif; ?>
        <?php if ($sPrice !== null): ?>
        <p class="special__price">
          <strong><?= e(format_price($sPrice)) ?></strong>
          <?php if ($special['price_cents'] !== null && $sNormal !== null && $sNormal > $special['price_cents']): ?><s aria-label="statt <?= e(format_price($sNormal)) ?>"><?= e(format_price($sNormal)) ?></s><span class="special__save">Du sparst <?= e(format_price($sNormal - (int) $special['price_cents'])) ?></span><?php endif; ?>
        </p>
        <?php endif; ?>
        <?php if ($special['until'] !== ''): ?>
        <small class="special__until"><?= icon('clock') ?>Nur bis <?= e(WEEKDAYS[(int) (new DateTimeImmutable($special['until']))->format('w')]) ?>, <?= e(german_date($special['until'])) ?></small>
        <?php endif; ?>
        <div class="special__actions">
          <?php if ($sAvailable): ?>
          <button class="btn btn--gold" type="button" data-add data-id="<?= (int) $sd['id'] ?>" data-nr="<?= e($sd['nr']) ?>" data-name="<?= e($sd['name']) ?>" data-variant="w" data-label="Pizza der Woche" data-price="<?= e($cents($sPrice)) ?>">
            <?= icon('plus') ?><span data-add-text>Auf den Bestellzettel</span>
          </button>
          <?php else: ?>
          <span class="dish__soldout" style="color:#F3B4AE">Heute leider schon ausverkauft</span>
          <?php endif; ?>
          <a class="btn btn--ghost" href="tel:<?= e($site['phone_e164']) ?>"><?= icon('phone') ?>Jetzt bestellen</a>
        </div>
      </div>
    </article>
  </div>
</section>
<?php endif; ?>

<div class="marquee" aria-hidden="true">
  <div class="marquee__track">
    <?php for ($i = 0; $i < 2; $i++): ?>
    <span>Margherita ✦ Diavolo ✦ Calzone ✦ Quattro Formaggi ✦ Familienpizza ✦ Penne all’Arrabbiata ✦ Buon appetito ✦ Pronto Pronto ✦ Mamma mia ✦</span>
    <?php endfor; ?>
  </div>
</div>

<!-- ===== Speisekarte ===== -->
<section class="menu" id="karte" aria-labelledby="karte-title">
  <div class="wrap">
    <div class="menu__head">
      <span class="kicker">Il menù</span>
      <h2 class="section-title" id="karte-title">Such dir deine Pizza aus – wir legen sofort los</h2>
      <p class="lead">Tipp auf <strong style="color:var(--green)">+</strong> und sammle alles auf deinem Bestellzettel. Beim Anruf liest du einfach die Nummern vor.</p>
    </div>

    <div class="tabs" role="tablist" aria-label="Kategorien">
      <?php foreach ($menu as $i => $group): ?>
      <button class="tab" type="button" role="tab" id="tab-<?= e($group['category']['slug']) ?>" aria-controls="panel-<?= e($group['category']['slug']) ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" tabindex="<?= $i === 0 ? '0' : '-1' ?>"><?= e($group['category']['name']) ?></button>
      <?php endforeach; ?>
    </div>

    <?php foreach ($menu as $i => $group): ?>
    <div class="menu__panel card" role="tabpanel" id="panel-<?= e($group['category']['slug']) ?>" aria-labelledby="tab-<?= e($group['category']['slug']) ?>" <?= $i === 0 ? '' : 'hidden' ?>>
      <ul class="menu__list">
        <?php
            $cat = $group['category'];
            [$sizeS, $sizeL] = size_labels($cat);
            $labelPrefix = $cat['slug'] === 'familienpizza' ? 'Familienpizza ' : '';
            foreach ($group['dishes'] as $d):
            $available = (int) $d['is_available'] === 1;
            $price = dish_price_label($d, $cat);
            $adds = additive_numbers((string) ($d['additives'] ?? ''));
            $twoPrices = $d['price_cents'] !== null && $d['price_large_cents'] !== null; ?>
        <li class="dish<?= $available ? '' : ' dish--soldout' ?>">
          <?php if ($d['nr'] !== ''): ?><span class="dish__nr"><?= e($d['nr']) ?></span><?php else: ?><span class="dish__nr dish__nr--empty" aria-hidden="true"></span><?php endif; ?>
          <div class="dish__body">
            <div class="dish__top">
              <span class="dish__name"><?= e($d['name']) ?></span>
              <?php if (!$twoPrices): ?>
              <span class="dish__dots" aria-hidden="true"></span>
              <span class="dish__price"><?= e($price) ?></span>
              <?php endif; ?>
            </div>
            <?php if ($twoPrices): ?><span class="dish__prices"><?= e($price) ?></span><?php endif; ?>
            <?php if ($d['description'] !== '' || $adds): ?><span class="dish__desc"><?= e($d['description']) ?><?php if ($adds): ?> <a class="dish__adds" href="#zusatzstoffe" title="Zusatzstoffe: <?= e(implode(', ', array_map(fn ($n) => ADDITIVES_LEGEND[$n], $adds))) ?>">(<?= e(implode(',', $adds)) ?>)</a><?php endif; ?></span><?php endif; ?>
            <?php $isSpecial = (int) $d['id'] === $specialId; ?>
            <?php if (!$available || $isSpecial || $d['is_vegetarian'] || $d['is_spicy'] || $d['is_new'] || $d['is_recommended']): ?>
            <span class="dish__tags">
              <?php if (!$available): ?><span class="dish__soldout">Heute leider aus</span><?php endif; ?>
              <?php if ($isSpecial): ?><a class="tag tag--week" href="#pizza-der-woche">Pizza der Woche</a><?php endif; ?>
              <?php if ($d['is_recommended']): ?><span class="tag tag--rec">Consiglio della casa</span><?php endif; ?>
              <?php if ($d['is_new']): ?><span class="tag tag--new">Neu</span><?php endif; ?>
              <?php if ($d['is_vegetarian']): ?><span class="tag">Vegetarisch</span><?php endif; ?>
              <?php if ($d['is_spicy']): ?><span class="tag tag--hot">Scharf</span><?php endif; ?>
            </span>
            <?php endif; ?>
            <?php if ($twoPrices && $available): ?>
            <div class="size-pick" id="size-<?= (int) $d['id'] ?>" role="group" aria-label="Größe für <?= e($d['name']) ?> wählen" hidden>
              <button type="button" class="size-pick__btn" data-add data-id="<?= (int) $d['id'] ?>" data-nr="<?= e($d['nr']) ?>" data-name="<?= e($d['name']) ?>" data-variant="k" data-label="<?= e($labelPrefix . $sizeS) ?>" data-price="<?= e($cents($d['price_cents'])) ?>"><?= e($sizeS) ?> <b><?= e(format_price((int) $d['price_cents'])) ?></b> <?= icon('plus') ?></button>
              <button type="button" class="size-pick__btn" data-add data-id="<?= (int) $d['id'] ?>" data-nr="<?= e($d['nr']) ?>" data-name="<?= e($d['name']) ?>" data-variant="g" data-label="<?= e($labelPrefix . $sizeL) ?>" data-price="<?= e($cents($d['price_large_cents'])) ?>"><?= e($sizeL) ?> <b><?= e(format_price((int) $d['price_large_cents'])) ?></b> <?= icon('plus') ?></button>
            </div>
            <?php endif; ?>
          </div>
          <?php if ($twoPrices): ?>
          <button class="add-btn" type="button" data-pick="size-<?= (int) $d['id'] ?>" data-id="<?= (int) $d['id'] ?>" data-nr="<?= e($d['nr']) ?>" data-name="<?= e($d['name']) ?>" data-count="0" aria-expanded="false" aria-controls="size-<?= (int) $d['id'] ?>" aria-label="<?= e($d['name']) ?>: Größe wählen und auf den Bestellzettel"<?= $available ? '' : ' disabled' ?>>
            <?= icon('plus') ?><span class="add-btn__count" aria-hidden="true"></span>
          </button>
          <?php else: ?>
          <button class="add-btn" type="button" data-add data-id="<?= (int) $d['id'] ?>" data-nr="<?= e($d['nr']) ?>" data-name="<?= e($d['name']) ?>" data-variant="" data-label="<?= e($labelPrefix) ?>" data-price="<?= e($cents($d['price_cents'] ?? $d['price_large_cents'])) ?>" data-count="0" aria-label="<?= e($d['name']) ?> auf den Bestellzettel"<?= $available ? '' : ' disabled' ?>>
            <?= icon('plus') ?><span class="add-btn__count" aria-hidden="true"></span>
          </button>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php if (trim((string) ($cat['note'] ?? '')) !== ''): ?><p class="menu__catnote"><?= e($cat['note']) ?></p><?php endif; ?>
      <p class="menu__note">Extra-Wünsche oder Fragen zu Allergenen? Frag uns einfach am Telefon.</p>
    </div>
    <?php endforeach; ?>

    <details class="additives" id="zusatzstoffe">
      <summary>Zusatzstoffe &amp; Hinweise</summary>
      <ol>
        <?php foreach (ADDITIVES_LEGEND as $n => $label): ?><li value="<?= $n ?>"><?= e($label) ?></li><?php endforeach; ?>
      </ol>
      <p>Wir verwenden für alle unsere Speisen Vorderschinken. Alle Preise inkl. Bedienung und MwSt.</p>
    </details>
  </div>
</section>

<div class="check" aria-hidden="true"></div>

<!-- ===== Unsere Küche ===== -->
<section class="kitchen" id="kueche" aria-labelledby="kueche-title">
  <div class="wrap">
    <div class="kitchen__head">
      <span class="kicker kicker--gold">La nostra cucina</span>
      <h2 class="section-title" id="kueche-title">Drei Gründe, heute bei Pronto Pronto zu bestellen</h2>
    </div>
    <div class="reasons">
      <div class="reason">
        <div class="reason__icon"><?= icon('pizza') ?></div>
        <div>
          <h3>Frisch gebacken, nie aufgewärmt</h3>
          <p>Deine Pizza kommt erst in den Ofen, wenn du bestellt hast. „Super lecker und frisch“, schreiben unsere Gäste.</p>
        </div>
      </div>
      <div class="reason">
        <div class="reason__icon"><?= icon('truck') ?></div>
        <div>
          <h3>Pronto heißt schnell</h3>
          <p><?= e((string) setting('delivery_note', 'Wir liefern in Vohenstrauß und Umgebung.')) ?> „Geliefert und noch richtig heiß“ – so soll es sein.</p>
        </div>
      </div>
      <div class="reason">
        <div class="reason__icon"><?= icon('pin') ?></div>
        <div>
          <h3>Mitten am Marktplatz</h3>
          <p>Auf dem Heimweg abholen oder gleich bei uns am Tisch essen: <?= e($site['street']) ?>, ab 17 Uhr.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== Gästestimmen ===== -->
<?php if ($reviews): ?>
<section class="reviews" id="stimmen" aria-labelledby="stimmen-title" data-reviews>
  <div class="wrap reviews__inner">
    <span class="kicker kicker--light" id="stimmen-title">Dicono di noi – das sagen unsere Gäste</span>
    <?php foreach ($reviews as $i => $r): ?>
    <figure class="review" data-review <?= $i === 0 ? '' : 'hidden' ?>>
      <?= stars((int) $r['rating']) ?>
      <blockquote>„<?= e($r['text']) ?>“</blockquote>
      <figcaption><?= e($r['author']) ?> <span>· <?= e($r['source']) ?>-Bewertung</span></figcaption>
    </figure>
    <?php endforeach; ?>
    <div class="reviews__nav">
      <div class="dots" aria-hidden="true">
        <?php foreach ($reviews as $i => $r): ?><span class="<?= $i === 0 ? 'is-active' : '' ?>"></span><?php endforeach; ?>
      </div>
      <button class="btn-ghost-light" type="button" data-review-next>Nächste Stimme →</button>
    </div>
    <a class="reviews__more" href="<?= e($mapsSearchUrl) ?>" target="_blank" rel="noopener">Alle Bewertungen auf Google ansehen ↗</a>
  </div>
</section>
<?php endif; ?>

<!-- ===== Öffnungszeiten & Kontakt ===== -->
<section class="visit" id="zeiten" aria-label="Öffnungszeiten und Kontakt">
  <div class="wrap">
    <div class="hours card">
      <span class="kicker">Orari</span>
      <h2 class="section-title" style="font-size:28px">Wann der Ofen heiß ist</h2>
      <ul class="hours__list">
        <?php foreach ($hours as $h): ?>
        <li class="hours__row<?= $h['open'] ? '' : ' hours__row--closed' ?><?= $h['today'] ? ' hours__row--today' : '' ?>">
          <span><?= e($h['name']) ?><?php if ($h['today']): ?><span class="oggi">Oggi</span><?php endif; ?></span>
          <strong><?= e($h['times']) ?></strong>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php if ($specials): ?>
      <div class="hours__specials">
        <strong>Sondertage</strong>
        <ul>
          <?php foreach ($specials as $s): ?>
          <li><?= e(german_date($s['date_from'])) ?><?= $s['date_to'] !== $s['date_from'] ? '–' . e(german_date($s['date_to'])) : '' ?> <?= e($s['title']) ?>: <?= (int) $s['is_closed'] === 1 ? 'geschlossen' : e($s['open_time'] . ' – ' . $s['close_time']) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
      <p class="hours__note">Dienstags gönnt sich unser Pizzaiolo ein bisschen dolce far niente.</p>
    </div>

    <div class="contact" id="kontakt">
      <span class="kicker">Chiamaci!</span>
      <h2>Hunger? Ruf an – in einer Minute ist bestellt</h2>
      <a class="contact__phone" href="tel:<?= e($site['phone_e164']) ?>"><?= e($site['phone']) ?></a>
      <address><?= icon('pin') ?><span>Pizzeria Pronto Pronto<br><?= e($site['street']) ?>, <?= e($site['zip_city']) ?></span></address>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px">
      <div class="map" data-map data-src="<?= e($mapsEmbedUrl) ?>">
        <div class="map__consent">
          <span class="map__pin"><?= icon('pin') ?></span>
          <strong>So findest du uns: <?= e($site['street']) ?></strong>
          <button class="btn btn--dark" type="button" data-map-load>Karte anzeigen</button>
          <p>Beim Anzeigen lädt Google Maps und erhält dabei Daten wie deine IP-Adresse. Mehr dazu in der <a href="<?= e(url('datenschutz.php')) ?>">Datenschutzerklärung</a>.</p>
          <label style="font-size:13px;display:flex;gap:8px;align-items:center;min-height:44px"><input type="checkbox" data-map-remember> Karte künftig immer anzeigen</label>
        </div>
      </div>
      <div class="map__actions">
        <a class="btn btn--outline" href="<?= e($routeUrl) ?>" target="_blank" rel="noopener"><?= icon('route') ?>Andiamo – Route planen</a>
      </div>
    </div>
  </div>
</section>

<!-- ===== Als App speichern ===== -->
<section class="app-promo" data-app-promo hidden aria-labelledby="app-title">
  <div class="wrap">
    <div class="app-promo__card">
      <img src="<?= e(url('assets/img/icon-192.png')) ?>" alt="" width="64" height="64" class="app-promo__icon">
      <div class="app-promo__body">
        <h2 id="app-title">Pronto Pronto aufs Handy holen</h2>
        <p>Ein Tipp auf das Pizza-Symbol – und du bist bei Speisekarte, Pizza der Woche und Telefon. Ohne App-Store, ohne Anmeldung.</p>
        <p class="app-promo__ios" data-ios-hint hidden>Tippe unten auf <strong>Teilen</strong> <span aria-hidden="true">⬆︎</span> und dann auf <strong>„Zum Home-Bildschirm“</strong>.</p>
        <div class="app-promo__actions">
          <button class="btn btn--red" type="button" data-install><?= icon('plus') ?>Zum Startbildschirm</button>
          <button class="btn-link" type="button" data-install-dismiss>Nicht jetzt</button>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require APP_DIR . '/views/site_footer.php'; ?>
