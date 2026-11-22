/* Comportamiento comun: tema, menu lateral, pestanas, menus, dialogos, avisos, alertas en vivo. */
(function (w, d) {
  'use strict';
  var CFG = w.MK_CFG || { rutas: {}, prefs: {} };
  var $ = function (s, c) { return (c || d).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); };
  var reducir = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Preferencias (se guardan en el servidor y en localStorage) ---------- */
  var guardarTimer;
  function guardarPrefs(parcial) {
    if (!CFG.rutas.preferencias) return;
    clearTimeout(guardarTimer);
    guardarTimer = setTimeout(function () {
      fetch(CFG.rutas.preferencias, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CFG.csrf, 'Accept': 'application/json' },
        body: JSON.stringify(parcial)
      }).catch(function () {});
    }, 400);
  }

  /* ---------- Tema ---------- */
  function aplicarTema(t) {
    d.documentElement.setAttribute('data-theme', t);
    try { localStorage.setItem('mk-tema', t); } catch (e) {}
    $$('[data-tema-toggle]').forEach(function (b) {
      b.setAttribute('aria-pressed', t === 'dark' ? 'true' : 'false');
      b.setAttribute('aria-label', t === 'dark' ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro');
      var i = $('.i-luna', b), j = $('.i-sol', b);
      if (i && j) { i.classList.toggle('hide', t === 'dark'); j.classList.toggle('hide', t !== 'dark'); }
    });
  }
  d.addEventListener('click', function (e) {
    var b = e.target.closest('[data-tema-toggle]'); if (!b) return;
    var t = d.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    aplicarTema(t); guardarPrefs({ tema: t });
  });

  /* ---------- Sonido (boton de la barra superior) ---------- */
  function pintarSonido() {
    if (!w.MKSound) return;
    $$('[data-sonido-toggle]').forEach(function (b) {
      b.setAttribute('aria-pressed', MKSound.activo ? 'true' : 'false');
      b.setAttribute('aria-label', MKSound.activo ? 'Silenciar alertas' : 'Activar sonido de alertas');
      var on = $('.i-on', b), off = $('.i-off', b);
      if (on && off) { on.classList.toggle('hide', !MKSound.activo); off.classList.toggle('hide', MKSound.activo); }
    });
  }
  d.addEventListener('click', function (e) {
    var b = e.target.closest('[data-sonido-toggle]'); if (!b || !w.MKSound) return;
    MKSound.fijar(!MKSound.activo); MKSound.desbloquear();
    if (MKSound.activo) MKSound.tocar('ok');
    guardarPrefs({ sonido: MKSound.activo, volumen: MKSound.volumen });
  });
  d.addEventListener('mk:sonido', pintarSonido);

  /* ---------- Menu lateral (movil) ---------- */
  d.addEventListener('click', function (e) {
    if (e.target.closest('[data-nav-toggle]')) { d.body.classList.toggle('nav-open'); return; }
    if (e.target.closest('.scrim')) d.body.classList.remove('nav-open');
  });

  /* ---------- Menus desplegables ---------- */
  d.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-menu]');
    $$('.menu-pop').forEach(function (p) {
      var dueno = p.parentElement.querySelector('[data-menu]');
      if (btn && dueno === btn) return;
      p.hidden = true; if (dueno) dueno.setAttribute('aria-expanded', 'false');
    });
    if (btn) {
      var pop = btn.parentElement.querySelector('.menu-pop');
      pop.hidden = !pop.hidden; btn.setAttribute('aria-expanded', pop.hidden ? 'false' : 'true');
      if (!pop.hidden) { var f = $('a,button', pop); if (f) f.focus({ preventScroll: true }); }
    }
  });
  d.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    d.body.classList.remove('nav-open');
    $$('.menu-pop').forEach(function (p) { if (!p.hidden) { p.hidden = true; var b = p.parentElement.querySelector('[data-menu]'); if (b) { b.setAttribute('aria-expanded', 'false'); b.focus(); } } });
  });

  /* ---------- Pestanas (con #hash) ---------- */
  function iniciarTabs(cont) {
    var tabs = $$('[role="tab"]', cont), paneles = tabs.map(function (t) { return d.getElementById(t.getAttribute('aria-controls')); });
    function ir(i, foco) {
      tabs.forEach(function (t, j) { var on = i === j; t.setAttribute('aria-selected', on); t.tabIndex = on ? 0 : -1; if (paneles[j]) paneles[j].hidden = !on; });
      if (foco) tabs[i].focus();
      if (cont.hasAttribute('data-hash') && tabs[i].id) history.replaceState(null, '', '#' + tabs[i].id);
      d.dispatchEvent(new CustomEvent('mk:tab', { detail: { panel: paneles[i], tab: tabs[i] } }));
    }
    tabs.forEach(function (t, i) {
      t.addEventListener('click', function () { ir(i); });
      t.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') { e.preventDefault(); ir((i + 1) % tabs.length, true); }
        if (e.key === 'ArrowLeft') { e.preventDefault(); ir((i - 1 + tabs.length) % tabs.length, true); }
      });
    });
    var inicial = tabs.findIndex(function (t) { return '#' + t.id === location.hash; });
    ir(inicial >= 0 ? inicial : Math.max(0, tabs.findIndex(function (t) { return t.getAttribute('aria-selected') === 'true'; })));
  }
  $$('[data-tabs]').forEach(iniciarTabs);

  /* ---------- Dialogos ---------- */
  d.addEventListener('click', function (e) {
    var a = e.target.closest('[data-dlg-open]');
    if (a) { var dl = d.getElementById(a.getAttribute('data-dlg-open')); if (dl && dl.showModal) { dl.showModal(); var f = $('[autofocus],input,select,textarea', dl); if (f) f.focus(); } return; }
    var c = e.target.closest('[data-dlg-close]');
    if (c) { var p = c.closest('dialog'); if (p) p.close(); return; }
    if (e.target.tagName === 'DIALOG') e.target.close();
  });

  // Confirmacion con nombre de la acción (en lugar de "Aceptar")
  var dlgConfirm;
  function confirmar(titulo, texto, verbo) {
    return new Promise(function (ok) {
      if (!dlgConfirm) {
        dlgConfirm = d.createElement('dialog'); dlgConfirm.className = 'dlg';
        dlgConfirm.innerHTML = '<form method="dialog"><div class="dlg-head"><h3 data-t></h3></div><div class="dlg-body"><p class="muted" data-x></p></div><div class="dlg-foot"><button class="btn" value="no">Cancelar</button><button class="btn btn-danger" value="si" data-v></button></div></form>';
        d.body.appendChild(dlgConfirm);
      }
      $('[data-t]', dlgConfirm).textContent = titulo; $('[data-x]', dlgConfirm).textContent = texto; $('[data-v]', dlgConfirm).textContent = verbo || 'Confirmar';
      dlgConfirm.onclose = function () { ok(dlgConfirm.returnValue === 'si'); };
      dlgConfirm.showModal();
    });
  }
  d.addEventListener('submit', function (e) {
    var f = e.target; if (!f.hasAttribute || !f.hasAttribute('data-confirmar') || f.__ok) return;
    e.preventDefault();
    confirmar(f.getAttribute('data-confirmar'), f.getAttribute('data-confirmar-texto') || 'Esta acción no se puede deshacer.', f.getAttribute('data-confirmar-verbo'))
      .then(function (si) { if (si) { f.__ok = true; f.requestSubmit ? f.requestSubmit() : f.submit(); } });
  }, true);

  /* ---------- Avisos ---------- */
  var cont = $('.toasts');
  var ICON = { ok: 'ok', critico: 'aviso', alto: 'aviso', medio: 'info', info: 'info' };
  function toast(o) {
    if (!cont) return;
    var t = d.createElement('div'); t.className = 'toast lvl lvl-' + (o.tipo || 'info'); t.setAttribute('role', o.tipo === 'critico' ? 'alert' : 'status');
    t.style.setProperty('--c', 'var(--' + (o.tipo === 'ok' ? 'ok' : (o.tipo || 'info')) + ')');
    var h = '<div><strong></strong><p></p></div><button class="icon-btn x" aria-label="Cerrar aviso" style="width:32px;height:32px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>';
    t.innerHTML = h; $('strong', t).textContent = o.titulo || ''; $('p', t).textContent = o.mensaje || '';
    if (!o.mensaje) $('p', t).remove();
    if (o.href) { var a = d.createElement('a'); a.href = o.href; a.className = 't-sec'; a.textContent = o.enlace || 'Ver'; $('div', t).appendChild(a); }
    $('.x', t).addEventListener('click', function () { t.remove(); });
    cont.appendChild(t);
    setTimeout(function () { t.remove(); }, o.ms || (o.tipo === 'critico' ? 14000 : 7000));
    while (cont.children.length > 4) cont.firstChild.remove();
  }
  w.MK = { toast: toast, confirmar: confirmar, guardarPrefs: guardarPrefs, aplicarTema: aplicarTema };

  /* ---------- Alertas en vivo ---------- */
  var vistas = {};
  try { vistas = JSON.parse(sessionStorage.getItem('mk-vistas') || '{}'); } catch (e) {}
  function badges(n, crit) {
    $$('[data-alertas-count]').forEach(function (b) { b.textContent = n > 99 ? '99+' : n; b.hidden = !n; });
    $$('[data-alertas-critico]').forEach(function (b) { b.hidden = !crit; });
  }
  function revisar() {
    if (!CFG.rutas.recientes || d.hidden) return;
    fetch(CFG.rutas.recientes, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) {
        if (!j || !j.success) return;
        var ahora = Date.now(), mayor = null, nuevas = 0;
        j.alertas.forEach(function (a) {
          var v = vistas[a.id], reaviso = v && a.nivel === 'critico' && (ahora - v.t) > 10 * 60000 && v.n < 3;
          if (!v || reaviso) {
            vistas[a.id] = { t: ahora, n: v ? v.n + 1 : 1 };
            nuevas++;
            toast({ tipo: a.nivel, titulo: a.titulo, mensaje: a.parcela + ': ' + a.mensaje.slice(0, 150) + (a.mensaje.length > 150 ? '...' : ''), href: a.url, enlace: 'Abrir parcela' });
            var rango = { critico: 4, alto: 3, medio: 2, info: 1 };
            if (!mayor || rango[a.nivel] > rango[mayor]) mayor = a.nivel;
          }
        });
        if (mayor && w.MKSound) MKSound.tocar(mayor);
        try { sessionStorage.setItem('mk-vistas', JSON.stringify(vistas)); } catch (e) {}
        badges(j.total, j.criticas);
      }).catch(function () {});
  }
  if (CFG.rutas.recientes) { setTimeout(revisar, 1500); setInterval(revisar, 60000); }
  if (CFG.rutas.contador) {
    var refrescar = function () {
      fetch(CFG.rutas.contador, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.ok ? r.json() : null; }).then(function (j) { if (j && j.success) badges(j.total, j.criticas); }).catch(function () {});
    };
    setInterval(refrescar, 90000);
  }

  /* ---------- Sincroniza sonido/tema guardados del servidor ---------- */
  d.addEventListener('DOMContentLoaded', function () {
    if (w.MKSound && CFG.prefs && typeof CFG.prefs.sonido === 'boolean') {
      var guardado = null; try { guardado = localStorage.getItem('mk-sonido'); } catch (e) {}
      if (guardado === null) MKSound.fijar(CFG.prefs.sonido, typeof CFG.prefs.volumen === 'number' ? CFG.prefs.volumen : undefined);
    }
    pintarSonido();
    aplicarTema(d.documentElement.getAttribute('data-theme') || 'light');
  });

  /* ---------- Utilidades de formularios ---------- */
  d.addEventListener('click', function (e) {
    var b = e.target.closest('[data-pass-toggle]'); if (!b) return;
    var i = d.getElementById(b.getAttribute('data-pass-toggle')); if (!i) return;
    var v = i.type === 'password'; i.type = v ? 'text' : 'password';
    b.setAttribute('aria-label', v ? 'Ocultar contraseña' : 'Mostrar contraseña');
  });
  // Evita doble envio
  d.addEventListener('submit', function (e) {
    var f = e.target; if (e.defaultPrevented || !f.querySelector) return;
    var s = f.querySelector('button[type=submit][data-ocupado]');
    if (s) { s.disabled = true; s.setAttribute('aria-busy', 'true'); s.dataset.txt = s.textContent; s.textContent = s.getAttribute('data-ocupado'); }
  });
})(window, document);
