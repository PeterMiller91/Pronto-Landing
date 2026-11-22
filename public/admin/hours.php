<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

if (!db_is_installed()) {
    redirect('/setup.php');
}
require_login();

if (is_post()) {
    require_csrf();
    $action = post('action');

    if ($action === 'save_week') {
        $errors = [];
        $rows = [];
        foreach (WEEKDAYS as $w => $name) {
            $open = isset($_POST['open'][$w]);
            $from = (string) ($_POST['from'][$w] ?? '17:00');
            $to = (string) ($_POST['to'][$w] ?? '23:00');
            $from2 = trim((string) ($_POST['from2'][$w] ?? ''));
            $to2 = trim((string) ($_POST['to2'][$w] ?? ''));
            if ($open && (!valid_time($from) || !valid_time($to))) {
                $errors[] = $name . ': Bitte gültige Uhrzeiten eingeben.';
            }
            $hasShift2 = $from2 !== '' || $to2 !== '';
            if ($open && $hasShift2 && (!valid_time($from2) || !valid_time($to2))) {
                $errors[] = $name . ': Die zweite Schicht braucht Beginn und Ende.';
            }
            $rows[$w] = [
                'is_open' => $open ? 1 : 0,
                'open_time' => valid_time($from) ? $from : '17:00',
                'close_time' => valid_time($to) ? $to : '23:00',
                'open_time2' => $hasShift2 && valid_time($from2) ? $from2 : null,
                'close_time2' => $hasShift2 && valid_time($to2) ? $to2 : null,
            ];
        }
        if ($errors) {
            flash(implode(' ', $errors), 'error');
            redirect('/admin/hours.php');
        }
        foreach ($rows as $w => $data) {
            if (db_value('SELECT COUNT(*) FROM opening_hours WHERE weekday = ?', [$w])) {
                db_update('opening_hours', $data, 'weekday = ?', [$w]);
            } else {
                db_insert('opening_hours', ['weekday' => $w] + $data);
            }
        }
        log_activity('Öffnungszeiten geändert');
        flash('Perfetto! Die Website ist aktualisiert.');
        redirect('/admin/hours.php');
    }

    if ($action === 'add_special') {
        $from = post('date_from');
        $to = post('date_to') ?: $from;
        $title = mb_substr(post('title'), 0, 120);
        $closed = post('mode', 'closed') === 'closed';
        $ot = post('open_time');
        $ct = post('close_time');
        $err = null;
        if (!valid_date($from) || !valid_date($to) || $to < $from) {
            $err = 'Bitte ein gültiges Datum bzw. einen gültigen Zeitraum wählen.';
        } elseif ($title === '') {
            $err = 'Bitte einen Titel eingeben, z. B. „Betriebsurlaub“.';
        } elseif (!$closed && (!valid_time($ot) || !valid_time($ct))) {
            $err = 'Bitte Öffnungszeiten für den Sondertag eingeben.';
        }
        if ($err) {
            flash($err, 'error');
        } else {
            db_insert('special_days', [
                'date_from' => $from, 'date_to' => $to, 'title' => $title, 'is_closed' => $closed ? 1 : 0,
                'open_time' => $closed ? null : $ot, 'close_time' => $closed ? null : $ct,
            ]);
            log_activity('Sondertag „' . $title . '“ (' . german_date($from) . ') eingetragen');
            flash('Eingetragen: ' . $title . '.');
        }
        redirect('/admin/hours.php');
    }

    if ($action === 'delete_special') {
        $row = db_one('SELECT * FROM special_days WHERE id = ?', [(int) post('id')]);
        if ($row) {
            db_exec('DELETE FROM special_days WHERE id = ?', [(int) $row['id']]);
            log_activity('Sondertag „' . $row['title'] . '“ gelöscht');
            flash('Sondertag gelöscht.');
        }
        redirect('/admin/hours.php');
    }
    redirect('/admin/hours.php');
}

$week = weekly_hours();
$specials = upcoming_special_days();

