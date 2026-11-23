<?php
/** @var string $pageTitle */
/** @var string $pageDescription */
/** @var bool $isHome */
$isHome ??= false;
$site = config('site');
$notice = active_notice();
$home = $isHome ? '' : url('/');
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<meta name="theme-color" content="#A8201A">
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="manifest" href="<?= e(url('manifest.webmanifest')) ?>">
<link rel="apple-touch-icon" href="<?= e(url('assets/img/apple-touch-icon.png')) ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Pronto Pronto">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="preload" href="<?= e(url('assets/fonts/playfair-display-latin-800-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDescription) ?>">
<meta property="og:type" content="restaurant.restaurant">
<meta property="og:image" content="<?= e(url('assets/img/margherita.jpg')) ?>">
<?php if (!empty($structuredData)): ?>
<script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endif; ?>
</head>
<body>
<a class="skip-link" href="#inhalt">Zum Inhalt springen</a>
<div class="tricolore" aria-hidden="true"><span></span><span></span><span></span></div>
<?php if ($notice): ?>
<div class="notice notice--<?= e($notice['color']) ?>" role="status"><?= icon('megaphone') ?><span><?= e($notice['text']) ?></span></div>
<?php endif; ?>
<header class="site-header">
  <div class="wrap">
    <a class="brand" href="<?= e($home) ?>#top" aria-label="<?= e($site['name']) ?> – zur Startseite">
      <span class="brand__name"><?= e($site['name']) ?></span>
      <span class="brand__sub"><?= e($site['tagline']) ?></span>
    </a>
    <nav class="main-nav" aria-label="Hauptnavigation">
      <a href="<?= e($home) ?>#karte">Speisekarte</a>
      <a href="<?= e($home) ?>#kueche">Unsere Küche</a>
      <a href="<?= e($home) ?>#zeiten">Öffnungszeiten</a>
      <a href="<?= e($home) ?>#kontakt">Kontakt</a>
      <a class="btn btn--red header-phone" href="tel:<?= e($site['phone_e164']) ?>"><?= icon('phone') ?><?= e($site['phone']) ?></a>
    </nav>
    <div class="header-actions">
      <a class="icon-btn icon-btn--red" href="tel:<?= e($site['phone_e164']) ?>" aria-label="Anrufen: <?= e($site['phone']) ?>"><?= icon('phone') ?></a>
      <button class="icon-btn menu-btn" type="button" aria-expanded="false" aria-controls="mobile-nav" aria-label="Menü öffnen">
        <?= icon('menu', 'icon icon-open') ?><?= icon('close', 'icon icon-close') ?>
      </button>
    </div>
  </div>
  <nav class="mobile-nav" id="mobile-nav" aria-label="Hauptnavigation" hidden>
    <a href="<?= e($home) ?>#karte"><strong>Speisekarte</strong><em>Il menù</em></a>
    <a href="<?= e($home) ?>#kueche"><strong>Unsere Küche</strong><em>La nostra cucina</em></a>
    <a href="<?= e($home) ?>#zeiten"><strong>Öffnungszeiten</strong><em>Orari</em></a>
    <a href="<?= e($home) ?>#kontakt"><strong>Kontakt &amp; Anfahrt</strong><em>Contatti</em></a>
    <?php if ($isHome): ?><a href="#app" data-install-open><strong>Als App installieren</strong><em>Sul tuo telefono</em></a><?php endif; ?>
  </nav>
</header>
<main id="inhalt">
