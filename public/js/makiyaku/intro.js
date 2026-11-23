/* Entrada a Makiyaku. Hojas que giran hacia el centro, las dos hojas del logo se unen en una gota,
   aparece el nombre y el logo vuela a su lugar en la barra superior mientras se descubre el sistema.
   Imagen en canvas y sonido sintetizado con WebAudio. La escena es determinista (sirve para exportar a video). */
(function (w, d) {
  'use strict';
  var DUR = 7.4, T_UNION = 2.9, T_NOMBRE = 3.5, T_LEMA = 4.3, T_SALIDA = 5.9;
  var TAU = Math.PI * 2;

  function rng(seed) { var a = seed >>> 0; return function () { a = (a + 0x6D2B79F5) >>> 0; var t = a; t = Math.imul(t ^ (t >>> 15), t | 1); t ^= t + Math.imul(t ^ (t >>> 7), t | 61); return ((t ^ (t >>> 14)) >>> 0) / 4294967296; }; }
  function clamp(x, a, b) { return Math.max(a, Math.min(b, x)); }
  function seg(t, a, b) { return clamp((t - a) / (b - a), 0, 1); }
  function eOut(x) { x = clamp(x, 0, 1); return 1 - Math.pow(1 - x, 3); }
  function eIO(x) { x = clamp(x, 0, 1); return x < .5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2; }
  function eBack(x) { x = clamp(x, 0, 1); var c1 = 1.4, c3 = c1 + 1; return 1 + c3 * Math.pow(x - 1, 3) + c1 * Math.pow(x - 1, 2); }
  function lerp(a, b, k) { return a + (b - a) * k; }

  // Logo (viewBox 100x100): dos mitades, nervadura y venas
  var IZQ = new Path2D('M50 4C50 4 16 40.5 16 63A34 34 0 0 0 50 97C42 89.5 40 80.5 46 70.5C54 57.5 58 40 50 4Z');
  var DER = new Path2D('M50 4C58 40 54 57.5 46 70.5C40 80.5 42 89.5 50 97A34 34 0 0 0 84 63C84 40.5 50 4 50 4Z');
  var NERV = new Path2D('M50 4C58 40 54 57.5 46 70.5C40 80.5 42 89.5 50 97');
  var VENAS = ['M54.8 45Q46 37 37 35', 'M50.6 63Q39 54 28.5 53', 'M55.3 55Q63 47.5 70.5 45', 'M44.3 78Q57 68.5 72.5 66.5'].map(function (p) { return new Path2D(p); });
  var HOJA = new Path2D('M0,-1 C.72,-.46 .72,.46 0,1 C-.72,.46 -.72,-.46 0,-1 Z');
  var VENA_H = new Path2D('M0,-.85 L0,.85');
  var COLORES = ['#12b99f', '#5fd6c4', '#9edece', '#c5db5d', '#8db321', '#7b9677', '#def08c'];

  /* ================= ESCENA ================= */
  function Escena(canvas, o) {
    this.c = canvas; this.ctx = canvas.getContext('2d'); this.o = o || {}; this.t = 0;
    var R = this.R = rng(this.o.semilla || 3);
    this.hojas = [];
    for (var i = 0; i < 64; i++) {
      this.hojas.push({ th: R() * TAU, r0: .62 + R() * .5, w: (R() < .5 ? 1 : -1) * 0 + (1.2 + R() * 1.2), entra: R() * 1.1, llega: T_UNION - .15 - R() * .9, rmin: .05 + R() * .14,
        s: .012 + R() * .022, c: COLORES[(R() * COLORES.length) | 0], rot: R() * TAU, vr: (R() - .5) * 4, fl: R() * TAU, vf: 2 + R() * 3, a: .55 + R() * .45,
        x: 0, y: 0, vx: 0, vy: 0, libre: false });
    }
    this.polvo = []; for (var j = 0; j < 70; j++) this.polvo.push({ x: R(), y: R(), s: .6 + R() * 1.8, v: .01 + R() * .02, f: R() * TAU });
    this.tamano(this.o.w || canvas.clientWidth, this.o.h || canvas.clientHeight, this.o.dpr || 1);
  }
  Escena.prototype.tamano = function (W, H, dpr) {
    this.W = W; this.H = H; this.dpr = dpr; this.c.width = Math.round(W * dpr); this.c.height = Math.round(H * dpr);
    this.u = Math.min(W, H); this.cx = W / 2; this.cy = H * .42; this.L = Math.min(H * .34, W * .38);   // lado del logo
  };
  Escena.prototype.paso = function (dt) {
    var t = (this.t += dt), u = this.u;
    for (var i = 0; i < this.hojas.length; i++) {
      var h = this.hojas[i];
      if (!h.libre) {
        // espiral hacia el centro
        var p = eIO(seg(t, h.entra, h.llega)), r = lerp(h.r0, h.rmin, p) * u * 1.05, th = h.th + h.w * t * (0.4 + p);
        var nx = this.cx + Math.cos(th) * r, ny = this.cy + this.L * .1 + Math.sin(th) * r * .78;
        if (t > .02) { h.vx = (nx - h.x) / dt; h.vy = (ny - h.y) / dt; }
        h.x = nx; h.y = ny;
        if (t >= T_UNION) {
          // estallido suave hacia afuera al unirse las hojas del logo
          h.libre = true; var dx = h.x - this.cx, dy = h.y - this.cy, dd = Math.hypot(dx, dy) + 1, f = 260 + this.R() * 420;
          h.vx = h.vx * .25 + dx / dd * f; h.vy = h.vy * .25 + dy / dd * f - 80;
        }
      } else {
        h.vx += (Math.cos(t * 1.3 + h.th) * 30 - h.vx) * Math.min(1, dt * 1.4);
        h.vy += (28 + Math.sin(t * 1.7 + h.th) * 20 - h.vy) * Math.min(1, dt * 1.1);
        h.x += h.vx * dt; h.y += h.vy * dt;
      }
      h.rot += h.vr * dt; h.fl += h.vf * dt;
    }
    for (var j = 0; j < this.polvo.length; j++) { var q = this.polvo[j]; q.y -= q.v * dt; if (q.y < -.02) { q.y = 1.02; q.x = this.R(); } }
  };

  Escena.prototype.dibujar = function () {
    var ctx = this.ctx, W = this.W, H = this.H, t = this.t, dpr = this.dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0); ctx.clearRect(0, 0, W, H);
    var salida = this.o.sinSalida ? 0 : eIO(seg(t, T_SALIDA, DUR - .15)), fondo = 1 - salida;

    // fondo luminoso: aguamarina y lima sobre blanco
    ctx.globalAlpha = fondo;
    ctx.fillStyle = '#f6fcfa'; ctx.fillRect(0, 0, W, H);
    var g1 = ctx.createRadialGradient(W * .12, H * .95, 0, W * .12, H * .95, this.u * 1.0); g1.addColorStop(0, 'rgba(158,222,206,.75)'); g1.addColorStop(1, 'rgba(158,222,206,0)');
    ctx.fillStyle = g1; ctx.fillRect(0, 0, W, H);
    var g2 = ctx.createRadialGradient(W * .9, H * .08, 0, W * .9, H * .08, this.u * .95); g2.addColorStop(0, 'rgba(222,240,140,.7)'); g2.addColorStop(1, 'rgba(222,240,140,0)');
    ctx.fillStyle = g2; ctx.fillRect(0, 0, W, H);
    var brillo = eOut(seg(t, T_UNION - .2, T_UNION + 1.2));
    if (brillo > 0) { var g3 = ctx.createRadialGradient(this.cx, this.cy, 0, this.cx, this.cy, this.L * 1.4); g3.addColorStop(0, 'rgba(18,185,159,' + (.22 * brillo) + ')'); g3.addColorStop(1, 'rgba(18,185,159,0)'); ctx.fillStyle = g3; ctx.fillRect(0, 0, W, H); }
    ctx.fillStyle = '#12b99f';
    for (var j = 0; j < this.polvo.length; j++) { var q = this.polvo[j]; ctx.globalAlpha = fondo * .25 * (.5 + .5 * Math.sin(t * 2 + q.f * 6)) * eOut(seg(t, 0, 1)); ctx.beginPath(); ctx.arc(q.x * W, q.y * H, q.s, 0, TAU); ctx.fill(); }
    ctx.globalAlpha = 1;

    this.dibujarOndas(ctx, t, fondo);
    this.dibujarHojas(ctx, t, fondo);
    this.dibujarLogo(ctx, t, salida);
    this.dibujarNombre(ctx, t, salida);
    var fi = 1 - seg(t, 0, .45); if (fi > 0) { ctx.globalAlpha = fi; ctx.fillStyle = '#f6fcfa'; ctx.fillRect(0, 0, W, H); ctx.globalAlpha = 1; }
  };

  Escena.prototype.dibujarHojas = function (ctx, t, fondo) {
    for (var i = 0; i < this.hojas.length; i++) {
      var h = this.hojas[i], ap = eOut(seg(t, h.entra, h.entra + .5)) * (1 - eIO(seg(t, T_NOMBRE + .4, T_SALIDA + .4)) * .85) * fondo;
      if (ap <= 0.01) continue;
      var tam = h.s * this.u;
      ctx.save(); ctx.translate(h.x, h.y); ctx.rotate(h.rot); ctx.scale(tam * (.3 + .7 * Math.abs(Math.cos(h.fl))), tam * 1.7);
      ctx.globalAlpha = h.a * ap; ctx.fillStyle = h.c; ctx.fill(HOJA);
      ctx.globalAlpha *= .7; ctx.lineWidth = .07; ctx.strokeStyle = '#ffffff'; ctx.stroke(VENA_H); ctx.restore();
    }
    ctx.globalAlpha = 1;
  };

  Escena.prototype.dibujarOndas = function (ctx, t, fondo) {
    for (var k = 0; k < 3; k++) {
      var tt = t - T_UNION - k * .25; if (tt < 0 || tt > 2) continue;
      var r = this.L * (.35 + 1.3 * eOut(tt / 2)), a = (1 - tt / 2) * .55 * fondo;
      ctx.beginPath(); ctx.ellipse(this.cx, this.cy + this.L * .44, r, r * .22, 0, 0, TAU); ctx.lineWidth = 2; ctx.strokeStyle = 'rgba(18,185,159,' + a + ')'; ctx.stroke();
    }
  };

  // posicion y escala del logo (se mueve a la marca de la barra superior al salir)
  Escena.prototype.posLogo = function (salida) {
    var x = this.cx, y = this.cy, L = this.L, dst = this.o.destino;
    if (salida > 0) {
      if (dst) { x = lerp(x, dst.x, salida); y = lerp(y, dst.y, salida); L = lerp(L, dst.size, salida); }
      else { L = L * (1 - .25 * salida); }
    }
    return { x: x, y: y, L: L };
  };

  Escena.prototype.dibujarLogo = function (ctx, t, salida) {
    if (t < .3) return;
    var p = this.posLogo(salida), k = p.L / 100, u = this.u;
    var a = eBack(seg(t, .5, T_UNION)), alfa = eOut(seg(t, .35, 1.2)) * (this.o.destino ? 1 : 1 - salida);
    var gi = function (c) { var g = c.createLinearGradient(15, 5, 55, 100); g.addColorStop(0, '#6fe6d4'); g.addColorStop(.55, '#12b99f'); g.addColorStop(1, '#0a8f80'); return g; };
    var gd = function (c) { var g = c.createLinearGradient(85, 5, 45, 100); g.addColorStop(0, '#ecf6a8'); g.addColorStop(.5, '#c5db5d'); g.addColorStop(1, '#8db321'); return g; };
    var mitad = function (path, grad, desdeX, desdeY, giro) {
      ctx.save();
      ctx.translate(p.x + lerp(desdeX, 0, a), p.y + lerp(desdeY, 0, a)); ctx.rotate(lerp(giro, 0, a));
      ctx.scale(k, k); ctx.translate(-50, -50.5);
      ctx.globalAlpha = alfa; ctx.fillStyle = grad(ctx); ctx.shadowColor = 'rgba(10,125,114,.18)'; ctx.shadowBlur = 24 / k * .05; ctx.fill(path); ctx.restore();
    };
    mitad(IZQ, gi, -u * .55, u * .25, -.9);
    mitad(DER, gd, u * .55, -u * .3, .9);
    // nervadura y venas se dibujan despues de la union
    var dib = eOut(seg(t, T_UNION - .05, T_UNION + .7));
    if (dib > 0) {
      ctx.save(); ctx.translate(p.x, p.y); ctx.scale(k, k); ctx.translate(-50, -50.5);
      ctx.strokeStyle = '#ffffff'; ctx.lineCap = 'round'; ctx.globalAlpha = alfa;
      ctx.lineWidth = 2.8; ctx.setLineDash([110 * dib, 200]); ctx.stroke(NERV);
      ctx.lineWidth = 2.3; ctx.globalAlpha = alfa * .92;
      VENAS.forEach(function (v, i) { var dv = eOut(seg(t, T_UNION + .2 + i * .08, T_UNION + .7 + i * .08)); if (dv > 0) { ctx.setLineDash([40 * dv, 80]); ctx.stroke(v); } });
      ctx.restore();
    }
  };

  Escena.prototype.dibujarNombre = function (ctx, t, salida) {
    if (t < T_NOMBRE) return;
    var fuera = 1 - eOut(seg(salida, 0, .5)), p = this.posLogo(0);
    var tam = Math.max(36, Math.min(this.W * .11, this.u * .12)), y = p.y + this.L * .62 + tam * 1.05, nombre = 'makiyaku';
    ctx.save(); ctx.font = '600 ' + tam + 'px "Bricolage Grotesque", "Figtree", system-ui, sans-serif'; if ('letterSpacing' in ctx) ctx.letterSpacing = (-tam * .02) + 'px';
    var ancho = ctx.measureText(nombre).width, x = this.cx - ancho / 2;
    for (var i = 0; i < nombre.length; i++) {
      var e = eOut(seg(t - T_NOMBRE - i * .06, 0, .6));
      ctx.globalAlpha = e * fuera; ctx.fillStyle = '#13302d'; ctx.fillText(nombre[i], x, y + (1 - e) * tam * .4);
      x += ctx.measureText(nombre[i]).width;
    }
    var ts = eOut(seg(t, T_LEMA, T_LEMA + .8)) * fuera;
    if (ts > 0) {
      var s2 = Math.max(15, tam * .27); ctx.font = '500 ' + s2 + 'px "Figtree", system-ui, sans-serif'; if ('letterSpacing' in ctx) ctx.letterSpacing = (s2 * .04) + 'px';
      ctx.textAlign = 'center'; ctx.globalAlpha = ts; ctx.fillStyle = '#0a7d72';
      ctx.fillText('Agua inteligente para tu cultivo', this.cx, y + tam * .72 + (1 - ts) * 8);
    }
    ctx.restore();
  };

  /* ================= AUDIO ================= */
  function construirAudio(c, destino, t0, saltar) {
    saltar = saltar || 0;
    var maestro = c.createGain(); maestro.gain.value = .9; var comp = c.createDynamicsCompressor(); maestro.connect(comp); comp.connect(destino);
    var eco = c.createDelay(1.2); eco.delayTime.value = .27; var fb = c.createGain(); fb.gain.value = .32; var lpE = c.createBiquadFilter(); lpE.type = 'lowpass'; lpE.frequency.value = 3200;
    var ecoOut = c.createGain(); ecoOut.gain.value = .45; eco.connect(lpE); lpE.connect(fb); fb.connect(eco); lpE.connect(ecoOut); ecoOut.connect(maestro);
    var A = function (t) { return t0 + Math.max(0, t - saltar); };
    var len = c.sampleRate * 2, buf = c.createBuffer(1, len, c.sampleRate), dat = buf.getChannelData(0), R = rng(9);
    for (var i = 0; i < len; i++) dat[i] = R() * 2 - 1;
    function ruido(ini, fin, f0, f1, q, pico) {
      if (fin <= saltar) return;
      var s = c.createBufferSource(); s.buffer = buf; s.loop = true; var bp = c.createBiquadFilter(); bp.type = 'bandpass'; bp.Q.value = q; var g = c.createGain();
      var a = A(Math.max(ini, saltar)), b = A(fin);
      bp.frequency.setValueAtTime(f0, a); bp.frequency.exponentialRampToValueAtTime(f1, b);
      g.gain.setValueAtTime(.0001, a); g.gain.linearRampToValueAtTime(pico, a + (b - a) * .6); g.gain.linearRampToValueAtTime(.0001, b);
      s.connect(bp); bp.connect(g); g.connect(maestro); s.start(a); s.stop(b + .1);
    }
    function tono(f, ini, dur, o) {
      o = o || {}; if (ini + dur <= saltar) return;
      var os = c.createOscillator(), g = c.createGain(), lp = c.createBiquadFilter(); lp.type = 'lowpass'; lp.frequency.value = o.corte || 3000;
      var a = A(Math.max(ini, saltar)), pico = o.gain === undefined ? .2 : o.gain, at = o.ataque || .02;
      os.type = o.tipo || 'sine'; os.frequency.setValueAtTime(f, a); if (o.glide) os.frequency.exponentialRampToValueAtTime(o.glide, a + dur); if (o.det) os.detune.value = o.det;
      g.gain.setValueAtTime(.0001, a); g.gain.exponentialRampToValueAtTime(pico, a + at); g.gain.setValueAtTime(pico, a + Math.max(at, dur - (o.rel || .3))); g.gain.exponentialRampToValueAtTime(.0001, a + dur);
      os.connect(lp); lp.connect(g); g.connect(maestro); if (o.eco) { var ge = c.createGain(); ge.gain.value = o.eco; g.connect(ge); ge.connect(eco); }
      os.start(a); os.stop(a + dur + .05);
    }
    function campana(f, ini, dur, gain) { tono(f, ini, dur, { gain: gain, ataque: .006, rel: dur * .9, eco: .8, corte: 6000 }); tono(f * 2.01, ini, dur * .5, { gain: gain * .28, ataque: .006, rel: dur * .45 }); tono(f * 3.01, ini, dur * .3, { gain: gain * .1, ataque: .006, rel: dur * .28 }); }

    ruido(.1, 2.9, 380, 1600, .9, .16);                  // viento que gira con las hojas
    ruido(T_SALIDA - .1, DUR - .2, 900, 3200, 1.1, .07);  // soplo al descubrir el sistema
    // colchon luminoso (Sol mayor con novena)
    [[196, .06], [246.94, .055], [293.66, .05], [440, .03]].forEach(function (n, i) { tono(n[0], .2 + i * .18, DUR - .5 - i * .18, { tipo: 'triangle', gain: n[1], ataque: 1.8, rel: 1.6, corte: 1400, det: i % 2 ? 6 : -6 }); });
    // union de las hojas: golpe suave + pulsacion
    tono(98, T_UNION, .9, { gain: .3, ataque: .01, glide: 70, rel: .8 });
    tono(784, T_UNION, .3, { gain: .14, ataque: .004, glide: 1568, rel: .25, eco: .6 });
    // arpegio ascendente (pentatonica de Sol)
    [587.33, 659.25, 783.99, 987.77, 1174.66, 1318.51, 1567.98].forEach(function (f, i) { campana(f, T_UNION + .08 + i * .11, 1.6, .075); });
    // acorde del nombre
    [392, 493.88, 587.33, 739.99].forEach(function (f, i) { campana(f, T_NOMBRE + i * .05, 2.6, .065); });
    campana(1567.98, T_LEMA + .1, 1.6, .04);
    // cierre al volar a la barra
    campana(1174.66, T_SALIDA + .3, 1.2, .05); campana(1567.98, T_SALIDA + .42, 1.2, .04);
    var fa = A(DUR - .9); maestro.gain.setValueAtTime(.9, fa); maestro.gain.linearRampToValueAtTime(0, A(DUR));
    return maestro;
  }

  /* ================= REPRODUCCION ================= */
  var Intro = { DUR: DUR, Escena: Escena, construirAudio: construirAudio, activa: false };

  function fuentes() {
    if (!d.fonts || !d.fonts.load) return Promise.resolve();
    return Promise.race([Promise.all([d.fonts.load('600 64px "Bricolage Grotesque"'), d.fonts.load('500 20px "Figtree"')]), new Promise(function (r) { setTimeout(r, 1200); })]);
  }

  Intro.reproducir = function () {
    var el = d.getElementById('intro'); if (!el || Intro.activa) return;
    Intro.activa = true;
    el.classList.add('on'); d.documentElement.style.overflow = 'hidden';
    var cv = el.querySelector('canvas'), hint = d.getElementById('intro-hint'), saltar = d.getElementById('intro-saltar');
    // primer cuadro de inmediato para tapar el sistema mientras cargan las fuentes
    var ctx0 = cv.getContext('2d'); cv.width = w.innerWidth; cv.height = w.innerHeight; ctx0.fillStyle = '#f6fcfa'; ctx0.fillRect(0, 0, cv.width, cv.height);

    fuentes().then(function () {
      el.style.background = '';
      var dpr = Math.min(w.devicePixelRatio || 1, 2), marca = d.querySelector('#marca .logo'), destino = null;
      if (marca) { var r = marca.getBoundingClientRect(); destino = { x: r.left + r.width / 2, y: r.top + r.height / 2, size: r.height }; }
      var esc = new Escena(cv, { w: w.innerWidth, h: w.innerHeight, dpr: dpr, semilla: 5, destino: destino });
      var reducir = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
      var inicio = performance.now(), fin = false, acc = 0, ultimo = inicio, audio = null;
      var quiere = w.MKSound ? MKSound.activo : false;
      function arrancar(desde) {
        if (!quiere || audio || !w.MKSound) return;
        var c = MKSound.contexto(); if (!c || c.state !== 'running') return;
        var g = c.createGain(); g.gain.value = Math.max(.05, MKSound.volumen) * 1.1; g.connect(c.destination);
        audio = construirAudio(c, g, c.currentTime + .04, desde); if (hint) hint.hidden = true;
      }
      if (quiere && !reducir) {
        MKSound.desbloquear();
        setTimeout(function () { arrancar(0); if (!audio && hint) hint.hidden = false; }, 50);
        var gesto = function () { if (fin) return; MKSound.desbloquear(); setTimeout(function () { arrancar(Math.min(DUR - 1, (performance.now() - inicio) / 1000)); }, 30); };
        ['pointerdown', 'keydown', 'touchstart'].forEach(function (ev) { d.addEventListener(ev, gesto, { once: true, passive: true }); });
      }
      function terminar() {
        if (fin) return; fin = true;
        el.style.transition = 'opacity .35s'; el.style.opacity = '0';
        setTimeout(function () { el.classList.remove('on'); el.style.opacity = ''; el.style.transition = ''; d.documentElement.style.overflow = ''; Intro.activa = false; d.dispatchEvent(new CustomEvent('mk:intro-fin')); }, 360);
        if (audio) { try { audio.gain.cancelScheduledValues(0); audio.gain.setTargetAtTime(0, audio.context.currentTime, .15); } catch (e) {} }
      }
      saltar.onclick = terminar;
      d.addEventListener('keydown', function esc_(e) { if (e.key === 'Escape' && !fin) { terminar(); d.removeEventListener('keydown', esc_); } });
      w.addEventListener('resize', function () { esc.tamano(w.innerWidth, w.innerHeight, dpr); });
      if (reducir) { esc.t = T_LEMA + 1; esc.dibujar(); setTimeout(terminar, 1400); return; }
      (function bucle(ahora) {
        if (fin) return;
        acc += Math.min(.1, (ahora - ultimo) / 1000); ultimo = ahora;
        while (acc >= 1 / 60) { esc.paso(1 / 60); acc -= 1 / 60; }
        esc.dibujar();
        if (esc.t >= DUR) { terminar(); return; }
        requestAnimationFrame(bucle);
      })(performance.now());
    });
  };

  w.MKIntro = Intro;
  d.addEventListener('DOMContentLoaded', function () {
    var el = d.getElementById('intro'); if (!el) return;
    var visto = false; try { visto = sessionStorage.getItem('mk-intro') === '1'; sessionStorage.setItem('mk-intro', '1'); } catch (e) {}
    if (el.getAttribute('data-forzar') === '1' || !visto) Intro.reproducir();
  });
})(window, document);
