<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

if (!db_is_installed()) {
    redirect('/setup.php');
}
require_login();

$cats = categories();
$catSlug = query_param('cat', $cats[0]['slug'] ?? '');
$cat = category_by_slug($catSlug) ?? ($cats[0] ?? null);
if ($cat === null) {
    exit('Keine Kategorien vorhanden.');
}
$back = '/admin/menu.php?cat=' . rawurlencode($cat['slug']);

if (is_post()) {
    require_csrf();
    $action = post('action');
    $id = (int) post('id', '0');

    if ($action === 'toggle' && ($d = dish($id))) {
        $new = (int) $d['is_available'] === 1 ? 0 : 1;
        db_update('dishes', ['is_available' => $new, 'updated_at' => now()], 'id = ?', [$id]);
        log_activity('„' . $d['name'] . '“ ' . ($new ? 'wieder verfügbar' : 'als ausverkauft markiert'));
        flash($new ? $d['name'] . ' ist wieder verfügbar.' : $d['name'] . ' ist für heute ausverkauft.');
        redirect($back);
    }

    if ($action === 'delete' && ($d = dish($id))) {
        db_exec('DELETE FROM dishes WHERE id = ?', [$id]);
        log_activity('„' . $d['name'] . '“ gelöscht');
        flash($d['name'] . ' wurde gelöscht.');
        redirect($back);
    }

    if ($action === 'save_category') {
        db_update('categories', [
            'name' => mb_substr(post('cat_name') ?: (string) $cat['name'], 0, 60),
            'size_small' => mb_substr(post('size_small'), 0, 40),
            'size_large' => mb_substr(post('size_large'), 0, 40),
            'note' => mb_substr(post('note'), 0, 400),
        ], 'id = ?', [$cat['id']]);
        log_activity('Kategorie „' . (post('cat_name') ?: $cat['name']) . '“ angepasst');
        flash('Kategorie gespeichert.');
        redirect($back);
    }

    if ($action === 'save') {
        $name = mb_substr(post('name'), 0, 120);
        $priceIn = post('price');
        $priceLargeIn = post('price_large');
        $price = parse_price($priceIn);
        $priceLarge = parse_price($priceLargeIn);
        $targetCat = (int) post('category_id', (string) $cat['id']);
        $errors = [];
        if ($name === '') {
            $errors[] = 'Bitte einen Namen eingeben.';
        }
        if ($priceIn !== '' && $price === null) {
            $errors[] = 'Der Preis ist ungültig (Beispiel: 9,50).';
        }
        if ($priceLargeIn !== '' && $priceLarge === null) {
            $errors[] = 'Der Preis „groß“ ist ungültig (Beispiel: 12,50).';
        }
        if (!db_one('SELECT id FROM categories WHERE id = ?', [$targetCat])) {
            $errors[] = 'Unbekannte Kategorie.';
        }
        if ($errors) {
            flash(implode(' ', $errors), 'error');
            redirect($back . '&edit=' . ($id ?: 'new'));
        }
        $data = [
            'category_id' => $targetCat,
            'nr' => mb_substr(post('nr'), 0, 8),
            'name' => $name,
            'description' => mb_substr(post('description'), 0, 400),
            'additives' => implode(',', additive_numbers(post('additives'))),
            'price_cents' => $price,
            'price_large_cents' => $priceLarge,
            'is_vegetarian' => isset($_POST['is_vegetarian']) ? 1 : 0,
            'is_spicy' => isset($_POST['is_spicy']) ? 1 : 0,
            'is_new' => isset($_POST['is_new']) ? 1 : 0,
            'is_recommended' => isset($_POST['is_recommended']) ? 1 : 0,
            'updated_at' => now(),
        ];
        if ($id > 0 && dish($id)) {
            db_update('dishes', $data, 'id = ?', [$id]);
            log_activity('„' . $name . '“ bearbeitet');
        } else {
            $data['sort'] = (int) db_value('SELECT COALESCE(MAX(sort), 0) + 1 FROM dishes WHERE category_id = ?', [$targetCat]);
            $data['is_available'] = 1;
            db_insert('dishes', $data);
            log_activity('Neues Gericht „' . $name . '“');
        }
        flash('Perfetto! „' . $name . '“ ist gespeichert.');
        $slug = (string) db_value('SELECT slug FROM categories WHERE id = ?', [$targetCat]);
        redirect('/admin/menu.php?cat=' . rawurlencode($slug));
    }
    redirect($back);
}

