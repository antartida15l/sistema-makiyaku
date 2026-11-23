/* Graficos SVG propios (sin librerias): linea/area, barras + linea, dona, medidor, brujula, arco solar, perfil. */
(function (w, d) {
  'use strict';
  var NS = 'http://www.w3.org/2000/svg';
  var P = { l: 44, r: 16, t: 14, b: 28 };

  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function fnum(v, dec) { return (v === null || v === undefined || isNaN(v)) ? '-' : Number(v).toLocaleString('es-PE', { minimumFractionDigits: dec || 0, maximumFractionDigits: dec === undefined ? 1 : dec }); }
  function nice(min, max, n) {
    if (min === max) { max = min + 1; }
    var span = max - min, step = Math.pow(10, Math.floor(Math.log10(span / n))), err = span / n / step;
    step *= err >= 7.5 ? 10 : err >= 3.5 ? 5 : err >= 1.5 ? 2 : 1;
    var a = Math.floor(min / step) * step, b = Math.ceil(max / step) * step, t = [];
    for (var v = a; v <= b + step / 2; v += step) t.push(+v.toFixed(10));
    return { min: a, max: b, ticks: t };
  }
  // curva suave que no se pasa de los datos (Fritsch-Carlson)
  function suave(pts) {
    var n = pts.length; if (n < 3) return 'M' + pts.map(function (p) { return p[0] + ',' + p[1]; }).join('L');
    var dx = [], dy = [], m = [], t = [];
    for (var i = 0; i < n - 1; i++) { dx[i] = pts[i + 1][0] - pts[i][0]; dy[i] = pts[i + 1][1] - pts[i][1]; m[i] = dy[i] / dx[i]; }
    t[0] = m[0]; t[n - 1] = m[n - 2];
    for (i = 1; i < n - 1; i++) t[i] = m[i - 1] * m[i] <= 0 ? 0 : (m[i - 1] + m[i]) / 2;
    for (i = 0; i < n - 1; i++) { if (m[i] === 0) { t[i] = 0; t[i + 1] = 0; } else { var a = t[i] / m[i], b = t[i + 1] / m[i], s = a * a + b * b; if (s > 9) { var k = 3 / Math.sqrt(s); t[i] = k * a * m[i]; t[i + 1] = k * b * m[i]; } } }
    var p = 'M' + pts[0][0] + ',' + pts[0][1];
    for (i = 0; i < n - 1; i++) p += 'C' + (pts[i][0] + dx[i] / 3) + ',' + (pts[i][1] + t[i] * dx[i] / 3) + ' ' + (pts[i + 1][0] - dx[i] / 3) + ',' + (pts[i + 1][1] - t[i + 1] * dx[i] / 3) + ' ' + pts[i + 1][0] + ',' + pts[i + 1][1];
    return p;
  }
  function el(tag, attrs, cont) { var e = d.createElementNS(NS, tag); for (var k in attrs) e.setAttribute(k, attrs[k]); if (cont) e.textContent = cont; return e; }
  function color(c) { return /^#|^rgb/.test(c) ? c : 'var(--' + (c || 'chart-1') + ')'; }

  var REDUCIR = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Monta el grafico: leyenda que muestra u oculta series, selector de rango y redibujado al cambiar de tamano.
  function montar(host, dibujar, cfg) {
    cfg = cfg || {};
    host.classList.add('chart'); host.style.position = 'relative';
    host.querySelectorAll('.chart-tools').forEach(function (x) { x.remove(); });
    var est = { ocultas: {}, rango: cfg.rangos ? cfg.rangos[0] : null, animar: !REDUCIR };
    var tools = d.createElement('div'); tools.className = 'chart-tools';
    var items = (cfg.series || []).map(function (s, k) { return { nombre: s.nombre, color: s.color || ('chart-' + (k + 1)) }; });
    if (cfg.linea) items.push({ nombre: cfg.linea.nombre, color: cfg.linea.color || 'chart-2', linea: true });
    if (cfg.leyenda !== false && items.length > 1) {
      var lg = d.createElement('div'); lg.className = 'chart-legend'; lg.setAttribute('role', 'group'); lg.setAttribute('aria-label', 'Series del gráfico');
      items.forEach(function (it) {
        var b = d.createElement('button'); b.type = 'button'; b.setAttribute('aria-pressed', 'true');
        b.innerHTML = '<i style="background:' + color(it.color) + (it.linea ? ';height:3px;border-radius:2px' : '') + '"></i>' + esc(it.nombre);
        b.title = 'Mostrar u ocultar ' + it.nombre;
        b.addEventListener('click', function () {
          var on = b.getAttribute('aria-pressed') === 'true';
          if (on && Object.keys(est.ocultas).filter(function (k) { return est.ocultas[k]; }).length >= items.length - 1) return; // siempre queda una
          b.setAttribute('aria-pressed', on ? 'false' : 'true'); est.ocultas[it.nombre] = on; est.animar = false; pintar(true);
        });
        lg.appendChild(b);
      });
      tools.appendChild(lg);
    }
    if (cfg.rangos) {
      var sg = d.createElement('div'); sg.className = 'segmented chart-range'; sg.setAttribute('role', 'group'); sg.setAttribute('aria-label', 'Periodo');
      cfg.rangos.forEach(function (n, i) {
        var b = d.createElement('button'); b.type = 'button'; b.textContent = (cfg.rangoTxt ? cfg.rangoTxt(n) : n + ' h'); b.setAttribute('aria-pressed', i === 0 ? 'true' : 'false');
        b.addEventListener('click', function () { sg.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', 'false'); }); b.setAttribute('aria-pressed', 'true'); est.rango = n; est.animar = !REDUCIR; pintar(true); });
        sg.appendChild(b);
      });
      tools.appendChild(sg);
    }
    if (tools.children.length) host.insertBefore(tools, host.firstChild);
    var tip = host.querySelector('.chart-tip') || (function () { var t = d.createElement('div'); t.className = 'chart-tip'; host.appendChild(t); return t; })();
    var ultimo = 0, visto = false;
    function pintar(forzar) {
      var W = Math.round(host.clientWidth); if (!W || (W === ultimo && !forzar)) return; ultimo = W;
      var s = host.querySelector('svg'); if (s) s.remove();
      var svg = el('svg', { role: 'img', 'aria-label': host.getAttribute('aria-label') || 'Gráfico' });
      host.insertBefore(svg, tip); dibujar(svg, W, tip, est);
      if (est.animar && !visto) animar(svg); visto = true; est.animar = false;
    }
    pintar();
    if (w.ResizeObserver) new ResizeObserver(function () { pintar(); }).observe(host); else w.addEventListener('resize', function () { pintar(); });
    d.addEventListener('mk:tab', function () { ultimo = 0; pintar(); });
    d.addEventListener('mk:redibujar', function () { ultimo = 0; pintar(); });
  }

  // Entrada animada: lineas que se dibujan, barras que crecen, areas que aparecen
  function animar(svg) {
    svg.querySelectorAll('path[data-l]').forEach(function (p) {
      var len = p.getTotalLength ? p.getTotalLength() : 0; if (!len) return;
      p.style.strokeDasharray = len; p.style.strokeDashoffset = len;
      requestAnimationFrame(function () { p.style.transition = 'stroke-dashoffset .9s cubic-bezier(.2,.7,.2,1)'; p.style.strokeDashoffset = 0; });
    });
    svg.querySelectorAll('rect[data-b]').forEach(function (r, i) {
      r.style.transformBox = 'fill-box'; r.style.transformOrigin = '50% 100%'; r.style.transform = 'scaleY(0)';
      requestAnimationFrame(function () { r.style.transition = 'transform .6s cubic-bezier(.2,.7,.2,1) ' + Math.min(.4, i * .008) + 's'; r.style.transform = 'scaleY(1)'; });
    });
    svg.querySelectorAll('path[data-a]').forEach(function (p) { p.style.opacity = 0; requestAnimationFrame(function () { p.style.transition = 'opacity .9s ease .2s'; p.style.opacity = 1; }); });
  }

  function ejes(svg, W, H, x, y, yt, o) {
    var g = el('g', { 'class': 'ax' }); svg.appendChild(g);
    yt.forEach(function (v) {
      var yy = y(v); g.appendChild(el('line', { x1: P.l, x2: W - P.r, y1: yy, y2: yy }));
      var t = el('text', { x: P.l - 8, y: yy + 4, 'text-anchor': 'end' }, o.fmtY(v)); g.appendChild(t);
    });
    if (o.unidadY) g.appendChild(el('text', { x: P.l - 8, y: P.t - 9, 'text-anchor': 'end' }, o.unidadY));
  }
  function etiquetasX(svg, W, H, n, xAt, labels, max) {
    var g = el('g', { 'class': 'ax' }); svg.appendChild(g);
    var ancho = labels.reduce(function (m, l) { return Math.max(m, String(l).length); }, 4) * 6.4 + 18;   // ancho aproximado de una etiqueta
    var caben = Math.max(2, Math.floor((W - P.l - P.r) / ancho)), tope = max ? Math.min(max, caben) : caben;
    var cada = Math.max(1, Math.ceil(n / tope));
    for (var i = 0; i < n; i += cada) g.appendChild(el('text', { x: xAt(i), y: H - 8, 'text-anchor': 'middle' }, labels[i]));
  }
  function interaccion(svg, host, tip, W, H, n, xAt, contenido, dotsY, onIdx) {
    var guia = el('line', { y1: P.t, y2: H - P.b, style: 'stroke:var(--ink-3);stroke-dasharray:3 3;opacity:0' }); svg.appendChild(guia);
    var puntos = (dotsY || []).map(function (c) { var p = el('circle', { r: 4, style: 'fill:' + color(c) + ';stroke:var(--surface);stroke-width:2;opacity:0' }); svg.appendChild(p); return p; });
    var cap = el('rect', { x: P.l, y: P.t, width: W - P.l - P.r, height: H - P.t - P.b, fill: 'transparent' }); svg.appendChild(cap);
    function mover(ev) {
      var r = svg.getBoundingClientRect(), px = (ev.touches ? ev.touches[0].clientX : ev.clientX) - r.left;
      var best = 0, bd = 1e9; for (var i = 0; i < n; i++) { var dd = Math.abs(xAt(i) - px); if (dd < bd) { bd = dd; best = i; } }
      var x = xAt(best); guia.setAttribute('x1', x); guia.setAttribute('x2', x); guia.style.opacity = 1;
      var info = contenido(best); tip.innerHTML = info.html; tip.style.left = Math.max(60, Math.min(W - 60, x)) + 'px'; tip.style.top = (info.y !== undefined ? info.y : P.t + 20) + 'px'; tip.classList.add('on');
      puntos.forEach(function (p, k) { var yy = info.dots && info.dots[k]; if (yy === null || yy === undefined) { p.style.opacity = 0; return; } p.setAttribute('cx', x); p.setAttribute('cy', yy); p.style.opacity = 1; });
      if (onIdx) onIdx(best);
    }
    function salir() { guia.style.opacity = 0; tip.classList.remove('on'); puntos.forEach(function (p) { p.style.opacity = 0; }); if (onIdx) onIdx(-1); }
    cap.addEventListener('mousemove', mover); cap.addEventListener('mouseleave', salir);
    cap.addEventListener('touchstart', mover, { passive: true }); cap.addEventListener('touchmove', mover, { passive: true }); cap.addEventListener('touchend', salir);
  }

  /* ---------- Linea / area ---------- */
  function recortar(c, est) {
    var n = est && est.rango ? Math.min(est.rango, c.labels.length) : c.labels.length;
    var cut = c.desdeFin ? function (a) { return a.slice(a.length - n); } : function (a) { return a.slice(0, n); };   // historial: los ultimos n
    return { labels: cut(c.labels), titulos: c.titulos ? cut(c.titulos) : null, n: n, cut: cut };
  }
  function linea(host, c) {
    montar(host, function (svg, W, tip, est) {
      var rc = recortar(c, est), H = c.alto || 240, labels = rc.labels, n = rc.n;
      var S = c.series.map(function (s, k) { return Object.assign({}, s, { datos: rc.cut(s.datos), color: s.color || ('chart-' + (k + 1)) }); }).filter(function (s) { return !(est && est.ocultas[s.nombre]); });
      var titulos = rc.titulos, Pr = Object.assign({}, P);
      if (c.mini) { P.l = 4; P.r = 4; P.t = 6; P.b = 6; }
      svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H); svg.setAttribute('height', H);
      var vals = []; S.forEach(function (s) { s.datos.forEach(function (v) { if (v !== null && v !== undefined) vals.push(v); }); });
      (c.lineas || []).forEach(function (l) { vals.push(l.valor); });
      var mn = c.yMin !== undefined ? c.yMin : Math.min.apply(null, vals), mx = c.yMax !== undefined ? c.yMax : Math.max.apply(null, vals);
      var sc = nice(mn, mx, 4), x = function (i) { return n === 1 ? (P.l + W - P.r) / 2 : P.l + (W - P.l - P.r) * i / (n - 1); }, y = function (v) { return P.t + (H - P.t - P.b) * (1 - (v - sc.min) / (sc.max - sc.min)); };
      var fmtY = c.fmtY || function (v) { return fnum(v); };
      (c.bandas || []).forEach(function (b) { svg.appendChild(el('rect', { x: x(b.desde), y: P.t, width: Math.max(0, x(b.hasta) - x(b.desde)), height: H - P.t - P.b, style: 'fill:' + (b.color || 'var(--surface-3)') + ';opacity:' + (b.opacidad || .5) })); });
      if (!c.mini) { ejes(svg, W, H, x, y, sc.ticks, { fmtY: fmtY, unidadY: c.unidadY }); etiquetasX(svg, W, H, n, x, labels, c.maxX); }
      (c.lineas || []).forEach(function (l) {
        svg.appendChild(el('line', { x1: P.l, x2: W - P.r, y1: y(l.valor), y2: y(l.valor), style: 'stroke:' + color(l.color || 'critico') + ';stroke-dasharray:5 4;stroke-width:1.4' }));
        svg.appendChild(el('text', { x: W - P.r - 4, y: y(l.valor) - 5, 'text-anchor': 'end', 'class': 'lbl', style: 'fill:' + color(l.color || 'critico') }, l.texto));
      });
      var defs = el('defs', {}); svg.appendChild(defs);
      S.forEach(function (s, k) {
        var pts = [], seg = []; s.datos.forEach(function (v, i) { if (v === null || v === undefined) { if (seg.length) { pts.push(seg); seg = []; } } else seg.push([x(i), y(v)]); }); if (seg.length) pts.push(seg);
        var col = color(s.color);
        pts.forEach(function (sg) {
          var dpath = s.escalon ? sg.map(function (p, i) { return (i ? 'L' + p[0] + ',' + sg[i - 1][1] + 'L' : 'M') + p[0] + ',' + p[1]; }).join('') : suave(sg);
          if (s.area) {
            var gid = 'ga' + Math.random().toString(36).slice(2, 8);
            var lg = el('linearGradient', { id: gid, x1: 0, y1: 0, x2: 0, y2: 1 }); lg.appendChild(el('stop', { offset: 0, style: 'stop-color:' + col + ';stop-opacity:.32' })); lg.appendChild(el('stop', { offset: 1, style: 'stop-color:' + col + ';stop-opacity:0' })); defs.appendChild(lg);
            svg.appendChild(el('path', { d: dpath + 'L' + sg[sg.length - 1][0] + ',' + y(sc.min) + 'L' + sg[0][0] + ',' + y(sc.min) + 'Z', fill: 'url(#' + gid + ')', 'data-a': 1 }));
          }
          var at = { d: dpath, fill: 'none', style: 'stroke:' + col + ';stroke-width:' + (s.ancho || 2.4) + (s.dash ? ';stroke-dasharray:' + s.dash : ''), 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }; if (!s.dash) at['data-l'] = 1;
          svg.appendChild(el('path', at));
        });
      });
      if (c.ahora !== undefined && c.ahora >= 0) { svg.appendChild(el('line', { x1: x(c.ahora), x2: x(c.ahora), y1: P.t, y2: H - P.b, style: 'stroke:var(--accent);stroke-width:1.5;opacity:.7' })); svg.appendChild(el('text', { x: x(c.ahora) + 5, y: P.t + 10, 'class': 'lbl', style: 'fill:var(--accent-ink)' }, 'Ahora')); }
      interaccion(svg, host, tip, W, H, n, x, function (i) {
        var h = '<strong>' + esc(titulos ? titulos[i] : labels[i]) + '</strong>';
        S.forEach(function (s) { var v = s.datos[i]; h += '<br><i style="display:inline-block;width:8px;height:8px;border-radius:2px;background:' + color(s.color) + ';margin-right:6px"></i>' + esc(s.nombre) + ': ' + (c.fmtTip ? c.fmtTip(v, s) : fmtY(v)); });
        return { html: h, dots: S.map(function (s) { return s.datos[i] === null || s.datos[i] === undefined ? null : y(s.datos[i]); }) };
      }, S.map(function (s) { return s.color; }));
      Object.assign(P, Pr);
    }, c);
  }

  /* ---------- Barras (+ linea en eje derecho) ---------- */
  function barras(host, c) {
    montar(host, function (svg, W, tip, est) {
      var rc = recortar(c, est), H = c.alto || 240, labels = rc.labels, n = rc.n, titulos = rc.titulos;
      var S = c.series.map(function (s, k) { return Object.assign({}, s, { datos: rc.cut(s.datos), colores: s.colores ? rc.cut(s.colores) : null, color: s.color || ('chart-' + (k + 1)) }); }).filter(function (s) { return !(est && est.ocultas[s.nombre]); });
      var L = c.linea && !(est && est.ocultas[c.linea.nombre]) ? Object.assign({}, c.linea, { datos: rc.cut(c.linea.datos) }) : null;
      var grupos = [];
      var Pr = Object.assign({}, P); if (L) P.r = 44;
      svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H); svg.setAttribute('height', H);
      var vals = [0]; S.forEach(function (s) { s.datos.forEach(function (v) { if (v !== null) vals.push(c.apilado ? 0 : v); }); });
      if (c.apilado) { for (var i = 0; i < n; i++) { var t = 0; S.forEach(function (s) { t += s.datos[i] || 0; }); vals.push(t); } }
      var sc = nice(0, Math.max.apply(null, vals) * 1.05 || 1, 4), bw = (W - P.l - P.r) / n;
      var x = function (i) { return P.l + bw * (i + .5); }, y = function (v) { return P.t + (H - P.t - P.b) * (1 - (v - sc.min) / (sc.max - sc.min)); };
      var fmtY = c.fmtY || function (v) { return fnum(v); };
      ejes(svg, W, H, x, y, sc.ticks, { fmtY: fmtY, unidadY: c.unidadY }); etiquetasX(svg, W, H, n, x, labels, c.maxX);
      var ancho = Math.max(2, Math.min(36, bw * (c.apilado ? .7 : .7 / S.length) - 1));
      for (var i2 = 0; i2 < n; i2++) {
        var acc = 0, g = el('g', { 'class': 'bar-g' }); svg.appendChild(g); grupos.push(g);
        S.forEach(function (s, k) {
          var v = s.datos[i2]; if (!v) return; var col = color((s.colores && s.colores[i2]) || s.color);
          var bx = c.apilado ? x(i2) - ancho / 2 : x(i2) - (ancho * S.length) / 2 + k * ancho, y0 = y(acc), y1 = y(acc + v);
          g.appendChild(el('rect', { x: bx, y: y1, width: ancho, height: Math.max(1, y0 - y1), rx: Math.min(4, ancho / 3), style: 'fill:' + col + ';transition:opacity .15s', 'data-b': 1 }));
          if (c.apilado) acc += v;
        });
      }
      var y2 = null, sc2 = null;
      if (L) {
        var mx2 = L.max !== undefined ? L.max : Math.max.apply(null, L.datos.filter(function (v) { return v !== null; }).concat([1]));
        sc2 = nice(0, mx2, 4); y2 = function (v) { return P.t + (H - P.t - P.b) * (1 - v / sc2.max); };
        var g2 = el('g', { 'class': 'ax' }); sc2.ticks.forEach(function (v) { g2.appendChild(el('text', { x: W - P.r + 6, y: y2(v) + 4 }, (L.fmt || fnum)(v))); }); svg.appendChild(g2);
        var pts = L.datos.map(function (v, i) { return v === null ? null : [x(i), y2(v)]; }).filter(Boolean);
        if (pts.length) svg.appendChild(el('path', { d: suave(pts), fill: 'none', style: 'stroke:' + color(L.color || 'chart-2') + ';stroke-width:2.2', 'stroke-linecap': 'round', 'data-l': 1 }));
      }
      interaccion(svg, host, tip, W, H, n, x, function (i) {
        var h = '<strong>' + esc(titulos ? titulos[i] : labels[i]) + '</strong>';
        S.forEach(function (s) { h += '<br><i style="display:inline-block;width:8px;height:8px;border-radius:2px;background:' + color(s.color) + ';margin-right:6px"></i>' + esc(s.nombre) + ': ' + (c.fmtTip ? c.fmtTip(s.datos[i], s) : fmtY(s.datos[i])); });
        if (L) h += '<br>' + esc(L.nombre) + ': ' + (L.fmt || fnum)(L.datos[i]);
        return { html: h };
      }, null, function (idx) { grupos.forEach(function (g, j) { g.style.opacity = idx < 0 || j === idx ? 1 : .45; }); });
      Object.assign(P, Pr);
    }, c);
  }

  /* ---------- Dona ---------- */
  function dona(host, c) {
    montar(host, function (svg, W) {
      var S = Math.min(W, c.tam || 220), r = S / 2 - 6, g = S / 2, tot = c.items.reduce(function (a, i) { return a + i.valor; }, 0) || 1;
      svg.setAttribute('viewBox', '0 0 ' + S + ' ' + S); svg.setAttribute('height', S); svg.style.maxWidth = S + 'px'; svg.style.margin = '0 auto';
      var a0 = -Math.PI / 2;
      c.items.forEach(function (it, k) {
        var a1 = a0 + it.valor / tot * Math.PI * 2 - .02; if (it.valor <= 0) return;
        var x0 = g + r * Math.cos(a0), y0 = g + r * Math.sin(a0), x1 = g + r * Math.cos(a1), y1 = g + r * Math.sin(a1), lg = a1 - a0 > Math.PI ? 1 : 0;
        svg.appendChild(el('path', { d: 'M' + x0 + ',' + y0 + 'A' + r + ',' + r + ' 0 ' + lg + ' 1 ' + x1 + ',' + y1, fill: 'none', style: 'stroke:' + color(it.color || ('chart-' + (k + 1))) + ';stroke-width:' + (r * .26), 'stroke-linecap': 'butt' }));
        a0 += it.valor / tot * Math.PI * 2;
      });
      if (c.centro) { svg.appendChild(el('text', { x: g, y: g + 2, 'text-anchor': 'middle', style: 'font:600 ' + S * .16 + 'px var(--font-display);fill:var(--ink)' }, c.centro.valor)); svg.appendChild(el('text', { x: g, y: g + S * .12, 'text-anchor': 'middle', 'class': 'lbl' }, c.centro.texto)); }
    });
  }

  /* ---------- Medidor semicircular ---------- */
  function medidor(host, c) {
    montar(host, function (svg, W) {
      var S = Math.min(W, c.tam || 240), r = S / 2 - 14, cx = S / 2, cy = S / 2 + 4, mn = c.min || 0, mx = c.max, v = Math.max(mn, Math.min(mx, c.valor));
      svg.setAttribute('viewBox', '0 0 ' + S + ' ' + (S / 2 + 34)); svg.setAttribute('height', S / 2 + 34); svg.style.maxWidth = S + 'px'; svg.style.margin = '0 auto';
      var ang = function (val) { return Math.PI + (val - mn) / (mx - mn) * Math.PI; };
      var arco = function (a, b) { return 'M' + (cx + r * Math.cos(a)) + ',' + (cy + r * Math.sin(a)) + 'A' + r + ',' + r + ' 0 ' + (b - a > Math.PI ? 1 : 0) + ' 1 ' + (cx + r * Math.cos(b)) + ',' + (cy + r * Math.sin(b)); };
      var ant = mn; (c.zonas || [{ hasta: mx, color: 'chart-1' }]).forEach(function (z) { svg.appendChild(el('path', { d: arco(ang(ant) + .01, ang(z.hasta) - .01), fill: 'none', style: 'stroke:' + color(z.color) + ';stroke-width:12;opacity:.9', 'stroke-linecap': 'butt' })); ant = z.hasta; });
      var a = ang(v);
      svg.appendChild(el('line', { x1: cx, y1: cy, x2: cx + (r - 18) * Math.cos(a), y2: cy + (r - 18) * Math.sin(a), style: 'stroke:var(--ink);stroke-width:3', 'stroke-linecap': 'round' }));
      svg.appendChild(el('circle', { cx: cx, cy: cy, r: 6, style: 'fill:var(--ink)' }));
      svg.appendChild(el('text', { x: cx, y: cy + 28, 'text-anchor': 'middle', style: 'font:600 ' + S * .11 + 'px var(--font-display);fill:var(--ink)' }, (c.texto !== undefined ? c.texto : fnum(c.valor))));
      svg.appendChild(el('text', { x: cx - r, y: cy + 16, 'text-anchor': 'middle', 'class': 'lbl' }, fnum(mn))); svg.appendChild(el('text', { x: cx + r, y: cy + 16, 'text-anchor': 'middle', 'class': 'lbl' }, fnum(mx)));
    });
  }

  /* ---------- Brujula de viento ---------- */
  function brujula(host, c) {
    montar(host, function (svg, W) {
      var S = Math.min(W, c.tam || 220), cx = S / 2, r = S / 2 - 22;
      svg.setAttribute('viewBox', '0 0 ' + S + ' ' + S); svg.setAttribute('height', S); svg.style.maxWidth = S + 'px'; svg.style.margin = '0 auto';
      svg.appendChild(el('circle', { cx: cx, cy: cx, r: r, fill: 'none', style: 'stroke:var(--border-strong);stroke-width:1.5' }));
      svg.appendChild(el('circle', { cx: cx, cy: cx, r: r * .6, fill: 'none', style: 'stroke:var(--border);stroke-width:1' }));
      for (var i = 0; i < 72; i++) { var a = i * 5 * Math.PI / 180, l = i % 9 === 0 ? 10 : (i % 3 === 0 ? 6 : 3); svg.appendChild(el('line', { x1: cx + (r) * Math.sin(a), y1: cx - (r) * Math.cos(a), x2: cx + (r - l) * Math.sin(a), y2: cx - (r - l) * Math.cos(a), style: 'stroke:var(--ink-3);stroke-width:1' })); }
      [['N', 0], ['E', 90], ['S', 180], ['O', 270]].forEach(function (p) { var a = p[1] * Math.PI / 180; svg.appendChild(el('text', { x: cx + (r + 12) * Math.sin(a), y: cx - (r + 12) * Math.cos(a) + 4, 'text-anchor': 'middle', 'class': 'lbl' }, p[0])); });
      // flecha hacia donde SOPLA el viento (grados meteorologicos = de donde viene)
      var rot = (c.grados + 180) % 360;
      var g = el('g', { transform: 'rotate(' + rot + ' ' + cx + ' ' + cx + ')' });
      g.appendChild(el('path', { d: 'M' + cx + ',' + (cx - r * .72) + 'L' + (cx + 9) + ',' + (cx + r * .15) + 'L' + cx + ',' + (cx + r * .02) + 'L' + (cx - 9) + ',' + (cx + r * .15) + 'Z', style: 'fill:var(--accent)' }));
      svg.appendChild(g);
      svg.appendChild(el('text', { x: cx, y: cx + r * .42, 'text-anchor': 'middle', style: 'font:600 ' + S * .1 + 'px var(--font-display);fill:var(--ink)' }, fnum(c.velocidad, 1) + ' ' + (c.unidad || 'm/s')));
      if (c.rafaga !== undefined && c.rafaga !== null) svg.appendChild(el('text', { x: cx, y: cx + r * .42 + 16, 'text-anchor': 'middle', 'class': 'lbl' }, 'Rafagas ' + fnum(c.rafaga, 1)));
    });
  }

  /* ---------- Arco solar ---------- */
  function arcoSolar(host, c) {
    montar(host, function (svg, W) {
      var H = 140, pad = 24, cy = H - 24, rx = (W - pad * 2) / 2, ry = H - 52, cx = W / 2;
      svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H); svg.setAttribute('height', H);
      var min = function (hm) { var p = hm.split(':'); return +p[0] * 60 + +p[1]; }, a = min(c.amanecer), b = min(c.atardecer), n = min(c.ahora), f = Math.max(0, Math.min(1, (n - a) / (b - a)));
      svg.appendChild(el('line', { x1: pad, x2: W - pad, y1: cy, y2: cy, style: 'stroke:var(--border-strong)' }));
      svg.appendChild(el('path', { d: 'M' + pad + ',' + cy + 'A' + rx + ',' + ry + ' 0 0 1 ' + (W - pad) + ',' + cy, fill: 'none', style: 'stroke:var(--border-strong);stroke-dasharray:4 5;stroke-width:1.5' }));
      var ang = Math.PI * (1 - f), px = cx + rx * Math.cos(ang), py = cy - ry * Math.sin(ang);
      if (n > a && n < b) {
        svg.appendChild(el('path', { d: 'M' + pad + ',' + cy + 'A' + rx + ',' + ry + ' 0 0 1 ' + px + ',' + py, fill: 'none', style: 'stroke:var(--chart-2);stroke-width:3', 'stroke-linecap': 'round' }));
        svg.appendChild(el('circle', { cx: px, cy: py, r: 9, style: 'fill:var(--chart-2);stroke:var(--surface);stroke-width:3' }));
      }
      svg.appendChild(el('text', { x: pad, y: cy + 18, 'class': 'lbl' }, 'Amanece ' + c.amanecer)); svg.appendChild(el('text', { x: W - pad, y: cy + 18, 'text-anchor': 'end', 'class': 'lbl' }, 'Anochece ' + c.atardecer));
      if (c.duracion) svg.appendChild(el('text', { x: cx, y: cy - 8, 'text-anchor': 'middle', style: 'font:600 15px var(--font-display);fill:var(--ink)' }, c.duracion));
    });
  }

  /* ---------- Perfil del suelo (profundidad) ---------- */
  function perfil(host, c) {
    montar(host, function (svg, W) {
      var n = c.items.length, H = 28 + n * 40, mx = c.max, mn = c.min || 0, x0 = 92, x1 = W - 64;
      svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H); svg.setAttribute('height', H);
      c.items.forEach(function (it, i) {
        var yy = 14 + i * 40, v = it.valor; if (v === null || v === undefined) return;
        svg.appendChild(el('text', { x: x0 - 10, y: yy + 17, 'text-anchor': 'end', 'class': 'lbl' }, it.nombre));
        svg.appendChild(el('rect', { x: x0, y: yy + 4, width: x1 - x0, height: 18, rx: 5, style: 'fill:var(--surface-3)' }));
        svg.appendChild(el('rect', { x: x0, y: yy + 4, width: Math.max(4, (x1 - x0) * (v - mn) / (mx - mn)), height: 18, rx: 5, style: 'fill:' + color(c.color || 'chart-1') + ';opacity:' + (.45 + .55 * i / Math.max(1, n - 1)) }));
        svg.appendChild(el('text', { x: x1 + 8, y: yy + 18, 'class': 'lbl' }, c.fmt ? c.fmt(v) : fnum(v)));
      });
    });
  }

  w.MKCharts = { linea: linea, barras: barras, dona: dona, medidor: medidor, brujula: brujula, arcoSolar: arcoSolar, perfil: perfil, fnum: fnum };
})(window, document);
