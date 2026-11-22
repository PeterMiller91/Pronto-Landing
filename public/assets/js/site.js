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

  /* ---------- Bestellzettel ---------- */
  var bar = document.querySelector('[data-order-bar]');
  if (bar) {
    var order = store.get('pp_order', {}) || {};
    var names = {};
    var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-add]'));
    buttons.forEach(function (b) { names[b.dataset.nr] = b.dataset.name; });
    Object.keys(order).forEach(function (nr) { if (!names[nr]) delete order[nr]; });

    var toggle = bar.querySelector('[data-order-toggle]');
    var sheet = bar.querySelector('#order-sheet');
    var lines = bar.querySelector('[data-order-lines]');
    var countEl = bar.querySelector('[data-order-count]');
    var hint = bar.querySelector('[data-order-hint]');

    var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
    var total = function () { return Object.keys(order).reduce(function (a, k) { return a + order[k]; }, 0); };

    var render = function () {
      buttons.forEach(function (b) {
        var n = order[b.dataset.nr] || 0;
        b.dataset.count = String(n);
        b.querySelector('.add-btn__count').textContent = n ? String(n) : '';
        b.setAttribute('aria-label', b.dataset.name + ' auf den Bestellzettel' + (n ? ' (' + n + ' notiert)' : ''));
      });
      var t = total();
      toggle.hidden = t === 0;
      countEl.textContent = t === 1 ? '1 Gericht' : t + ' Gerichte';
      if (t === 0) setSheet(false);
      lines.innerHTML = Object.keys(order).sort(function (a, b) { return a - b; }).map(function (nr) {
        return '<li class="order-line"><span class="dish__nr">' + esc(nr) + '</span><span>' + order[nr] + '× ' + esc(names[nr]) +
          '</span><button class="icon-btn" type="button" data-remove="' + esc(nr) + '" aria-label="Eine ' + esc(names[nr]) + ' entfernen">' +
          '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14"/></svg></button></li>';
      }).join('');
      store.set('pp_order', order);
    };
    var setSheet = function (open) {
      sheet.hidden = !open;
      toggle.setAttribute('aria-expanded', String(open));
      hint.textContent = open ? 'schließen' : 'ansehen ↑';
    };

    buttons.forEach(function (b) {
      b.addEventListener('click', function () {
        order[b.dataset.nr] = (order[b.dataset.nr] || 0) + 1;
        render();
      });
    });
    toggle.addEventListener('click', function () { setSheet(sheet.hidden); });
    bar.querySelector('[data-order-close]').addEventListener('click', function () { setSheet(false); toggle.focus(); });
    bar.querySelector('[data-order-clear]').addEventListener('click', function () { order = {}; render(); });
    lines.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-remove]');
      if (!btn) return;
      var nr = btn.getAttribute('data-remove');
      order[nr] = (order[nr] || 0) - 1;
      if (order[nr] <= 0) delete order[nr];
      render();
    });
    render();
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
})();