$dishes = dishes_for_category((int) $cat['id']);
$editParam = query_param('edit');
$editing = null;
if ($editParam === 'new') {
    $nextNr = '';
    $last = end($dishes);
    if ($last && ctype_digit((string) $last['nr'])) {
        $nextNr = (string) ((int) $last['nr'] + 1);
    }
    $editing = ['id' => 0, 'category_id' => $cat['id'], 'nr' => $nextNr, 'name' => '', 'description' => '', 'price_cents' => null, 'price_large_cents' => null, 'additives' => '', 'is_vegetarian' => 0, 'is_spicy' => 0, 'is_new' => 0, 'is_recommended' => 0];
} elseif ($editParam !== '' && ctype_digit($editParam)) {
    $editing = dish((int) $editParam);
}
$priceField = fn (?int $c) => $c === null ? '' : number_format($c / 100, 2, ',', '');

$adminTitle = 'Speisekarte bearbeiten';
$adminKicker = 'Il menù';
$active = 'menu';
require APP_DIR . '/views/admin_header.php';
?>

<?php if ($editing): ?>
<form class="panel anim-in" method="post" action="<?= e(url('admin/menu.php?cat=' . rawurlencode($cat['slug']))) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
  <div class="row-between">
    <h1 style="font-size:24px"><?= $editing['id'] ? e($editing['name']) . ' bearbeiten' : 'Neues Gericht' ?></h1>
    <a class="round-btn" href="<?= e(url($back)) ?>" aria-label="Abbrechen"><?= icon('close') ?></a>
  </div>
  <div class="grid-nr">
    <div class="field"><label for="nr">Nr.</label><input class="input" id="nr" name="nr" inputmode="numeric" maxlength="8" value="<?= e($editing['nr']) ?>"></div>
    <div class="field"><label for="name">Name</label><input class="input" id="name" name="name" required maxlength="120" value="<?= e($editing['name']) ?>" placeholder="z. B. Pizza Calabrese"></div>
  </div>
  <div class="field">
    <label for="description">Beschreibung <small>(macht Appetit – erzähl, was drauf ist)</small></label>
    <textarea class="textarea" id="description" name="description" maxlength="400" rows="3"><?= e($editing['description']) ?></textarea>
  </div>
  <div class="grid-2">
    <?php $editCat = db_one('SELECT * FROM categories WHERE id = ?', [(int) $editing['category_id']]); [$ls, $ll] = size_labels($editCat); ?>
    <div class="field"><label for="price">Preis <small>(bzw. <?= e($ls) ?>)</small></label><input class="input" id="price" name="price" inputmode="decimal" placeholder="z. B. 8,50" value="<?= e($priceField($editing['price_cents'] !== null ? (int) $editing['price_cents'] : null)) ?>"></div>
    <div class="field"><label for="price_large">Preis <?= e($ll) ?> <small>(optional)</small></label><input class="input" id="price_large" name="price_large" inputmode="decimal" placeholder="z. B. 11,50" value="<?= e($priceField($editing['price_large_cents'] !== null ? (int) $editing['price_large_cents'] : null)) ?>"></div>
  </div>
  <div class="field">
    <label for="category_id">Kategorie</label>
    <select class="input" id="category_id" name="category_id">
      <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $editing['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <fieldset class="field" style="border:0;margin:0;padding:0">
    <legend style="margin-bottom:8px">Zusatzstoffe</legend>
    <?php $sel = additive_numbers((string) ($editing['additives'] ?? '')); ?>
    <input type="hidden" name="additives" value="<?= e(implode(',', $sel)) ?>" data-additives>
    <div class="chips">
      <?php foreach (ADDITIVES_LEGEND as $n => $label): ?>
      <label class="chip"><input type="checkbox" value="<?= $n ?>" data-additive <?= in_array($n, $sel, true) ? 'checked' : '' ?>><span><?= $n ?>) <?= e(str_replace('mit ', '', $label)) ?></span></label>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <fieldset class="field" style="border:0;margin:0;padding:0">
    <legend style="margin-bottom:8px">Kennzeichnung</legend>
    <div class="chips">
      <?php foreach (['is_vegetarian' => 'Vegetarisch', 'is_spicy' => 'Scharf', 'is_new' => 'Neu', 'is_recommended' => 'Empfehlung des Hauses'] as $k => $label): ?>
      <label class="chip"><input type="checkbox" name="<?= $k ?>" value="1" <?= (int) $editing[$k] ? 'checked' : '' ?>><span><?= e($label) ?></span></label>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <div class="btn-row">
    <button class="btn btn--green" type="submit">Speichern – perfetto!</button>
  </div>
