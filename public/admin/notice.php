<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

if (!db_is_installed()) {
    redirect('/setup.php');
}
require_login();

$colors = ['red' => ['Rosso', '#A8201A'], 'green' => ['Verde', '#2E6B3F'], 'gold' => ['Oro', '#E9B949']];

if (is_post()) {
    // Datei größer als post_max_size → PHP verwirft das komplette Formular
    if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('Das Foto ist zu groß für den Server. Bitte ein kleineres Foto wählen.', 'error');
        redirect('/admin/notice.php#fotos');
    }
    require_csrf();
    $action = post('action');

    if ($action === 'save_notice') {
        $text = mb_substr(post('notice_text'), 0, 120);
        $color = post('notice_color', 'red');
        $until = post('notice_until');
        $activeOn = isset($_POST['notice_active']);
        if ($activeOn && $text === '') {
            flash('Bitte einen Text für die Hinweis-Leiste eingeben.', 'error');
            redirect('/admin/notice.php');
        }
        set_setting('notice_active', $activeOn ? '1' : '0');
        set_setting('notice_text', $text);
        set_setting('notice_color', array_key_exists($color, $colors) ? $color : 'red');
        set_setting('notice_until', valid_date($until) ? $until : '');
        log_activity($activeOn ? 'Hinweis-Leiste aktualisiert' : 'Hinweis-Leiste ausgeschaltet');
        flash('Perfetto! Die Hinweis-Leiste ist gespeichert.');
        redirect('/admin/notice.php');
    }

    if ($action === 'upload') {
        try {
            $name = store_uploaded_image($_FILES['photo'] ?? []);
            $label = mb_substr(post('label'), 0, 120);
            $isFirst = (int) db_value('SELECT COUNT(*) FROM photos') === 0;
            db_insert('photos', ['filename' => $name, 'label' => $label, 'is_hero' => $isFirst ? 1 : 0, 'sort' => 0, 'created_at' => now()]);
            log_activity('Neues Foto hochgeladen' . ($label !== '' ? ' („' . $label . '“)' : ''));
            flash('Foto hochgeladen. Bellissimo!');
        } catch (RuntimeException $ex) {
            flash($ex->getMessage(), 'error');
        }
        redirect('/admin/notice.php#fotos');
    }

    $photo = db_one('SELECT * FROM photos WHERE id = ?', [(int) post('id')]);
    if ($photo && $action === 'hero') {
        db_exec('UPDATE photos SET is_hero = 0');
        db_update('photos', ['is_hero' => 1], 'id = ?', [$photo['id']]);
        log_activity('Neues Startbild gewählt');
        flash('Das Foto ist jetzt das Startbild der Website.');
        redirect('/admin/notice.php#fotos');
    }
    if ($photo && $action === 'label') {
        db_update('photos', ['label' => mb_substr(post('label'), 0, 120)], 'id = ?', [$photo['id']]);
        flash('Bildtitel gespeichert.');
        redirect('/admin/notice.php#fotos');
    }
    if ($photo && $action === 'delete_photo') {
        delete_photo_files($photo);
        db_exec('DELETE FROM photos WHERE id = ?', [(int) $photo['id']]);
        if ((int) $photo['is_hero'] === 1) {
            $next = db_value('SELECT id FROM photos ORDER BY id DESC LIMIT 1');
            if ($next !== null) {
                db_update('photos', ['is_hero' => 1], 'id = ?', [$next]);
            }
        }
        log_activity('Foto gelöscht');
        flash('Foto gelöscht.');
        redirect('/admin/notice.php#fotos');
    }
    redirect('/admin/notice.php');
}

$noticeActive = setting('notice_active') === '1';
$noticeText = (string) setting('notice_text', '');
$noticeColor = (string) setting('notice_color', 'red');
$noticeUntil = (string) setting('notice_until', '');
$photos = photos();
$presets = [
    'Heute geschlossen – morgen wieder für euch da!',
    'Betriebsurlaub bis [Datum] – ci vediamo presto!',
    'Neu auf der Karte: [Gericht] – provala!',
];

