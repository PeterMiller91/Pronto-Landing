/* Pizzeria Pronto Pronto – Website-Interaktionen (ohne Abhängigkeiten) */
(function () {
  'use strict';

  var store = {
    get: function (k, d) { try { var v = localStorage.getItem(k); return v === null ? d : JSON.parse(v); } catch (e) { return d; } },
    set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* privater Modus */ } }
  };

  /* ---------- Mobiles Menü ---------- */
  var menuBtn = document.querySelector('.menu-btn');
  var mobileNav = document.getElementById('mobile-nav');
  if (menuBtn && mobileNav) {
    var setMenu = function (open) {
      menuBtn.setAttribute('aria-expanded', String(open));
      menuBtn.setAttribute('aria-label', open ? 'Menü schließen' : 'Menü öffnen');
      mobileNav.hidden = !open;
    };
    menuBtn.addEventListener('click', function () { setMenu(menuBtn.getAttribute('aria-expanded') !== 'true'); });
    mobileNav.addEventListener('click', function (e) { if (e.target.closest('a')) setMenu(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setMenu(false); });
  }

  /* ---------- Speisekarte: Reiter ---------- */
  var tabs = Array.prototype.slice.call(document.querySelectorAll('[role="tab"]'));
  function selectTab(tab) {
    tabs.forEach(function (t) {
      var on = t === tab;
      t.setAttribute('aria-selected', String(on));
      t.tabIndex = on ? 0 : -1;
      var panel = document.getElementById(t.getAttribute('aria-controls'));
      if (panel) panel.hidden = !on;
    });
  }
  tabs.forEach(function (tab, i) {
    tab.addEventListener('click', function () { selectTab(tab); });
    tab.addEventListener('keydown', function (e) {
      var next = null;
      if (e.key === 'ArrowRight') next = tabs[(i + 1) % tabs.length];
      if (e.key === 'ArrowLeft') next = tabs[(i - 1 + tabs.length) % tabs.length];
      if (next) { e.preventDefault(); selectTab(next); next.focus(); }
    });
  });

  /* ---------- Bestellzettel mit Preisen ---------- */
  var bar = document.querySelector('[data-order-bar]');
  if (bar) {
    var euro = function (c) { return (c / 100).toFixed(2).replace('.', ',') + ' €'; };
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };

    // Katalog aus der Seite: jede Bestell-Variante (Gericht + Größe) hat einen Schlüssel
    var sources = Array.prototype.slice.call(document.querySelectorAll('[data-add]'));
    var catalog = {};
    var keyOf = function (el) { return el.dataset.id + '|' + (el.dataset.variant || ''); };
    sources.forEach(function (el) {
      var p = el.dataset.price;
      catalog[keyOf(el)] = { id: el.dataset.id, nr: el.dataset.nr, name: el.dataset.name, label: el.dataset.label || '', price: p === '' || p === undefined ? null : parseInt(p, 10) };
    });

    // Gespeicherten Zettel laden, nur noch gültige Gerichte behalten (Preise immer aktuell aus der Seite)
    var saved = store.get('pp_order3', {}) || {};
    var order = {};
    Object.keys(saved).forEach(function (k) { if (catalog[k] && saved[k] > 0) order[k] = Math.min(99, saved[k] | 0); });

    var toggle = bar.querySelector('[data-order-toggle]');
    var sheet = bar.querySelector('#order-sheet');
    var lines = bar.querySelector('[data-order-lines]');
    var countEl = bar.querySelector('[data-order-count]');
    var sumEl = bar.querySelector('[data-order-sum]');
    var totalEl = bar.querySelector('[data-order-total]');
    var totalLabel = bar.querySelector('[data-order-total-label]');
    var noteEl = bar.querySelector('[data-order-note]');
    var hint = bar.querySelector('[data-order-hint]');
    var mainBtns = Array.prototype.slice.call(document.querySelectorAll('.add-btn'));

    var setSheet = function (open) {
      sheet.hidden = !open;
      toggle.setAttribute('aria-expanded', String(open));
      hint.textContent = open ? 'schließen' : 'ansehen ↑';
    };

    var render = function () {
      var keys = Object.keys(order).sort(function (a, b) {
        var ca = catalog[a], cb = catalog[b], rank = { '': 0, k: 1, g: 2, w: 3 };
        if (ca.label !== cb.label && (ca.label.indexOf('Familienpizza') === 0) !== (cb.label.indexOf('Familienpizza') === 0)) return ca.label.indexOf('Familienpizza') === 0 ? 1 : -1;
        var na = parseInt(ca.nr, 10) || 999, nb = parseInt(cb.nr, 10) || 999;
        return na !== nb ? na - nb : rank[a.split('|')[1]] - rank[b.split('|')[1]];
      });
      var count = 0, sum = 0, unknown = 0;
      keys.forEach(function (k) {
        var q = order[k], c = catalog[k];
        count += q;
        if (c.price === null) unknown += q; else sum += c.price * q;
      });

      // Zähler an den Plus-Knöpfen (alle Größen zusammen, ohne Aktions-Variante)
      mainBtns.forEach(function (b) {
        var n = 0;
        keys.forEach(function (k) { if (catalog[k].id === b.dataset.id && k.slice(-2) !== '|w') n += order[k]; });
        b.dataset.count = String(n);
        b.querySelector('.add-btn__count').textContent = n ? String(n) : '';
      });
      // Knopf "Pizza der Woche"
      document.querySelectorAll('[data-add][data-variant="w"]').forEach(function (b) {
        var n = order[keyOf(b)] || 0;
        var t = b.querySelector('[data-add-text]');
        if (t) t.textContent = n ? 'Auf dem Zettel (' + n + ') – noch eine?' : 'Auf den Bestellzettel';
        b.classList.toggle('is-added', n > 0);
      });

      toggle.hidden = count === 0;
      countEl.textContent = count === 1 ? '1 Gericht' : count + ' Gerichte';
      sumEl.textContent = sum > 0 ? ' · ' + euro(sum) + (unknown ? '*' : '') : '';
      totalEl.textContent = euro(sum);
      totalLabel.textContent = unknown ? 'Summe bisher*' : 'Summe';
      noteEl.textContent = (unknown ? '* Ohne ' + (unknown === 1 ? '1 Gericht' : unknown + ' Gerichte') + ' ohne Preis – den sagen wir dir am Telefon. ' : '') + 'Zzgl. eventueller Lieferkosten, Extras berechnen wir am Telefon.';
      if (count === 0) setSheet(false);

      lines.innerHTML = keys.map(function (k) {
        var c = catalog[k], q = order[k];
        var label = c.label ? ' <small>' + esc(c.label) + (c.price !== null ? ' · je ' + euro(c.price) : '') + '</small>' : (c.price !== null && q > 1 ? ' <small>je ' + euro(c.price) + '</small>' : '');
        var price = c.price === null ? 'Preis am Telefon' : euro(c.price * q);
        return '<li class="order-line"><span class="dish__nr' + (c.nr ? '' : ' dish__nr--empty') + '">' + esc(c.nr) + '</span>' +
          '<span class="order-line__name">' + q + '× ' + esc(c.name) + label + '</span>' +
          '<span class="order-line__price">' + price + '</span>' +
          '<button class="icon-btn" type="button" data-remove="' + esc(k) + '" aria-label="Eine ' + esc(c.name + (c.label ? ' (' + c.label + ')' : '')) + ' entfernen">' +
          '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14"/></svg></button></li>';
      }).join('');
      store.set('pp_order3', order);
    };

    var live = document.createElement('div');
    live.className = 'sr-only'; live.setAttribute('aria-live', 'polite');
    document.body.appendChild(live);

    sources.forEach(function (el) {
      el.addEventListener('click', function () {
        var k = keyOf(el);
        order[k] = Math.min(99, (order[k] || 0) + 1);
        render();
        var c = catalog[k];
        live.textContent = c.name + (c.label ? ' (' + c.label + ')' : '') + ' notiert.';
        el.classList.add('is-bump');
        setTimeout(function () { el.classList.remove('is-bump'); }, 250);
      });
    });

    // Größenwahl bei Gerichten mit "klein / groß"
    document.querySelectorAll('[data-pick]').forEach(function (btn) {
      var box = document.getElementById(btn.getAttribute('data-pick'));
      if (!box) return;
      btn.addEventListener('click', function () {
        var open = box.hidden;
        box.hidden = !open;
        btn.setAttribute('aria-expanded', String(open));
        if (open) box.querySelector('button').focus();
      });
    });

    toggle.addEventListener('click', function () { setSheet(sheet.hidden); });
    bar.querySelector('[data-order-close]').addEventListener('click', function () { setSheet(false); toggle.focus(); });
    bar.querySelector('[data-order-clear]').addEventListener('click', function () { order = {}; render(); });
    lines.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-remove]');
      if (!btn) return;
      var k = btn.getAttribute('data-remove');
      order[k] = (order[k] || 0) - 1;
      if (order[k] <= 0) delete order[k];
      render();
    });
    render();
  }

  /* ---------- Zusatzstoffe aufklappen ---------- */
  var adds = document.getElementById('zusatzstoffe');
  if (adds) {
    document.addEventListener('click', function (e) {
      if (e.target.closest('a[href="#zusatzstoffe"]')) adds.open = true;
    });
  }

  /* ---------- Gästestimmen ---------- */
  var reviewsEl = document.querySelector('[data-reviews]');
  if (reviewsEl) {
    var items = reviewsEl.querySelectorAll('[data-review]');
    var dots = reviewsEl.querySelectorAll('.dots span');
    var idx = 0;
    var show = function (n) {
      idx = (n + items.length) % items.length;
      Array.prototype.forEach.call(items, function (el, i) { el.hidden = i !== idx; });
      Array.prototype.forEach.call(dots, function (el, i) { el.classList.toggle('is-active', i === idx); });
    };
    var nextBtn = reviewsEl.querySelector('[data-review-next]');
    if (items.length < 2) { nextBtn.hidden = true; }
    nextBtn.addEventListener('click', function () { show(idx + 1); restart(); });
    var timer = null;
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var restart = function () {
      if (reduced || items.length < 2) return;
      clearInterval(timer);
      timer = setInterval(function () { show(idx + 1); }, 8000);
    };
    reviewsEl.addEventListener('mouseenter', function () { clearInterval(timer); });
    reviewsEl.addEventListener('mouseleave', restart);
    reviewsEl.addEventListener('focusin', function () { clearInterval(timer); });
    restart();
  }

  /* ---------- Google Maps (erst nach Zustimmung laden) ---------- */
  var map = document.querySelector('[data-map]');
  if (map) {
    var loadMap = function () {
      var iframe = document.createElement('iframe');
      iframe.src = map.dataset.src;
      iframe.title = 'Karte: Pizzeria Pronto Pronto, Marktplatz 6, Vohenstrauß';
      iframe.loading = 'lazy';
      iframe.referrerPolicy = 'no-referrer-when-downgrade';
      iframe.setAttribute('allowfullscreen', '');
      map.innerHTML = '';
      map.appendChild(iframe);
    };
    if (store.get('pp_maps_ok', false) === true) {
      loadMap();
    } else {
      map.querySelector('[data-map-load]').addEventListener('click', function () {
        if (map.querySelector('[data-map-remember]').checked) store.set('pp_maps_ok', true);
        loadMap();
      });
    }
  }
  /* ---------- Als App speichern (PWA) ---------- */
  var manifestLink = document.querySelector('link[rel="manifest"]');
  if ('serviceWorker' in navigator && manifestLink && window.isSecureContext) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(new URL('sw.js', manifestLink.href).href).catch(function () { /* egal */ });
    });
  }

  var promo = document.querySelector('[data-app-promo]');
  if (promo) {
    var standalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
    var ua = navigator.userAgent || '';
    var isIOS = /iPhone|iPad|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var isIOSSafari = isIOS && /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS/.test(ua);
    var dismissed = store.get('pp_app_dismissed', 0);
    var recentlyDismissed = dismissed && (Date.now() - dismissed) < 1000 * 60 * 60 * 24 * 30;
    var installBtn = promo.querySelector('[data-install]');
    var iosHint = promo.querySelector('[data-ios-hint]');
    var deferred = null;

    if (!standalone && !recentlyDismissed) {
      window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferred = e;
        promo.hidden = false;
      });
      if (isIOSSafari) {
        promo.hidden = false;
        installBtn.textContent = 'So geht’s';
      }
    }
    installBtn.addEventListener('click', function () {
      if (deferred) {
        deferred.prompt();
        deferred.userChoice.then(function (r) {
          if (r.outcome === 'accepted') promo.hidden = true;
          deferred = null;
        });
      } else {
        iosHint.hidden = false;
        installBtn.hidden = true;
      }
    });
    promo.querySelector('[data-install-dismiss]').addEventListener('click', function () {
      store.set('pp_app_dismissed', Date.now());
      promo.hidden = true;
    });
    window.addEventListener('appinstalled', function () { promo.hidden = true; });
  }
})();
