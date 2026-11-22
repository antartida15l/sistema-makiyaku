/* Sonidos de la aplicacion sintetizados con WebAudio (sin archivos de audio). */
(function (w) {
  'use strict';
  var ctx = null, master = null;
  var estado = { activo: true, volumen: .6 };
  try {
    var a = localStorage.getItem('mk-sonido'); if (a !== null) estado.activo = a === '1';
    var v = localStorage.getItem('mk-volumen'); if (v !== null) estado.volumen = Math.max(0, Math.min(1, parseFloat(v)));
  } catch (e) {}

  function asegurar() {
    if (!ctx) {
      var AC = w.AudioContext || w.webkitAudioContext; if (!AC) return null;
      ctx = new AC();
      master = ctx.createGain();
      var comp = ctx.createDynamicsCompressor();
      master.connect(comp); comp.connect(ctx.destination);
    }
    if (ctx.state === 'suspended') ctx.resume();
    master.gain.value = estado.volumen;
    return ctx;
  }

  // nota con envolvente suave. tipo: sine | triangle | square | sawtooth. bell = agrega armonicos
  function nota(f, t0, dur, o) {
    o = o || {};
    var c = ctx, g = c.createGain(), osc = c.createOscillator(), lp = c.createBiquadFilter();
    osc.type = o.tipo || 'sine'; osc.frequency.setValueAtTime(f, t0);
    if (o.glide) osc.frequency.exponentialRampToValueAtTime(o.glide, t0 + dur);
    lp.type = 'lowpass'; lp.frequency.value = o.corte || 5200;
    var pico = (o.gain === undefined ? .3 : o.gain);
    g.gain.setValueAtTime(0.0001, t0);
    g.gain.exponentialRampToValueAtTime(pico, t0 + (o.ataque || .012));
    g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
    osc.connect(lp); lp.connect(g); g.connect(master);
    osc.start(t0); osc.stop(t0 + dur + .05);
    if (o.campana) {
      [2.01, 3.02].forEach(function (m, i) {
        var o2 = c.createOscillator(), g2 = c.createGain();
        o2.type = 'sine'; o2.frequency.setValueAtTime(f * m, t0);
        g2.gain.setValueAtTime(0.0001, t0); g2.gain.exponentialRampToValueAtTime(pico * (.32 / (i + 1)), t0 + .01);
        g2.gain.exponentialRampToValueAtTime(0.0001, t0 + dur * .6);
        o2.connect(g2); g2.connect(master); o2.start(t0); o2.stop(t0 + dur);
      });
    }
  }

  var MOTIVOS = {
    // informativa: una campana suave
    info: function (t) { nota(784, t, .9, { campana: true, gain: .22 }); },
    // media: dos notas ascendentes
    medio: function (t) { nota(659, t, .6, { campana: true, gain: .26 }); nota(880, t + .16, .8, { campana: true, gain: .26 }); },
    // alta: tres golpes de atencion
    alto: function (t) { [0, .2, .4].forEach(function (d, i) { nota(i === 2 ? 740 : 587, t + d, .32, { tipo: 'triangle', gain: .36, corte: 3200 }); }); },
    // critica: alarma firme (dos tonos alternados, repetida), no estridente
    critico: function (t) {
      for (var r = 0; r < 3; r++) {
        nota(880, t + r * .62, .22, { tipo: 'sawtooth', gain: .26, corte: 2400 });
        nota(659, t + r * .62 + .26, .26, { tipo: 'sawtooth', gain: .26, corte: 2400 });
      }
      nota(110, t, .5, { tipo: 'sine', gain: .35 });
    },
    ok: function (t) { nota(523, t, .5, { campana: true, gain: .22 }); nota(784, t + .1, .5, { campana: true, gain: .22 }); nota(1047, t + .2, .8, { campana: true, gain: .22 }); },
    error: function (t) { nota(220, t, .4, { tipo: 'triangle', gain: .3, glide: 165 }); },
    clic: function (t) { nota(1200, t, .05, { tipo: 'sine', gain: .1 }); }
  };

  w.MKSound = {
    get activo() { return estado.activo; },
    get volumen() { return estado.volumen; },
    fijar: function (activo, volumen) {
      if (typeof activo === 'boolean') estado.activo = activo;
      if (typeof volumen === 'number') estado.volumen = Math.max(0, Math.min(1, volumen));
      try { localStorage.setItem('mk-sonido', estado.activo ? '1' : '0'); localStorage.setItem('mk-volumen', String(estado.volumen)); } catch (e) {}
      document.dispatchEvent(new CustomEvent('mk:sonido', { detail: estado }));
    },
    // El navegador exige un gesto del usuario antes de reproducir audio.
    desbloquear: function () { asegurar(); },
    tocar: function (tipo, forzar) {
      if (!estado.activo && !forzar) return false;
      var c = asegurar(); if (!c || c.state !== 'running') return false;
      (MOTIVOS[tipo] || MOTIVOS.info)(c.currentTime + .02);
      return true;
    },
    tipos: Object.keys(MOTIVOS),
    contexto: function () { return asegurar(); }
  };

  ['pointerdown', 'keydown', 'touchstart'].forEach(function (ev) {
    w.addEventListener(ev, function once() { asegurar(); w.removeEventListener(ev, once, true); }, { capture: true, passive: true });
  });
})(window);
