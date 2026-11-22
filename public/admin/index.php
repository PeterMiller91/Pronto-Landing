<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

if (!db_is_installed()) {
    redirect('/setup.php');
}
$user = require_login();

if (is_post()) {
    require_csrf();
    if (post('action') === 'closed_today') {
        $today = date('Y-m-d');
        $closing = isset($_POST['closed']);
        set_setting('closed_today', $closing ? $today : '');
        log_activity($closing ? 'Heute spontan geschlossen' : 'Heute doch geöffnet');
        flash($closing ? 'Erledigt: Die Website zeigt heute „geschlossen“.' : 'Perfetto! Die Website zeigt wieder die normalen Zeiten.');
    }
    redirect('/admin/');
}

$status = opening_status();
$closedToday = setting('closed_today') === date('Y-m-d');
$counts = count_dishes();
$specialCount = count(upcoming_special_days());
$noticeActive = active_notice() !== null;
$photoCount = count(photos());
$activity = recent_activity(5);
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Buongiorno' : ($hour < 17 ? 'Buon pomeriggio' : 'Buonasera');
$firstName = trim(explode(' ', (string) $user['name'])[0] ?? '') ?: 'Chef';

$adminTitle = 'Übersicht';
$adminKicker = 'Area riservata';
$active = 'dashboard';
$showBack = false;
require APP_DIR . '/views/admin_header.php';
?>
<div class="anim-in">
  <span class="kicker"><?= e($greeting) ?>, <?= e($firstName) ?>!</span>
  <h1>Was möchtest du heute ändern?</h1>
</div>

<section class="panel anim-in d1" aria-labelledby="live-h">
  <span id="live-h" class="hint" style="font-weight:700;letter-spacing:1px;text-transform:uppercase">So sieht es gerade auf der Website aus</span>
  <span class="status <?= $status['open'] ? 'status--open' : 'status--closed' ?>"><span class="status__dot" aria-hidden="true"></span><?= e($status['text']) ?></span>
  <form method="post" class="row-between" style="padding-top:12px;border-top:1px solid var(--line-soft)" data-autosubmit>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="closed_today">
    <div>
      <label for="closed" style="font-weight:700;display:block">Heute spontan geschlossen</label>
      <span class="hint">Krank, Urlaub, Ofen kaputt? Ein Tipp genügt – gilt nur für heute.</span>
    </div>
    <span class="switch switch--red">
      <input type="checkbox" id="closed" name="closed" value="1" role="switch" <?= $closedToday ? 'checked' : '' ?>>
      <span aria-hidden="true"></span>
    </span>
    <noscript><button class="btn btn--sm btn--dark" type="submit">Speichern</button></noscript>
  </form>
</section>

<div class="tiles anim-in d2">
  <a class="tile tile--dark" href="<?= e(url('admin/menu.php')) ?>"><?= icon('pizza') ?><strong>Speisekarte</strong><small><?= $counts['total'] ?> Gerichte<?= $counts['soldout'] ? ' · ' . $counts['soldout'] . ' ausverkauft' : '' ?></small></a>
  <a class="tile" href="<?= e(url('admin/hours.php')) ?>"><?= icon('clock') ?><strong>Öffnungszeiten</strong><small><?= $specialCount ? $specialCount . ' Sondertag' . ($specialCount > 1 ? 'e' : '') . ' geplant' : 'Wochenplan & Urlaub' ?></small></a>
  <a class="tile" href="<?= e(url('admin/notice.php')) ?>"><?= icon('megaphone') ?><strong>Hinweis-Leiste</strong><small style="<?= $noticeActive ? 'color:var(--green);font-weight:700' : '' ?>"><?= $noticeActive ? '● aktiv' : 'aus' ?></small></a>
  <a class="tile" href="<?= e(url('admin/notice.php#fotos')) ?>"><?= icon('image') ?><strong>Fotos</strong><small><?= $photoCount ?> Bild<?= $photoCount === 1 ? '' : 'er' ?></small></a>
</div>

<section class="anim-in d3">
  <h2 style="margin-bottom:6px">Zuletzt geändert</h2>
  <?php if ($activity): ?>
  <ul class="activity">
    <?php foreach ($activity as $a): ?>
    <li><span class="dot"><?= icon('check') ?></span><span><?= e($a['message']) ?></span><time datetime="<?= e($a['created_at']) ?>"><?= e(time_ago($a['created_at'])) ?></time></li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?>
  <p class="hint">Noch keine Änderungen.</p>
  <?php endif; ?>
</section>

<div class="btn-row">
  <a class="btn btn--outline" href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Website ansehen</a>
  <a class="btn btn--outline" href="<?= e(url('admin/account.php')) ?>"><?= icon('key') ?>Passwort ändern</a>
</div>
<?php require APP_DIR . '/views/admin_footer.php'; ?>
