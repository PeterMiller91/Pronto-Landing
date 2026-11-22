<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

if (!db_is_installed()) {
    redirect('/setup.php');
}
require_login();

if (is_post()) {
    require_csrf();
    $active = isset($_POST['special_active']);
    $dishId = (int) post('special_dish_id', '0');
    $text = mb_substr(post('special_text'), 0, 240);
    $priceIn = post('special_price');
    $price = parse_price($priceIn);
    $until = post('special_until');
    $photoId = (int) post('special_photo_id', '0');

    $errors = [];
    if ($active && !dish($dishId)) {
        $errors[] = 'Bitte eine Pizza auswählen.';
    }
    if ($priceIn !== '' && $price === null) {
        $errors[] = 'Der Aktionspreis ist ungültig (Beispiel: 8,90).';
    }
    if ($until !== '' && !valid_date($until)) {
        $errors[] = 'Bitte ein gültiges Enddatum wählen.';
    }
    if ($photoId > 0 && !db_one('SELECT id FROM photos WHERE id = ?', [$photoId])) {
        $photoId = 0;
    }
    if ($errors) {
        flash(implode(' ', $errors), 'error');
        redirect('/admin/special.php');
    }

    set_setting('special_active', $active ? '1' : '0');
    set_setting('special_dish_id', (string) $dishId);
    set_setting('special_text', $text);
    set_setting('special_price_cents', $price === null ? '' : (string) $price);
    set_setting('special_until', $until);
    set_setting('special_photo_id', (string) $photoId);

    $d = dish($dishId);
    log_activity($active && $d ? 'Pizza der Woche: „' . $d['name'] . '“' : 'Pizza der Woche ausgeschaltet');
    flash($active ? 'Perfetto! Die Pizza der Woche ist online.' : 'Pizza der Woche ist ausgeschaltet.');
    redirect('/admin/special.php');
}

$active = setting('special_active') === '1';
$dishId = (int) setting('special_dish_id', '0');
$text = (string) setting('special_text', '');
$priceCents = (string) setting('special_price_cents', '');
$until = (string) setting('special_until', '') ?: next_sunday();
$photoId = (int) setting('special_photo_id', '0');
$live = weekly_special();
$menu = menu_grouped();
$photos = photos();
$expired = $active && $live === null && $dishId > 0;

$adminTitle = 'Pizza der Woche';
$adminKicker = 'Pizza della settimana';
require APP_DIR . '/views/admin_header.php';
?>
<?php if ($live): ?>
<div class="alert alert--ok" role="status">Online: <strong><?= e($live['dish']['name']) ?></strong><?= $live['until'] ? ' – bis ' . e(german_date($live['until'], true)) : '' ?></div>
<?php elseif ($expired): ?>
<div class="alert" role="status">Die Aktion ist abgelaufen und wird nicht mehr angezeigt. Wähle ein neues Enddatum oder eine neue Pizza.</div>
<?php endif; ?>

<form class="panel" method="post" action="">
  <?= csrf_field() ?>
  <div class="row-between">
    <div>
      <h2 id="special-h">Pizza der Woche anzeigen</h2>
      <span class="hint">Erscheint groß direkt unter dem Startbild.</span>
    </div>
    <span class="switch">
      <input type="checkbox" name="special_active" value="1" role="switch" aria-labelledby="special-h" <?= $active ? 'checked' : '' ?>>
      <span aria-hidden="true"></span>
    </span>
  </div>

  <div class="field">
    <label for="special_dish_id">Welche Pizza?</label>
    <select class="input" id="special_dish_id" name="special_dish_id">
      <option value="0">– bitte wählen –</option>
      <?php foreach ($menu as $group): ?>
      <optgroup label="<?= e($group['category']['name']) ?>">
        <?php foreach ($group['dishes'] as $d): ?>
        <option value="<?= (int) $d['id'] ?>" <?= (int) $d['id'] === $dishId ? 'selected' : '' ?>><?= e($d['nr'] . ' · ' . $d['name']) ?><?= $d['price_cents'] !== null ? ' (' . e(format_price((int) $d['price_cents'])) . ')' : '' ?></option>
        <?php endforeach; ?>
      </optgroup>
      <?php endforeach; ?>
    </select>
    <span class="hint">Neue Pizza? Erst unter <a href="<?= e(url('admin/menu.php?edit=new')) ?>">Speisekarte</a> anlegen, dann hier auswählen.</span>
  </div>

  <div class="field">
    <label for="special_text">Warum gerade diese Pizza? <small>(<span data-count-for="special_text"><?= 240 - mb_strlen($text) ?></span> Zeichen übrig)</small></label>
    <textarea class="textarea" id="special_text" name="special_text" maxlength="240" rows="3" data-count-source placeholder="z. B. Mit scharfer ’Nduja aus Kalabrien und süßen roten Zwiebeln – nur diese Woche."><?= e($text) ?></textarea>
    <span class="hint">Tipp: konkrete Zutaten und Herkunft verkaufen besser als „lecker“. Leer = Beschreibung aus der Speisekarte.</span>
  </div>

  <div class="grid-2">
    <div class="field">
      <label for="special_price">Aktionspreis <small>(optional)</small></label>
      <input class="input" id="special_price" name="special_price" inputmode="decimal" placeholder="z. B. 8,90" value="<?= e($priceCents === '' ? '' : number_format(((int) $priceCents) / 100, 2, ',', '')) ?>">
    </div>
    <div class="field">
      <label for="special_until">Gültig bis</label>
      <input class="input" type="date" id="special_until" name="special_until" value="<?= e($until) ?>">
    </div>
  </div>
  <p class="hint" style="margin-top:-6px">Mit Aktionspreis zeigt die Website den normalen Preis durchgestrichen und „Du sparst …“. Nach dem Enddatum verschwindet die Aktion automatisch.</p>

  <fieldset class="field" style="border:0;margin:0;padding:0">
    <legend style="margin-bottom:8px">Foto</legend>
    <div class="photo-pick">
      <label class="photo-pick__item">
        <input type="radio" name="special_photo_id" value="0" <?= $photoId === 0 ? 'checked' : '' ?>>
        <span class="photo-pick__none">Startbild verwenden</span>
      </label>
      <?php foreach ($photos as $p): ?>
      <label class="photo-pick__item">
        <input type="radio" name="special_photo_id" value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === $photoId ? 'checked' : '' ?>>
        <img src="<?= e(photo_url($p, true)) ?>" alt="<?= e($p['label'] ?: 'Foto') ?>" loading="lazy">
      </label>
      <?php endforeach; ?>
    </div>
    <span class="hint">Eigenes Foto? Unter <a href="<?= e(url('admin/notice.php#fotos')) ?>">Fotos</a> hochladen.</span>
  </fieldset>

  <button class="btn btn--green btn--block" type="submit">Speichern – perfetto!</button>
</form>

<a class="btn btn--outline" href="<?= e(url('/#pizza-der-woche')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Auf der Website ansehen</a>
<?php $active = 'dashboard'; require APP_DIR . '/views/admin_footer.php'; ?>
