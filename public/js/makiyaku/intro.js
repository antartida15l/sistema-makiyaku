/* Presentacion de Makiyaku: hojas llevadas por el viento, una gota cae, el logo crece.
   Todo se dibuja en canvas y el sonido se sintetiza con WebAudio (sin archivos).
   La escena es determinista: dado el mismo tiempo dibuja los mismos cuadros (sirve para exportar a video). */
(function (w, d) {
  'use strict';
  var DUR = 10.6, T_GOTA = 3.15, T_IMPACTO = 4.1, T_PLANTA = 4.9, T_TEXTO = 6.2, T_SUB = 7.2, T_FADE = 9.7;
  var TAU = Math.PI * 2;
  var base = (function () { var s = d.currentScript ? d.currentScript.src : ''; return s ? s.replace(/\/js\/makiyaku\/intro\.js.*$/, '') : ''; })();

  function rng(seed) { var a = seed >>> 0; return function () { a = (a + 0x6D2B79F5) >>> 0; var t = a; t = Math.imul(t ^ (t >>> 15), t | 1); t ^= t + Math.imul(t ^ (t >>> 7), t | 61); return ((t ^ (t >>> 14)) >>> 0) / 4294967296; }; }
  function clamp(x, a, b) { return Math.max(a, Math.min(b, x)); }
  function ease(x) { x = clamp(x, 0, 1); return 1 - Math.pow(1 - x, 3); }
  function easeIO(x) { x = clamp(x, 0, 1); return x < .5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2; }
  function seg(t, a, b) { return clamp((t - a) / (b - a), 0, 1); }

  var LEAF = new Path2D('M0,-1 C.72,-.46 .72,.46 0,1 C-.72,.46 -.72,-.46 0,-1 Z');
  var VEIN = new Path2D('M0,-.85 L0,.85');
  var COLORES = ['#8cc97a', '#6fd1a8', '#c9e6a0', '#a3cc9b', '#4fae7e', '#e3ab3a'];

  /* ================= ESCENA ================= */
  function Escena(canvas, opts) {
    this.c = canvas; this.ctx = canvas.getContext('2d');
    this.o = opts || {}; this.logo = this.o.logo; this.t = 0;
    var R = rng(this.o.semilla || 7);
    this.R = R;
    this.hojas = []; this.polvo = [];
    for (var i = 0; i < 78; i++) this.hojas.push(this.nuevaHoja(true, R));
    for (var j = 0; j < 110; j++) this.polvo.push({ x: R(), y: R(), s: .5 + R() * 1.6, v: .008 + R() * .02, f: R() * TAU, a: .15 + R() * .5 });
    this.off = d.createElement('canvas'); this.mask = d.createElement('canvas');
    this.tamano(this.o.w || canvas.clientWidth, this.o.h || canvas.clientHeight, this.o.dpr || 1);
    this.impulso = false;
  }
  Escena.prototype.tamano = function (W, H, dpr) {
    this.W = W; this.H = H; this.dpr = dpr;
    this.c.width = Math.round(W * dpr); this.c.height = Math.round(H * dpr);
    var u = Math.min(W, H);
    var lh = Math.min(H * .4, W * .44); this.lh = lh; this.lw = lh * 1221 / 1400;
    this.cx = W / 2; this.ly = H * .43 - lh / 2;               // arriba del logo
    this.surfY = this.ly + lh * .60;                            // superficie del agua del cuenco
    this.u = u;
    this.off.width = Math.ceil(this.lw * dpr); this.off.height = Math.ceil(lh * dpr);
    this.mask.width = this.off.width; this.mask.height = this.off.height;
  };
  Escena.prototype.nuevaHoja = function (inicial, R) {
    var s = (.016 + R() * .03);
    return { x: -.04 - R() * .18, y: .1 + R() * .85, vx: 0, vy: 0, s: s, c: COLORES[(R() * COLORES.length) | 0], rot: R() * TAU, vr: (R() - .5) * 3, fl: R() * TAU, vf: 1.5 + R() * 3, ph: R() * TAU, fz: .6 + R() * 1.3, a: .5 + R() * .5, entra: R() * 2.2, viva: true, flot: false };
  };

  // fisica con paso fijo: viento -> impacto -> flotar
  Escena.prototype.paso = function (dt) {
    var t = this.t += dt, W = this.W, H = this.H, u = this.u;
    var viento = t < T_GOTA ? 330 + 230 * ease(seg(t, .4, 1.8)) : (t < T_IMPACTO ? 560 * (1 - ease(seg(t, T_GOTA, T_IMPACTO))) : 26);
    if (!this.impulso && t >= T_IMPACTO) {
      // Al caer la gota todas las hojas estallan hacia afuera desde el agua (composicion simetrica).
      this.impulso = true;
      var cx = this.cx / W, cy = this.surfY / H;
      for (var k = 0; k < this.hojas.length; k++) {
        var h = this.hojas[k], ang = this.R() * TAU, vel = 220 + this.R() * 620;
        h.x = cx + (this.R() - .5) * .05; h.y = cy + (this.R() - .5) * .03; h.entra = t; h.viva = true; h.flot = true;
        h.vx = Math.cos(ang) * vel; h.vy = Math.sin(ang) * vel * .8 - 140; h.a = .5 + this.R() * .5;
      }
    }
    for (var i = 0; i < this.hojas.length; i++) {
      var h = this.hojas[i];
      if (!h.viva || t < h.entra) continue;
      if (!h.flot && t < T_GOTA + 1.2) {
        var dx2 = (h.x * W - this.cx), dy2 = (h.y * H - this.H * .5);
        h.vx += (viento * 1.2 - h.vx) * Math.min(1, dt * 1.8);
        h.vy += (Math.sin(t * h.fz + h.ph) * 70 + Math.sin(h.x * 7 + t) * 40 - h.vy) * Math.min(1, dt * 2.2);
        h.vy += -dy2 * .22 * dt;                      // la corriente pasa cerca del centro
      } else {
        // flotando: caida suave con viento ligero y vaiven
        h.vx += ((Math.cos(t * h.fz + h.ph) * 40 + viento) - h.vx) * Math.min(1, dt * .9);
        h.vy += ((20 + 24 * h.s * 40 + Math.sin(t * h.fz * 1.3 + h.ph) * 18) - h.vy) * Math.min(1, dt * .9);
      }
      h.x += h.vx * dt / W; h.y += h.vy * dt / H; h.rot += h.vr * dt; h.fl += h.vf * dt;
      if (h.x > 1.15 || h.y > 1.12 || h.x < -.2 || h.y < -.3) {
        if (t < T_GOTA - .4 && !h.flot) { h.x = -.08 - this.R() * .2; h.y = .1 + this.R() * .8; h.vx = 0; h.vy = 0; }
        else if (t > T_IMPACTO + 1 && t < DUR - 2) { h.x = this.R() * 1.1 - .05; h.y = -.1 - this.R() * .15; h.vx = 0; h.vy = 30; h.flot = true; }
        else h.viva = false;
      }
    }
    for (var j = 0; j < this.polvo.length; j++) { var p = this.polvo[j]; p.y -= p.v * dt; p.x += Math.sin(t * .4 + p.f) * .004 * dt * 10; if (p.y < -.02) { p.y = 1.02; p.x = this.R(); } }
  };

  Escena.prototype.dibujar = function () {
    var ctx = this.ctx, W = this.W, H = this.H, t = this.t, dpr = this.dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    // fondo
    var g = ctx.createLinearGradient(0, 0, 0, H); g.addColorStop(0, '#020c0a'); g.addColorStop(1, '#0a2a22');
    ctx.globalAlpha = 1; ctx.fillStyle = g; ctx.fillRect(0, 0, W, H);
    // resplandor tras el logo despues del impacto
    var rb = ease(seg(t, T_IMPACTO, T_IMPACTO + 1.6)) * (1 - .25 * seg(t, T_FADE - 1, T_FADE));
    if (rb > 0) {
      var rg = ctx.createRadialGradient(this.cx, this.ly + this.lh * .55, 0, this.cx, this.ly + this.lh * .55, this.u * .75);
      rg.addColorStop(0, 'rgba(140,201,122,' + (.30 * rb) + ')'); rg.addColorStop(.45, 'rgba(79,174,126,' + (.10 * rb) + ')'); rg.addColorStop(1, 'rgba(0,0,0,0)');
      ctx.fillStyle = rg; ctx.fillRect(0, 0, W, H);
    }
    // polvo / luciérnagas
    ctx.fillStyle = '#d8f0c8';
    var apareceP = ease(seg(t, .2, 1.8));
    for (var j = 0; j < this.polvo.length; j++) {
      var p = this.polvo[j]; var par = .5 + .5 * Math.sin(t * 1.5 + p.f * 5);
      ctx.globalAlpha = p.a * par * .55 * apareceP; ctx.beginPath(); ctx.arc(p.x * W, p.y * H, p.s, 0, TAU); ctx.fill();
    }
    ctx.globalAlpha = 1;

    // gota y ondas, hojas al fondo, logo y texto por encima
    this.dibujarGota(ctx, t);
    this.dibujarOndas(ctx, t);
    this.dibujarHojas(ctx, t);
    this.dibujarLogo(ctx, t);
    this.dibujarTexto(ctx, t);
    // vineta y fundido final
    var v = ctx.createRadialGradient(W / 2, H / 2, this.u * .35, W / 2, H / 2, Math.max(W, H) * .78);
    v.addColorStop(0, 'rgba(0,0,0,0)'); v.addColorStop(1, 'rgba(0,0,0,.55)'); ctx.fillStyle = v; ctx.fillRect(0, 0, W, H);
    var fo = seg(t, T_FADE, DUR); if (fo > 0) { ctx.fillStyle = 'rgba(2,12,10,' + easeIO(fo) + ')'; ctx.fillRect(0, 0, W, H); }
    var fi = 1 - seg(t, 0, .9); if (fi > 0) { ctx.fillStyle = 'rgba(0,0,0,' + fi + ')'; ctx.fillRect(0, 0, W, H); }
  };

  Escena.prototype.dibujarHojas = function (ctx, t) {
    for (var i = 0; i < this.hojas.length; i++) {
      var h = this.hojas[i]; if (!h.viva || t < h.entra) continue;
      var tam = h.s * this.u;
      ctx.save(); ctx.translate(h.x * this.W, h.y * this.H); ctx.rotate(h.rot);
      ctx.scale(tam * (.3 + .7 * Math.abs(Math.cos(h.fl))), tam * 1.7);
      ctx.globalAlpha = h.a * (.35 + .65 * ease(seg(t - h.entra, 0, .8))) * (1 - .5 * ease(seg(t, T_TEXTO - .4, T_TEXTO + 1.2))); ctx.fillStyle = h.c; ctx.fill(LEAF);
      ctx.globalAlpha *= .6; ctx.lineWidth = .06; ctx.strokeStyle = 'rgba(255,255,255,.8)'; ctx.stroke(VEIN);
      ctx.restore();
    }
    ctx.globalAlpha = 1;
  };

  Escena.prototype.dibujarGota = function (ctx, t) {
    if (t < T_GOTA - .6 || t > T_IMPACTO + .05) return;
    var p = seg(t, T_GOTA, T_IMPACTO), y0 = -this.u * .05, y1 = this.surfY - this.u * .03;
    var y = y0 + (y1 - y0) * (p * p), s = this.u * .02, a = ease(seg(t, T_GOTA - .6, T_GOTA + .1));
    ctx.save(); ctx.translate(this.cx, y); ctx.globalAlpha = a;
    // estela
    var tg = ctx.createLinearGradient(0, -s * 12, 0, 0); tg.addColorStop(0, 'rgba(159,225,208,0)'); tg.addColorStop(1, 'rgba(159,225,208,.55)');
    ctx.fillStyle = tg; ctx.beginPath(); ctx.moveTo(-s * .5, -s * 12 * p); ctx.lineTo(s * .5, -s * 12 * p); ctx.lineTo(s * .9, 0); ctx.lineTo(-s * .9, 0); ctx.fill();
    ctx.scale(s, s * 1.35);
    ctx.beginPath(); ctx.moveTo(0, -2.2); ctx.bezierCurveTo(.4, -1, 1.05, -.1, 1.05, .55); ctx.arc(0, .55, 1.05, 0, Math.PI, false); ctx.bezierCurveTo(-1.05, -.1, -.4, -1, 0, -2.2); ctx.closePath();
    var gg = ctx.createLinearGradient(0, -2, 0, 1.6); gg.addColorStop(0, '#dff7f0'); gg.addColorStop(1, '#4fc3b0'); ctx.fillStyle = gg; ctx.fill();
    ctx.restore(); ctx.globalAlpha = 1;
  };

  Escena.prototype.dibujarOndas = function (ctx, t) {
    for (var k = 0; k < 4; k++) {
      var tt = t - T_IMPACTO - k * .22; if (tt < 0 || tt > 2.4) continue;
      var r = this.u * (.02 + .34 * ease(tt / 2.4)), a = (1 - tt / 2.4) * .7;
      ctx.save(); ctx.translate(this.cx, this.surfY); ctx.scale(1, .26);
      ctx.beginPath(); ctx.arc(0, 0, r, 0, TAU); ctx.lineWidth = 2.2 - k * .35; ctx.strokeStyle = 'rgba(159,225,208,' + a + ')'; ctx.stroke(); ctx.restore();
    }
  };

  Escena.prototype.dibujarLogo = function (ctx, t) {
    if (!this.logo || !this.logo.complete || !this.logo.naturalWidth || t < T_IMPACTO) return;
    var dpr = this.dpr, ow = this.off.width, oh = this.off.height, o = this.off.getContext('2d'), m = this.mask.getContext('2d');
    o.globalCompositeOperation = 'source-over'; o.clearRect(0, 0, ow, oh); o.drawImage(this.logo, 0, 0, ow, oh);
    // mascara: cuenco (circulo que crece desde el agua) + planta (sube desde el cuenco)
    m.setTransform(1, 0, 0, 1, 0, 0); m.clearRect(0, 0, ow, oh);
    var bowlTop = oh * .52, pb = ease(seg(t, T_IMPACTO, T_IMPACTO + 1.25)), pp = easeIO(seg(t, T_PLANTA, T_PLANTA + 1.7));
    if (pb > 0) {
      var R = Math.hypot(ow, oh) * .62 * pb, cx = ow / 2, cy = oh * .60;
      var rg = m.createRadialGradient(cx, cy, Math.max(0, R - 40 * dpr), cx, cy, R + 4); rg.addColorStop(0, 'rgba(255,255,255,1)'); rg.addColorStop(1, 'rgba(255,255,255,0)');
      m.save(); m.beginPath(); m.rect(0, bowlTop - 4 * dpr, ow, oh - bowlTop + 4 * dpr); m.clip(); m.fillStyle = rg; m.fillRect(0, 0, ow, oh); m.restore();
    }
    if (pp > 0) {
      var edge = bowlTop + 30 * dpr - (bowlTop + 60 * dpr) * pp, grad = m.createLinearGradient(0, edge, 0, edge + 90 * dpr);
      grad.addColorStop(0, 'rgba(255,255,255,0)'); grad.addColorStop(1, 'rgba(255,255,255,1)');
      m.save(); m.beginPath(); m.rect(0, 0, ow, bowlTop + 6 * dpr); m.clip(); m.fillStyle = grad; m.fillRect(0, 0, ow, oh); m.restore();
    }
    o.globalCompositeOperation = 'destination-in'; o.drawImage(this.mask, 0, 0);
    // un leve "respiro" de escala al terminar
    var resp = 1 + .012 * Math.sin(seg(t, T_PLANTA + 1.2, T_FADE) * TAU * 1.5) * ease(seg(t, T_PLANTA + 1.2, T_PLANTA + 2));
    ctx.save(); ctx.translate(this.cx, this.ly + this.lh / 2); ctx.scale(resp, resp);
    ctx.shadowColor = 'rgba(140,201,122,.22)'; ctx.shadowBlur = 26;
    ctx.drawImage(this.off, -this.lw / 2, -this.lh / 2, this.lw, this.lh); ctx.restore();
  };

  Escena.prototype.dibujarTexto = function (ctx, t) {
    if (t < T_TEXTO) return;
    var nombre = 'Makiyaku', tam = Math.max(34, Math.min(this.W * .12, this.u * .13)), y = this.ly + this.lh + tam * 1.05;
    ctx.save(); ctx.textBaseline = 'alphabetic'; ctx.font = '600 ' + tam + 'px "Bricolage Grotesque", "Figtree", system-ui, sans-serif';
    var ancho = ctx.measureText(nombre).width, x = this.cx - ancho / 2;
    for (var i = 0; i < nombre.length; i++) {
      var ch = nombre[i], tt = seg(t - T_TEXTO - i * .07, 0, .7), e = ease(tt);
      ctx.globalAlpha = e; ctx.fillStyle = '#eaf6ee';
      ctx.fillText(ch, x, y + (1 - e) * tam * .35);
      x += ctx.measureText(ch).width;
    }
    var ts = ease(seg(t, T_SUB, T_SUB + 1)); if (ts > 0) {
      var s2 = Math.max(14, tam * .27); ctx.font = '400 ' + s2 + 'px "Figtree", system-ui, sans-serif'; if ('letterSpacing' in ctx) ctx.letterSpacing = (s2 * .08) + 'px';
      ctx.fillStyle = '#9fd3bd'; ctx.globalAlpha = ts; ctx.textAlign = 'center';
      ctx.fillText('Agua inteligente para tu cultivo', this.cx, y + tam * .7 + (1 - ts) * 8);
    }
    ctx.restore();
  };

  /* ================= AUDIO (reutilizable: contexto en vivo u OfflineAudioContext) ================= */
  function construirAudio(c, destino, t0, saltar) {
    saltar = saltar || 0;
    var maestro = c.createGain(); maestro.gain.value = .9; var comp = c.createDynamicsCompressor(); maestro.connect(comp); comp.connect(destino);
    var eco = c.createDelay(1.2); eco.delayTime.value = .31; var fb = c.createGain(); fb.gain.value = .34; var lpE = c.createBiquadFilter(); lpE.type = 'lowpass'; lpE.frequency.value = 2400;
    var ecoOut = c.createGain(); ecoOut.gain.value = .5; eco.connect(lpE); lpE.connect(fb); fb.connect(eco); lpE.connect(ecoOut); ecoOut.connect(maestro);
    var A = function (t) { return t0 + Math.max(0, t - saltar); };
    var ok = function (t) { return t >= saltar; };

    // ruido (viento y hojas)
    var len = c.sampleRate * 2, buf = c.createBuffer(1, len, c.sampleRate), dat = buf.getChannelData(0), R = rng(5);
    for (var i = 0; i < len; i++) dat[i] = R() * 2 - 1;
    function ruido(ini, fin, f0, f1, q, pico, tipo) {
      if (fin <= saltar) return;
      var s = c.createBufferSource(); s.buffer = buf; s.loop = true;
      var bp = c.createBiquadFilter(); bp.type = tipo || 'bandpass'; bp.Q.value = q;
      var g = c.createGain(); var a = A(Math.max(ini, saltar)), b = A(fin);
      bp.frequency.setValueAtTime(f0, a); bp.frequency.linearRampToValueAtTime(f1, b);
      g.gain.setValueAtTime(.0001, a); g.gain.linearRampToValueAtTime(pico, a + (b - a) * .45); g.gain.linearRampToValueAtTime(.0001, b);
      s.connect(bp); bp.connect(g); g.connect(maestro); s.start(a); s.stop(b + .1);
    }
    ruido(0, 3.9, 320, 1100, .8, .22);                // viento que crece y pasa
    ruido(T_IMPACTO - .02, T_IMPACTO + 1.4, 3200, 1800, 1.2, .16); // hojas al estallar
    ruido(7.4, 9.8, 500, 380, .6, .05);               // brisa final

    function tono(f, ini, dur, o) {
      o = o || {}; if (ini + dur <= saltar) return;
      var os = c.createOscillator(), g = c.createGain(), lp = c.createBiquadFilter(); lp.type = 'lowpass'; lp.frequency.value = o.corte || 2600;
      var a = A(Math.max(ini, saltar)), pico = o.gain === undefined ? .2 : o.gain, at = o.ataque || .02;
      os.type = o.tipo || 'sine'; os.frequency.setValueAtTime(f, a); if (o.glide) os.frequency.exponentialRampToValueAtTime(o.glide, a + dur);
      if (o.det) os.detune.value = o.det;
      g.gain.setValueAtTime(.0001, a); g.gain.exponentialRampToValueAtTime(pico, a + at); g.gain.setValueAtTime(pico, a + Math.max(at, dur - (o.rel || .3)));
      g.gain.exponentialRampToValueAtTime(.0001, a + dur);
      os.connect(lp); lp.connect(g); g.connect(maestro); if (o.eco) { var ge = c.createGain(); ge.gain.value = o.eco; g.connect(ge); ge.connect(eco); }
      os.start(a); os.stop(a + dur + .05);
    }
    function campana(f, ini, dur, gain) { tono(f, ini, dur, { gain: gain, ataque: .008, rel: dur * .9, eco: .7 }); tono(f * 2.01, ini, dur * .6, { gain: gain * .3, ataque: .008, rel: dur * .5 }); tono(f * 3.02, ini, dur * .35, { gain: gain * .12, ataque: .008, rel: dur * .3 }); }

    // colchon armonico (Re mayor con novena): entra lento, crece al aparecer el logo
    [[146.83, .07], [220, .07], [293.66, .06], [369.99, .05], [440, .035]].forEach(function (n, i) {
      tono(n[0], 1.0 + i * .25, 9.1 - i * .25, { tipo: 'triangle', gain: n[1], ataque: 2.4, rel: 2.2, corte: 1100, det: (i % 2 ? 5 : -5) });
    });
    tono(73.42, T_IMPACTO - .1, 6.4, { tipo: 'sine', gain: .12, ataque: .6, rel: 2 }); // bajo

    // gota: "plin" en el impacto y un golpe grave suave
    if (ok(T_IMPACTO - .02)) {
      tono(1500, T_IMPACTO, .16, { gain: .3, ataque: .004, glide: 520, rel: .12, eco: .8 });
      tono(260, T_IMPACTO, .5, { gain: .35, ataque: .006, glide: 120, rel: .45 });
      tono(2100, T_GOTA + .02, .06, { gain: .06, ataque: .003, glide: 3200 }); // brillo de la caida
    }
    // arpegio de pentatonica al crecer la planta
    [587.33, 739.99, 880, 1108.73, 1318.51, 1760].forEach(function (f, i) { campana(f, T_IMPACTO + .3 + i * .22, 1.9, .085); });
    // acorde final luminoso al aparecer el nombre
    [293.66, 369.99, 440, 554.37, 659.25].forEach(function (f, i) { campana(f, T_TEXTO + i * .06, 3.2, .07); });
    // cierre
    campana(880, T_SUB + .4, 2.2, .05); campana(1318.51, T_SUB + .6, 2.4, .04);
    // fundido de la mezcla
    var fa = A(T_FADE - .4); maestro.gain.setValueAtTime(.9, fa); maestro.gain.linearRampToValueAtTime(0, A(DUR));
    return maestro;
  }

  /* ================= REPRODUCCION EN VIVO ================= */
  var Intro = { DUR: DUR, Escena: Escena, construirAudio: construirAudio, activa: false };

  function cargarLogo() {
    return new Promise(function (ok) { var im = new Image(); im.onload = function () { ok(im); }; im.onerror = function () { ok(null); }; im.src = base + '/images/logo-dark.svg'; });
  }
  function fuentes() {
    if (!d.fonts || !d.fonts.load) return Promise.resolve();
    return Promise.race([Promise.all([d.fonts.load('600 64px "Bricolage Grotesque"'), d.fonts.load('400 20px "Figtree"')]), new Promise(function (r) { setTimeout(r, 1800); })]);
  }

  Intro.reproducir = function () {
    var el = d.getElementById('intro'); if (!el || Intro.activa) return;
    Intro.activa = true;
    var cv = el.querySelector('canvas'), hint = d.getElementById('intro-hint'), saltar = d.getElementById('intro-saltar');
    Promise.all([cargarLogo(), fuentes()]).then(function (r) {
      el.classList.add('on'); d.documentElement.style.overflow = 'hidden';
      var dpr = Math.min(w.devicePixelRatio || 1, 2);
      var esc = new Escena(cv, { logo: r[0], w: w.innerWidth, h: w.innerHeight, dpr: dpr, semilla: 11 });
      var reducir = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
      var inicio = performance.now(), fin = false, acc = 0, ultimo = inicio, audio = null, ctxA = null, audioDesde = null;
      var quiereSonido = w.MKSound ? MKSound.activo : false;

      function arrancarAudio(desde) {
        if (!quiereSonido || audio || !w.MKSound) return;
        ctxA = MKSound.contexto(); if (!ctxA || ctxA.state !== 'running') return;
        var g = ctxA.createGain(); g.gain.value = Math.max(.05, MKSound.volumen) * 1.15; g.connect(ctxA.destination);
        audio = construirAudio(ctxA, g, ctxA.currentTime + .05, desde); audioDesde = desde;
        if (hint) hint.hidden = true;
      }
      // Si el navegador bloquea el audio, se pide un toque y se sigue desde donde va la imagen.
      if (quiereSonido) {
        if (w.MKSound) MKSound.desbloquear();
        setTimeout(function () {
          if (!audio) { arrancarAudio(0); }
          if (!audio && hint) hint.hidden = false;
        }, 60);
        var gesto = function () {
          if (fin) return;
          MKSound.desbloquear();
          setTimeout(function () { if (!audio) arrancarAudio(Math.min(DUR - 1, (performance.now() - inicio) / 1000)); }, 30);
        };
        ['pointerdown', 'keydown', 'touchstart'].forEach(function (ev) { el.addEventListener(ev, gesto, { once: true, passive: true }); });
      }

      function terminar() {
        if (fin) return; fin = true;
        el.style.transition = 'opacity .5s'; el.style.opacity = '0';
        setTimeout(function () { el.classList.remove('on'); el.style.opacity = ''; el.style.transition = ''; d.documentElement.style.overflow = ''; Intro.activa = false; d.dispatchEvent(new CustomEvent('mk:intro-fin')); }, 520);
        if (audio) { try { audio.gain.cancelScheduledValues(0); audio.gain.setTargetAtTime(0, audio.context.currentTime, .12); } catch (e) {} }
      }
      saltar.onclick = terminar;
      d.addEventListener('keydown', function esc_(e) { if (e.key === 'Escape' && !fin) { terminar(); d.removeEventListener('keydown', esc_); } });
      w.addEventListener('resize', function () { esc.tamano(w.innerWidth, w.innerHeight, dpr); });

      if (reducir) { esc.t = 7.8; esc.paso(0); esc.dibujar(); setTimeout(terminar, 1800); return; }

      (function bucle(ahora) {
        if (fin) return;
        var dt = Math.min(.1, (ahora - ultimo) / 1000); ultimo = ahora; acc += dt;
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
    d.addEventListener('click', function (e) { if (e.target.closest('[data-intro-play]')) { e.preventDefault(); Intro.reproducir(); } });
    if (el.getAttribute('data-auto') === '1') Intro.reproducir();
  });
})(window, document);