$adminTitle = 'Öffnungszeiten';
$adminKicker = 'Orari';
$active = 'hours';
require APP_DIR . '/views/admin_header.php';
?>
<form class="panel" method="post" action="">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save_week">
  <h2>Wochenplan</h2>
  <div>
    <?php foreach (WEEKDAYS as $w => $name):
        $r = $week[$w];
        $open = (int) $r['is_open'] === 1; ?>
    <div class="day-row" data-day data-open="<?= $open ? '1' : '0' ?>">
      <span class="switch">
        <input type="checkbox" name="open[<?= $w ?>]" value="1" id="open-<?= $w ?>" role="switch" <?= $open ? 'checked' : '' ?>>
        <span aria-hidden="true"></span>
      </span>
      <label class="day-row__name" for="open-<?= $w ?>"><?= e($name) ?></label>
      <div class="day-row__times">
        <input class="input input--time" type="time" name="from[<?= $w ?>]" value="<?= e($r['open_time']) ?>" aria-label="<?= e($name) ?> öffnet um">
        <span aria-hidden="true">–</span>
        <input class="input input--time" type="time" name="to[<?= $w ?>]" value="<?= e($r['close_time']) ?>" aria-label="<?= e($name) ?> schließt um">
        <button class="shift2-add" type="button" data-shift2-add<?= $r['open_time2'] ? ' hidden' : '' ?>>+ 2. Schicht</button>
        <div class="shift2" data-shift2<?= $r['open_time2'] ? '' : ' hidden' ?>>
          <span>2. Schicht (optional):</span>
          <input class="input input--time" type="time" name="from2[<?= $w ?>]" value="<?= e((string) $r['open_time2']) ?>" aria-label="<?= e($name) ?> zweite Schicht ab">
          <span aria-hidden="true">–</span>
          <input class="input input--time" type="time" name="to2[<?= $w ?>]" value="<?= e((string) $r['close_time2']) ?>" aria-label="<?= e($name) ?> zweite Schicht bis">
        </div>
      </div>
      <span class="day-row__closed">Ruhetag</span>
    </div>
    <?php endforeach; ?>
  </div>
  <p class="hint">Mittagszeit? Tippe bei einem Tag auf „+ 2. Schicht“, z. B. 11:30 – 14:00. Zum Entfernen beide Felder leeren.</p>
  <button class="btn btn--green btn--block" type="submit">Wochenplan speichern</button>
</form>

<section style="display:flex;flex-direction:column;gap:10px">
  <div>
    <h2>Sondertage &amp; Urlaub</h2>
    <p class="hint">Feiertage, Betriebsferien, Events – haben Vorrang vor dem Wochenplan und erscheinen auf der Website.</p>
  </div>
  <?php foreach ($specials as $s):
      $d = new DateTimeImmutable($s['date_from']); ?>
  <div class="panel special" style="gap:12px;flex-direction:row">
    <div class="special__date<?= (int) $s['is_closed'] === 1 ? '' : ' special__date--open' ?>"><b><?= $d->format('d') ?></b><span><?= e(MONTHS_SHORT[(int) $d->format('n')]) ?></span></div>
    <div class="special__body">
      <strong><?= e($s['title']) ?></strong>
      <small><?= e(german_date($s['date_from'], true)) ?><?= $s['date_to'] !== $s['date_from'] ? ' – ' . e(german_date($s['date_to'], true)) : '' ?> · <?= (int) $s['is_closed'] === 1 ? 'geschlossen' : e($s['open_time'] . ' – ' . $s['close_time']) ?></small>
    </div>
    <form method="post" action="" data-confirm="„<?= e($s['title']) ?>“ löschen?">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete_special">
      <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
      <button class="round-btn" type="submit" aria-label="<?= e($s['title']) ?> löschen" style="border:0;background:#F1E6D2;color:var(--red-dark)"><?= icon('trash') ?></button>
    </form>
  </div>
  <?php endforeach; ?>

  <details class="panel" <?= $specials ? '' : 'open' ?>>
    <summary>+ Sondertag oder Urlaub eintragen</summary>
    <form method="post" action="" style="display:flex;flex-direction:column;gap:14px">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_special">
      <div class="field"><label for="title">Titel</label><input class="input" id="title" name="title" maxlength="120" required placeholder="z. B. Betriebsurlaub, Heiligabend"></div>
      <div class="grid-2">
        <div class="field"><label for="date_from">Von</label><input class="input" type="date" id="date_from" name="date_from" required min="<?= e(date('Y-m-d')) ?>"></div>
        <div class="field"><label for="date_to">Bis <small>(optional)</small></label><input class="input" type="date" id="date_to" name="date_to" min="<?= e(date('Y-m-d')) ?>"></div>
      </div>
      <fieldset class="field" style="border:0;margin:0;padding:0">
        <legend style="margin-bottom:8px">An diesen Tagen</legend>
        <div class="chips">
          <label class="chip"><input type="radio" name="mode" value="closed" checked data-mode><span>geschlossen</span></label>
          <label class="chip"><input type="radio" name="mode" value="open" data-mode><span>andere Zeiten</span></label>
        </div>
      </fieldset>
      <div class="grid-2" data-special-times hidden>
        <div class="field"><label for="open_time">Öffnet</label><input class="input" type="time" id="open_time" name="open_time" value="17:00"></div>
        <div class="field"><label for="close_time">Schließt</label><input class="input" type="time" id="close_time" name="close_time" value="23:00"></div>
      </div>
      <button class="btn btn--dashed btn--block" type="submit">Eintragen</button>
    </form>
  </details>
</section>
<?php require APP_DIR . '/views/admin_footer.php'; ?>
