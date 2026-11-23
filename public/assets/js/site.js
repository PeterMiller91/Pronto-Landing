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

  /* ---------- Einwilligung (Cookie-Banner) ---------- */
  var CONSENT_VERSION = 1;
  var consent = store.get('pp_consent', null);
  if (!consent || consent.v !== CONSENT_VERSION) consent = null;
  // Übernahme der alten Einstellung "Karte immer anzeigen"
  if (!consent && store.get('pp_maps_ok', false) === true) {
    consent = { v: CONSENT_VERSION, maps: true, comfort: false, ts: Date.now() };
    store.set('pp_consent', consent);
  }
  var has = function (cat) { return !!(consent && consent[cat]); };
  var onConsentChange = [];

  var banner = document.querySelector('[data-consent]');
  var saveConsent = function (maps, comfort) {
    var before = consent;
    consent = { v: CONSENT_VERSION, maps: !!maps, comfort: !!comfort, ts: Date.now() };
    store.set('pp_consent', consent);
    try { localStorage.removeItem('pp_maps_ok'); } catch (e) { /* egal */ }
    if (banner) banner.hidden = true;
    onConsentChange.forEach(function (fn) { fn(consent, before); });
  };
  if (banner) {
    var details = banner.querySelector('[data-consent-details]');
    var toggleBtn = banner.querySelector('[data-consent-toggle]');
    var saveBtn = banner.querySelector('[data-consent-save]');
    var boxMaps = banner.querySelector('[data-consent-cat="maps"]');
    var boxComfort = banner.querySelector('[data-consent-cat="comfort"]');
    var showDetails = function (on) {
      details.hidden = !on;
      saveBtn.hidden = !on;
      toggleBtn.setAttribute('aria-expanded', String(on));
      toggleBtn.textContent = on ? 'Weniger anzeigen' : 'Einstellungen';
    };
    var openBanner = function (withDetails) {
      boxMaps.checked = has('maps');
      boxComfort.checked = has('comfort');
      showDetails(!!withDetails);
      banner.hidden = false;
      banner.querySelector('button').focus();
    };
    toggleBtn.addEventListener('click', function () { showDetails(details.hidden); });
    banner.querySelector('[data-consent-all]').addEventListener('click', function () { saveConsent(true, true); });
    banner.querySelector('[data-consent-necessary]').addEventListener('click', function () { saveConsent(false, false); });
    saveBtn.addEventListener('click', function () { saveConsent(boxMaps.checked, boxComfort.checked); });
    document.querySelectorAll('[data-consent-open]').forEach(function (b) {
      b.addEventListener('click', function () { openBanner(true); });
    });
    if (!consent) openBanner(false);
  }

  /* ---------- Google Maps (nur mit Einwilligung) ---------- */
  var map = document.querySelector('[data-map]');
  if (map) {
    var placeholder = map.innerHTML;
    var loaded = false;
    var loadMap = function () {
      if (loaded) return;
      loaded = true;
      var iframe = document.createElement('iframe');
      iframe.src = map.dataset.src;
      iframe.title = 'Karte: Pizzeria Pronto Pronto, Marktplatz 6, Vohenstrauß';
      iframe.loading = 'lazy';
      iframe.referrerPolicy = 'no-referrer-when-downgrade';
      iframe.setAttribute('allowfullscreen', '');
      map.innerHTML = '';
      map.appendChild(iframe);
    };
    var bindPlaceholder = function () {
      var btn = map.querySelector('[data-map-load]');
      if (!btn) return;
      btn.addEventListener('click', function () {
        if (map.querySelector('[data-map-remember]').checked) saveConsent(true, has('comfort'));
        loadMap();
      });
    };
    if (has('maps')) loadMap(); else bindPlaceholder();
    onConsentChange.push(function (c) {
      if (c.maps) { loadMap(); }
      else if (loaded) { loaded = false; map.innerHTML = placeholder; bindPlaceholder(); }
    });
  }

  /* ---------- Offline-Speicher / Service Worker (nur mit Einwilligung "Komfort") ---------- */
  var manifestLink = document.querySelector('link[rel="manifest"]');
  var swSupported = 'serviceWorker' in navigator && manifestLink && window.isSecureContext;
  var registerSW = function () {
    if (swSupported) navigator.serviceWorker.register(new URL('sw.js', manifestLink.href).href).catch(function () { /* egal */ });
  };
  var removeSW = function () {
    if (!swSupported) return;
    navigator.serviceWorker.getRegistrations().then(function (regs) { regs.forEach(function (r) { r.unregister(); }); });
    if (window.caches) caches.keys().then(function (keys) { keys.forEach(function (k) { if (k.indexOf('pp-') === 0) caches.delete(k); }); });
  };
  if (has('comfort')) registerSW(); else removeSW();
  onConsentChange.push(function (c) { if (c.comfort) registerSW(); else removeSW(); });

  /* ---------- Als App installieren ---------- */
  var promo = document.querySelector('[data-app-promo]');
  if (promo) {
    var standalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
    var ua = navigator.userAgent || '';
    var isIOS = /iPhone|iPad|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var isAndroid = /Android/.test(ua);
    var inApp = /FBAN|FBAV|Instagram|Line\/|WhatsApp|Snapchat|TikTok|; wv\)/.test(ua);
    var isSamsung = /SamsungBrowser/.test(ua);
    var isFirefox = /Firefox|FxiOS/.test(ua);
    var isMobile = isIOS || isAndroid || (window.matchMedia && window.matchMedia('(pointer: coarse)').matches);
    var dismissed = store.get('pp_app_dismissed', 0);
    var recentlyDismissed = dismissed && (Date.now() - dismissed) < 1000 * 60 * 60 * 24 * 30;
    var installBtn = promo.querySelector('[data-install]');
    var stepsEl = promo.querySelector('[data-install-steps]');
    var deferred = null;

    var steps = function () {
      if (inApp) return ['Diese Seite ist gerade in einer App (z. B. Instagram oder WhatsApp) geöffnet.', 'Tippe auf ⋮ bzw. ··· und wähle „Im Browser öffnen“.', 'Dann dort erneut auf „Zum Startbildschirm“ tippen.'];
      if (isIOS) return ['Tippe unten in der Leiste auf <strong>Teilen</strong> (Quadrat mit Pfeil nach oben).', 'Scrolle nach unten und wähle <strong>„Zum Home-Bildschirm“</strong>.', 'Oben rechts auf <strong>„Hinzufügen“</strong> tippen – fertig!'];
      if (isSamsung) return ['Tippe unten auf das Menü <strong>≡</strong>.', 'Wähle <strong>„Seite hinzufügen zu“</strong> → <strong>„Startbildschirm“</strong>.'];
      if (isFirefox) return ['Tippe auf das Menü <strong>⋮</strong>.', 'Wähle <strong>„Installieren“</strong> bzw. <strong>„Zum Startbildschirm hinzufügen“</strong>.'];
      if (isAndroid) return ['Tippe oben rechts auf das Menü <strong>⋮</strong>.', 'Wähle <strong>„App installieren“</strong> oder <strong>„Zum Startbildschirm hinzufügen“</strong>.', 'Mit <strong>„Installieren“</strong> bestätigen – fertig!'];
      return ['Klicke in der Adressleiste auf das Symbol <strong>„Installieren“</strong> (Bildschirm mit Pfeil).', 'Oder im Browser-Menü: <strong>„App installieren“</strong>.'];
    };
    var showSteps = function () {
      stepsEl.innerHTML = steps().map(function (t) { return '<li>' + t + '</li>'; }).join('');
      stepsEl.hidden = false;
    };
    var openPromo = function () {
      promo.hidden = false;
      if (!deferred) { showSteps(); installBtn.hidden = true; }
      promo.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    window.addEventListener('beforeinstallprompt', function (e) {
      e.preventDefault();
      deferred = e;
      installBtn.hidden = false;
      installBtn.textContent = 'Jetzt installieren';
      stepsEl.hidden = true;
      if (!standalone && !recentlyDismissed) promo.hidden = false;
    });
    // Auf Handys immer anbieten – mit Anleitung, falls der Browser keinen eigenen Dialog hat
    if (!standalone && !recentlyDismissed && isMobile) {
      promo.hidden = false;
      installBtn.textContent = 'So geht’s';
    }
    installBtn.addEventListener('click', function () {
      if (deferred) {
        deferred.prompt();
        deferred.userChoice.then(function (r) {
          if (r.outcome === 'accepted') promo.hidden = true;
          deferred = null;
        });
      } else {
        showSteps();
        installBtn.hidden = true;
      }
    });
    promo.querySelector('[data-install-dismiss]').addEventListener('click', function () {
      store.set('pp_app_dismissed', Date.now());
      promo.hidden = true;
    });
    document.querySelectorAll('[data-install-open]').forEach(function (a) {
      if (standalone) { a.hidden = true; return; }
      a.addEventListener('click', function (e) { e.preventDefault(); openPromo(); });
    });
    window.addEventListener('appinstalled', function () { promo.hidden = true; });
  }
})();