$adminTitle = 'Hinweis-Leiste & Fotos';
$adminKicker = 'Avvisi & foto';
$active = 'notice';
require APP_DIR . '/views/admin_header.php';
?>
<form class="panel" method="post" action="" data-notice-form>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save_notice">
  <div class="row-between">
    <div>
      <h2 id="notice-h">Hinweis-Leiste</h2>
      <span class="hint">Erscheint ganz oben auf der Website.</span>
    </div>
    <span class="switch">
      <input type="checkbox" name="notice_active" value="1" role="switch" aria-labelledby="notice-h" <?= $noticeActive ? 'checked' : '' ?> data-notice-active>
      <span aria-hidden="true"></span>
    </span>
  </div>

  <div class="preview" aria-label="Vorschau">
    <span class="preview__label">Vorschau</span>
    <div class="notice notice--<?= e($noticeColor) ?>" data-notice-preview <?= $noticeActive ? '' : 'hidden' ?>><?= icon('megaphone') ?><span data-notice-preview-text><?= e($noticeText ?: 'Dein Text erscheint hier') ?></span></div>
    <div class="preview__site"><b>Pronto Pronto</b><span class="hint">Website</span></div>
  </div>

  <div class="field">
    <label for="notice_text">Text <small>(<span data-notice-count><?= 120 - mb_strlen($noticeText) ?></span> Zeichen übrig)</small></label>
    <textarea class="textarea" id="notice_text" name="notice_text" maxlength="120" rows="2" data-notice-text><?= e($noticeText) ?></textarea>
    <div class="chips">
      <?php foreach ($presets as $p): ?>
      <button class="btn btn--outline btn--sm" type="button" style="min-height:36px;font-size:13px;border-width:1.5px" data-preset="<?= e($p) ?>"><?= e(mb_strimwidth($p, 0, 30, '…')) ?></button>
      <?php endforeach; ?>
    </div>
  </div>

  <fieldset class="field" style="border:0;margin:0;padding:0">
    <legend style="margin-bottom:8px">Farbe</legend>
    <div class="chips">
      <?php foreach ($colors as $key => [$label, $hex]): ?>
      <label class="chip swatch"><input type="radio" name="notice_color" value="<?= e($key) ?>" <?= $noticeColor === $key ? 'checked' : '' ?> data-notice-color><span><i style="background:<?= e($hex) ?>"></i><?= e($label) ?></span></label>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <div class="field">
    <label for="notice_until">Automatisch ausblenden nach dem <small>(optional)</small></label>
    <input class="input" style="max-width:220px" type="date" id="notice_until" name="notice_until" value="<?= e($noticeUntil) ?>">
  </div>
  <button class="btn btn--green btn--block" type="submit">Speichern &amp; veröffentlichen</button>
</form>

<section id="fotos" style="display:flex;flex-direction:column;gap:10px">
  <div>
    <h2>Fotos</h2>
    <p class="hint">Tipp: das fertige Gericht von oben, bei Tageslicht. Wir verkleinern automatisch. Das Startbild erscheint groß auf der Website.</p>
  </div>
  <div class="photo-grid">
    <form method="post" action="" enctype="multipart/form-data" class="upload" data-upload>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="upload">
      <input type="hidden" name="label" value="" data-upload-label>
      <?= icon('plus') ?>
      <span>Foto hochladen</span>
      <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" aria-label="Foto hochladen" data-upload-input>
    </form>
    <?php foreach ($photos as $p): ?>
    <div class="photo">
      <img src="<?= e(photo_url($p, true)) ?>" alt="<?= e($p['label'] ?: 'Foto') ?>" loading="lazy">
      <?php if ((int) $p['is_hero'] === 1): ?><span class="photo__hero">STARTBILD</span><?php endif; ?>
      <?php if ($p['label'] !== ''): ?><span class="photo__label"><?= e($p['label']) ?></span><?php endif; ?>
      <div class="photo__actions">
        <?php if ((int) $p['is_hero'] !== 1): ?>
        <form method="post" action="">
          <?= csrf_field() ?><input type="hidden" name="action" value="hero"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <button type="submit" aria-label="Als Startbild verwenden" title="Als Startbild"><?= icon('star') ?></button>
        </form>
        <?php endif; ?>
        <form method="post" action="" data-confirm="Foto wirklich löschen?">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete_photo"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <button type="submit" aria-label="Foto löschen" title="Löschen"><?= icon('trash') ?></button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if ($photos): ?>
  <details class="panel">
    <summary>Bildtitel bearbeiten</summary>
    <?php foreach ($photos as $p): ?>
    <form method="post" action="" class="row-between" style="gap:8px">
      <?= csrf_field() ?><input type="hidden" name="action" value="label"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
      <img src="<?= e(photo_url($p, true)) ?>" alt="" width="44" height="44" style="border-radius:8px;object-fit:cover;width:44px;height:44px">
      <label class="sr-only" for="label-<?= (int) $p['id'] ?>">Bildtitel</label>
      <input class="input" id="label-<?= (int) $p['id'] ?>" name="label" maxlength="120" value="<?= e($p['label']) ?>" placeholder="z. B. Diavola">
      <button class="round-btn" type="submit" aria-label="Titel speichern"><?= icon('check') ?></button>
    </form>
    <?php endforeach; ?>
  </details>
  <?php endif; ?>
</section>
<?php require APP_DIR . '/views/admin_footer.php'; ?>
