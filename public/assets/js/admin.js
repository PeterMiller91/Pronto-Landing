/* Pronto Pronto – Backend-Helfer */
(function () {
  'use strict';

  // Schalter, die sofort speichern (z. B. "Heute spontan geschlossen")
  document.querySelectorAll('form[data-autosubmit] input[type="checkbox"]').forEach(function (cb) {
    cb.addEventListener('change', function () { cb.form.submit(); });
  });

  // Sicherheitsabfrage vor dem Löschen
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // Passwort anzeigen
  document.querySelectorAll('[data-pw-toggle]').forEach(function (btn) {
    var input = document.getElementById(btn.getAttribute('data-pw-toggle'));
    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-label', show ? 'Passwort verbergen' : 'Passwort anzeigen');
    });
  });

  // Speisekarte: Suche
  var search = document.querySelector('[data-search]');
  if (search) {
    var rows = document.querySelectorAll('[data-dish]');
    var empty = document.querySelector('[data-search-empty]');
    search.addEventListener('input', function () {
      var q = search.value.trim().toLowerCase();
      var shown = 0;
      rows.forEach(function (r) {
        var hit = !q || r.getAttribute('data-dish').indexOf(q) !== -1;
        r.hidden = !hit;
        if (hit) shown++;
      });
      if (empty) empty.hidden = shown !== 0;
    });
  }

  // Öffnungszeiten: Ruhetag ein/aus
  document.querySelectorAll('[data-day]').forEach(function (row) {
    var cb = row.querySelector('input[type="checkbox"]');
    cb.addEventListener('change', function () { row.setAttribute('data-open', cb.checked ? '1' : '0'); });
  });
  document.querySelectorAll('[data-shift2-add]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var box = btn.parentNode.querySelector('[data-shift2]');
      box.hidden = false; btn.hidden = true;
      box.querySelector('input').focus();
    });
  });
  var times = document.querySelector('[data-special-times]');
  document.querySelectorAll('[data-mode]').forEach(function (r) {
    r.addEventListener('change', function () { if (times) times.hidden = r.value !== 'open' || !r.checked; });
  });

  // Hinweis-Leiste: Live-Vorschau
  var form = document.querySelector('[data-notice-form]');
  if (form) {
    var text = form.querySelector('[data-notice-text]');
    var count = form.querySelector('[data-notice-count]');
    var prev = form.querySelector('[data-notice-preview]');
    var prevText = form.querySelector('[data-notice-preview-text]');
    var activeCb = form.querySelector('[data-notice-active]');
    var update = function () {
      prevText.textContent = text.value.trim() || 'Dein Text erscheint hier';
      count.textContent = String(120 - text.value.length);
      prev.hidden = !activeCb.checked;
      var c = form.querySelector('[data-notice-color]:checked');
      prev.className = 'notice notice--' + (c ? c.value : 'red');
    };
    text.addEventListener('input', update);
    activeCb.addEventListener('change', update);
    form.querySelectorAll('[data-notice-color]').forEach(function (r) { r.addEventListener('change', update); });
    form.querySelectorAll('[data-preset]').forEach(function (b) {
      b.addEventListener('click', function () { text.value = b.getAttribute('data-preset'); activeCb.checked = true; update(); text.focus(); });
    });
  }

  // Foto-Upload: nach Auswahl sofort hochladen (mit optionalem Titel)
  var up = document.querySelector('[data-upload]');
  if (up) {
    var input = up.querySelector('[data-upload-input]');
    // Große Handy-Fotos vor dem Hochladen im Browser verkleinern (schneller, umgeht Server-Limits)
    var shrink = function (file, done) {
      if (!window.createImageBitmap || !window.DataTransfer || file.size < 1.5 * 1024 * 1024) { done(); return; }
      createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (bmp) {
        var max = 2000, scale = Math.min(1, max / Math.max(bmp.width, bmp.height));
        var c = document.createElement('canvas');
        c.width = Math.round(bmp.width * scale); c.height = Math.round(bmp.height * scale);
        c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
        c.toBlob(function (blob) {
          if (blob) {
            var dt = new DataTransfer();
            dt.items.add(new File([blob], 'foto.jpg', { type: 'image/jpeg' }));
            input.files = dt.files;
          }
          done();
        }, 'image/jpeg', 0.88);
      }).catch(function () { done(); });
    };
    input.addEventListener('change', function () {
      if (!input.files || !input.files.length) return;
      var label = window.prompt('Wie heißt das Gericht auf dem Foto? (optional)', '');
      up.querySelector('[data-upload-label]').value = label || '';
      up.querySelector('span').textContent = 'Lädt hoch …';
      shrink(input.files[0], function () { up.submit(); });
    });
  }
  // Zeichenzähler (z. B. Pizza der Woche)
  document.querySelectorAll('[data-count-source]').forEach(function (ta) {
    var out = document.querySelector('[data-count-for="' + ta.id + '"]');
    if (!out) return;
    ta.addEventListener('input', function () { out.textContent = String((parseInt(ta.getAttribute('maxlength'), 10) || 0) - ta.value.length); });
  });
})();