</form>
<?php if ($editing['id']): ?>
<form method="post" action="<?= e(url('admin/menu.php?cat=' . rawurlencode($cat['slug']))) ?>" data-confirm="„<?= e($editing['name']) ?>“ wirklich löschen?">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
  <button class="btn btn--danger btn--block" type="submit"><?= icon('trash') ?>Gericht löschen</button>
</form>
<?php endif; ?>
<?php else: ?>

<div class="search">
  <?= icon('search') ?>
  <label class="sr-only" for="dish-search">Gericht oder Nummer suchen</label>
  <input class="input" id="dish-search" type="search" placeholder="Gericht oder Nummer suchen" data-search>
</div>

<nav class="admin-tabs-cats" aria-label="Kategorien">
  <?php foreach ($cats as $c):
      $n = (int) db_value('SELECT COUNT(*) FROM dishes WHERE category_id = ?', [$c['id']]); ?>
  <a href="<?= e(url('admin/menu.php?cat=' . rawurlencode($c['slug']))) ?>"<?= (int) $c['id'] === (int) $cat['id'] ? ' aria-current="page"' : '' ?>><?= e($c['name']) ?> (<?= $n ?>)</a>
  <?php endforeach; ?>
</nav>

<div class="panel panel--flush">
  <?php foreach ($dishes as $d):
      $avail = (int) $d['is_available'] === 1;
      $price = dish_price_label($d, $cat); ?>
  <div class="dish-row<?= $avail ? '' : ' dish-row--off' ?>" data-dish="<?= e(mb_strtolower($d['nr'] . ' ' . $d['name'])) ?>">
    <span class="dish__nr<?= $d['nr'] === '' ? ' dish__nr--empty' : '' ?>"><?= e($d['nr']) ?></span>
    <div class="dish-row__body">
      <span class="dish-row__name"><?= e($d['name']) ?></span>
      <span class="dish-row__sub"><?= $avail ? e(($price ?: 'Preis fehlt') . ' · verfügbar') : 'Heute ausverkauft' ?></span>
    </div>
    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="toggle">
      <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
      <button class="switch-btn" type="submit" role="switch" aria-checked="<?= $avail ? 'true' : 'false' ?>" aria-label="<?= e($d['name']) ?> verfügbar">
        <span class="switch-ui<?= $avail ? ' is-on' : '' ?>" aria-hidden="true"></span>
      </button>
    </form>
    <a class="round-btn" href="<?= e(url('admin/menu.php?cat=' . rawurlencode($cat['slug']) . '&edit=' . (int) $d['id'])) ?>" aria-label="<?= e($d['name']) ?> bearbeiten"><?= icon('edit') ?></a>
  </div>
  <?php endforeach; ?>
  <p class="search-empty" data-search-empty hidden>Nichts gefunden.</p>
  <?php if (!$dishes): ?><p class="search-empty">Noch keine Gerichte in dieser Kategorie.</p><?php endif; ?>
</div>
<p class="hint">Schalter aus = „heute ausverkauft“. Das Gericht bleibt auf der Karte, wird aber durchgestrichen angezeigt. Ohne Preis zeigt die Website einfach keinen Preis an.</p>

<details class="panel">
  <summary>Kategorie „<?= e($cat['name']) ?>“: Größen &amp; Hinweis</summary>
  <form method="post" action="" style="display:flex;flex-direction:column;gap:12px">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_category">
    <div class="field"><label for="cat_name">Name der Kategorie</label><input class="input" id="cat_name" name="cat_name" maxlength="60" value="<?= e($cat['name']) ?>"></div>
    <div class="grid-2">
      <div class="field"><label for="size_small">Größe 1</label><input class="input" id="size_small" name="size_small" maxlength="40" value="<?= e($cat['size_small'] ?? '') ?>" placeholder="z. B. Ø 26 cm"></div>
      <div class="field"><label for="size_large">Größe 2</label><input class="input" id="size_large" name="size_large" maxlength="40" value="<?= e($cat['size_large'] ?? '') ?>" placeholder="z. B. Ø 30 cm"></div>
    </div>
    <div class="field"><label for="note">Hinweis unter der Kategorie <small>(z. B. Extra-Zutaten)</small></label><textarea class="textarea" id="note" name="note" maxlength="400" rows="3"><?= e($cat['note'] ?? '') ?></textarea></div>
    <button class="btn btn--green btn--block" type="submit">Kategorie speichern</button>
  </form>
</details>

<a class="fab" href="<?= e(url('admin/menu.php?cat=' . rawurlencode($cat['slug']) . '&edit=new')) ?>"><?= icon('plus') ?>Neues Gericht</a>
<?php endif; ?>

<?php require APP_DIR . '/views/admin_footer.php'; ?>
