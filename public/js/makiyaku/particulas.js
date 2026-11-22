/* Hojas flotantes (canvas 2D). Ligero: pausa si la pestana esta oculta, respeta prefers-reduced-motion. */
(function (w) {
  'use strict';
  var TAU = Math.PI * 2;
  var reduce = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Hoja: elipse puntiaguda con nervadura central
  var LEAF = new Path2D('M0,-1 C.72,-.46 .72,.46 0,1 C-.72,.46 -.72,-.46 0,-1 Z');
  var VEIN = new Path2D('M0,-.85 L0,.85');

  var PALETAS = {
    claro: ['#2f7d5c', '#5fa57d', '#8cc97a', '#a3cc9b', '#c99a3d'],
    oscuro: ['#8cc97a', '#6fd1a8', '#c9e6a0', '#a3cc9b', '#e3ab3a']
  };

  function Hojas(canvas, opts) {
    this.c = canvas;
    this.ctx = canvas.getContext('2d');
    this.o = Object.assign({ cantidad: 0, densidad: 1 / 70000, max: 40, alpha: .3, velocidad: 1, tamano: [10, 26], paleta: 'claro', viento: 14 }, opts || {});
    this.hojas = [];
    this.t = 0;
    this.corriendo = false;
    this.dpr = Math.min(w.devicePixelRatio || 1, 2);
    var self = this;
    this._resize = function () { self.resize(); };
    w.addEventListener('resize', this._resize);
    this._vis = function () { document.hidden ? self.parar() : self.iniciar(); };
    document.addEventListener('visibilitychange', this._vis);
    this.resize();
  }

  Hojas.prototype.resize = function () {
    var r = this.c.getBoundingClientRect();
    this.w = Math.max(1, r.width); this.h = Math.max(1, r.height);
    this.c.width = Math.round(this.w * this.dpr); this.c.height = Math.round(this.h * this.dpr);
    this.ctx.setTransform(this.dpr, 0, 0, this.dpr, 0, 0);
    var n = this.o.cantidad || Math.min(this.o.max, Math.max(6, Math.round(this.w * this.h * this.o.densidad)));
    while (this.hojas.length < n) this.hojas.push(this.nueva(true));
    this.hojas.length = n;
    if (reduce) this.pintar();
  };

  Hojas.prototype.nueva = function (inicial) {
    var o = this.o, pal = PALETAS[o.paleta] || PALETAS.claro;
    var s = o.tamano[0] + Math.random() * (o.tamano[1] - o.tamano[0]);
    return {
      x: Math.random() * this.w,
      y: inicial ? Math.random() * this.h : -40 - Math.random() * 80,
      s: s, color: pal[(Math.random() * pal.length) | 0],
      vy: (14 + Math.random() * 26) * o.velocidad * (s / 18),
      amp: 14 + Math.random() * 38, f: .25 + Math.random() * .5, ph: Math.random() * TAU,
      rot: Math.random() * TAU, vr: (Math.random() - .5) * 1.1,
      flip: Math.random() * TAU, vf: .6 + Math.random() * 1.4,
      a: o.alpha * (.45 + Math.random() * .55)
    };
  };

  Hojas.prototype.paso = function (dt) {
    this.t += dt;
    var o = this.o;
    for (var i = 0; i < this.hojas.length; i++) {
      var h = this.hojas[i];
      h.y += h.vy * dt;
      h.x += (Math.cos(this.t * h.f + h.ph) * h.amp * h.f + o.viento * o.velocidad) * dt;
      h.rot += h.vr * dt; h.flip += h.vf * dt;
      if (h.y > this.h + 40 || h.x > this.w + 60) {
        var n = this.nueva(false);
        if (h.x > this.w + 60) { n.x = -30; n.y = Math.random() * this.h; }
        this.hojas[i] = n;
      }
    }
  };

  Hojas.prototype.pintar = function () {
    var ctx = this.ctx;
    ctx.clearRect(0, 0, this.w, this.h);
    for (var i = 0; i < this.hojas.length; i++) {
      var h = this.hojas[i];
      ctx.save();
      ctx.translate(h.x, h.y);
      ctx.rotate(h.rot);
      ctx.scale(h.s * (.35 + .65 * Math.abs(Math.cos(h.flip))), h.s * 1.6);
      ctx.globalAlpha = h.a;
      ctx.fillStyle = h.color;
      ctx.fill(LEAF);
      ctx.globalAlpha = h.a * .7;
      ctx.lineWidth = .06; ctx.strokeStyle = 'rgba(255,255,255,.75)';
      ctx.stroke(VEIN);
      ctx.restore();
    }
  };

  Hojas.prototype.iniciar = function () {
    if (reduce || this.corriendo) return;
    this.corriendo = true;
    var self = this, ant = performance.now();
    (function bucle(ahora) {
      if (!self.corriendo) return;
      var dt = Math.min(.05, (ahora - ant) / 1000); ant = ahora;
      self.paso(dt); self.pintar();
      self.raf = requestAnimationFrame(bucle);
    })(ant);
  };
  Hojas.prototype.parar = function () { this.corriendo = false; cancelAnimationFrame(this.raf); };
  Hojas.prototype.destruir = function () { this.parar(); w.removeEventListener('resize', this._resize); document.removeEventListener('visibilitychange', this._vis); };

  w.MKLeaves = {
    crear: function (canvas, opts) { var h = new Hojas(canvas, opts); h.iniciar(); return h; },
    PALETAS: PALETAS, LEAF: LEAF, VEIN: VEIN
  };

  // Hojas de fondo de la aplicacion y de los paneles oscuros (login, portada)
  document.addEventListener('DOMContentLoaded', function () {
    var oscuro = function () { return document.documentElement.getAttribute('data-theme') === 'dark'; };
    var cv = document.getElementById('hojas');
    var ocultas = (window.MK_CFG && MK_CFG.prefs && MK_CFG.prefs.hojas === false);
    if (cv && ocultas) cv.style.display = 'none';
    if (cv && !ocultas) {
      var h = MKLeaves.crear(cv, { densidad: 1 / 90000, max: 18, alpha: oscuro() ? .22 : .3, paleta: oscuro() ? 'oscuro' : 'claro', velocidad: .8, tamano: [10, 22] });
      new MutationObserver(function () {
        h.o.paleta = oscuro() ? 'oscuro' : 'claro'; h.o.alpha = oscuro() ? .22 : .3;
        h.hojas.forEach(function (x) { x.color = PALETAS[h.o.paleta][(Math.random() * 5) | 0]; });
      }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    }
    [['hojas-auth', { cantidad: 34, alpha: .5, paleta: 'oscuro', tamano: [14, 34], velocidad: 1 }],
     ['hojas-hero', { cantidad: window.innerWidth < 700 ? 22 : 46, alpha: .4, paleta: 'oscuro', tamano: [14, 40], velocidad: 1.05 }]].forEach(function (c) {
      var e = document.getElementById(c[0]); if (e) MKLeaves.crear(e, c[1]);
    });
  });
})(window);
