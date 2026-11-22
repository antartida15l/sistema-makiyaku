@extends('layouts.app')
@section('title', 'Alertas')

@section('content')
@php
    $niveles = ['critico' => ['Críticas', 'Actúa ahora'], 'alto' => ['Altas', 'Atiende hoy'], 'medio' => ['Medias', 'Prepárate'], 'info' => ['Informativas', 'Solo para saber']];
    $temas = ['riego' => 'Riego', 'helada' => 'Helada', 'calor' => 'Calor', 'lluvia' => 'Lluvia', 'ciclo' => 'Ciclo del cultivo', 'hidrogel' => 'Hidrogel'];
    $hay = collect($filtros)->filter()->isNotEmpty();
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
@endphp

<header class="page-head">
    <div><h1>Alertas</h1><p>@if($resumen['total']){{ $resumen['total'] }} {{ $resumen['total'] === 1 ? 'alerta activa' : 'alertas activas' }}@if($resumen['criticas']), {{ $resumen['criticas'] }} {{ $resumen['criticas'] === 1 ? 'crítica' : 'críticas' }}@endif. @else Todo en orden. @endif Se actualizan solas cada 30 minutos con el clima de tus parcelas.</p></div>
    <div class="head-actions">
        <a href="{{ route('alertas.archivadas') }}" class="btn btn-ghost"><x-icono name="archivo" :size="18" />Archivadas @if($archivadas)({{ $archivadas }})@endif</a>
        <form method="POST" action="{{ route('alertas.refrescar') }}">@csrf<button class="btn" type="submit" data-ocupado="Actualizando..."><x-icono name="actualizar" :size="18" />Actualizar ahora</button></form>
        @if($resumen['total'])<button class="btn" type="button" id="btn-todas"><x-icono name="ok" :size="18" />Archivar todas</button>@endif
    </div>
</header>

<div class="grid g-main">
<div class="stack" style="min-width:0">
    <form method="GET" class="row" id="filtros" role="search" aria-label="Filtros de alertas">
        <div class="segmented" role="group" aria-label="Nivel">
            <a href="{{ route('alertas.index', array_filter(array_merge($filtros, ['nivel' => null]))) }}" @if(empty($filtros['nivel'])) aria-current="true" @endif>Todas</a>
            @foreach($niveles as $k => [$txt])<a href="{{ route('alertas.index', array_filter(array_merge($filtros, ['nivel' => $k]))) }}" @if(($filtros['nivel'] ?? '') === $k) aria-current="true" @endif>{{ $txt }}@if($resumen[['critico'=>'criticas','alto'=>'altas','medio'=>'medias','info'=>'info'][$k]] ?? 0) <span class="t-meta">{{ $resumen[['critico'=>'criticas','alto'=>'altas','medio'=>'medias','info'=>'info'][$k]] }}</span>@endif</a>@endforeach
        </div>
        @if(!empty($filtros['nivel']))<input type="hidden" name="nivel" value="{{ $filtros['nivel'] }}">@endif
        <label class="sr-only" for="f-grupo">Tema</label>
        <select id="f-grupo" name="grupo" class="select" style="width:auto;min-height:40px" onchange="this.form.submit()"><option value="">Todos los temas</option>@foreach($temas as $k => $t)<option value="{{ $k }}" @selected(($filtros['grupo'] ?? '') === $k)>{{ $t }}</option>@endforeach</select>
        <label class="sr-only" for="f-parcela">Parcela</label>
        <select id="f-parcela" name="parcela" class="select" style="width:auto;min-height:40px" onchange="this.form.submit()"><option value="">Todas las parcelas</option>@foreach($parcelas as $p)<option value="{{ $p->id }}" @selected(($filtros['parcela'] ?? '') == $p->id)>{{ $p->nombre }}</option>@endforeach</select>
        @if($hay)<a href="{{ route('alertas.index') }}" class="btn btn-ghost btn-sm">Limpiar filtros</a>@endif
    </form>

    <div id="feed" class="stack">
    @forelse(['critico', 'alto', 'medio', 'info'] as $nv)
        @if(($porNivel[$nv] ?? collect())->count())
        <section aria-label="{{ $niveles[$nv][0] }}" class="stack" data-nivel="{{ $nv }}">
            <h2 style="font-size:var(--t-lg)">{{ $niveles[$nv][0] }} <span class="t-sec muted" style="font-family:var(--font-ui);font-weight:var(--w-regular)">{{ $niveles[$nv][1] }}</span></h2>
            <div class="panel divide">
            @foreach($porNivel[$nv] as $al)
                @php $d = $al->datos_adicionales ?? []; @endphp
                <article class="item lvl lvl-{{ $al->nivel }}" id="alerta-{{ $al->id }}" style="padding:20px 24px">
                    <span class="ic" style="width:44px;height:44px"><x-icono :name="$al->icono" :size="22" /></span>
                    <div class="body">
                        <div class="row" style="gap:8px"><h3 style="font-size:var(--t-body);font-family:var(--font-ui)">{{ $al->titulo }}</h3><span class="tag lvl lvl-{{ $al->nivel }}">{{ $al->etiqueta_nivel }}</span><span class="t-meta">{{ ($al->actualizada_en ?? $al->created_at)->diffForHumans() }}</span></div>
                        <a href="{{ route('parcelas.show', $al->parcela_id) }}" class="t-sec">{{ $al->parcela->nombre ?? 'Parcela' }}@if($al->parcela?->cultivo), {{ $al->parcela->cultivo->nombreCultivo }}@endif</a>
                        <p style="margin-top:8px;max-width:72ch">{{ $al->mensaje }}</p>
                        @if(isset($d['litros_recomendados']) && $d['litros_recomendados'] > 0)
                            <div class="row" style="gap:24px;margin-top:12px">
                                <div class="figure"><span class="l">Agua recomendada</span><span class="num" style="font-size:var(--t-lg);font-weight:var(--w-semibold)">{{ $d['litros_recomendados'] >= 1000 ? number_format($d['litros_recomendados'] / 1000, 1, ',', '.') . ' m³' : $fmt($d['litros_recomendados']) . ' L' }}</span></div>
                                <div class="figure"><span class="l">Por m²</span><span class="num" style="font-size:var(--t-lg);font-weight:var(--w-semibold)">{{ number_format($d['litros_por_m2'] ?? 0, 1, ',', '.') }} L</span></div>
                                @if(($d['baldes'] ?? 0) > 0 && $d['litros_recomendados'] < 1000)<div class="figure"><span class="l">Equivale a</span><span class="num" style="font-size:var(--t-lg);font-weight:var(--w-semibold)">{{ rtrim(rtrim(number_format($d['baldes'], 1, ',', ''), '0'), ',') }} baldes</span></div>@endif
                                @if(isset($d['dias_sin_regar']))<div class="figure"><span class="l">Sin regar</span><span class="num" style="font-size:var(--t-lg);font-weight:var(--w-semibold)">{{ $d['dias_sin_regar'] }} días</span></div>@endif
                            </div>
                        @endif
                        <div class="row" style="margin-top:16px">
                            <a href="{{ route('parcelas.show', $al->parcela_id) }}" class="btn btn-sm {{ $al->grupo === 'riego' ? 'btn-primary' : '' }}">{{ $al->grupo === 'riego' ? 'Registrar riego' : 'Ver parcela' }}</a>
                            <button type="button" class="btn btn-sm btn-ghost" data-archivar="{{ $al->id }}" data-url="{{ route('alertas.marcar-leida', $al) }}">Archivar</button>
                        </div>
                    </div>
                </article>
            @endforeach
            </div>
        </section>
        @endif
    @empty
    @endforelse
    @if($alertas->isEmpty())
        <section class="panel">
            @if($hay)
                <div class="empty"><x-icono name="buscar" :size="40" :stroke="1.5" /><h3>Ninguna alerta coincide</h3><p>Prueba con otro tema o parcela.</p><a href="{{ route('alertas.index') }}" class="btn">Limpiar filtros</a></div>
            @else
                <div class="empty"><x-icono name="ok" :size="40" :stroke="1.5" /><h3>No hay alertas activas</h3><p>Tus parcelas están en orden. Te avisaremos cuando haya que regar, proteger del frío o prepararse para la lluvia.</p>@if($parcelas->isEmpty())<a href="{{ route('parcelas.create') }}" class="btn btn-primary">Crear parcela</a>@endif</div>
            @endif
        </section>
    @endif
    </div>
</div>

<aside class="stack-lg" style="min-width:0">
    <section class="panel" aria-labelledby="h-son">
        <div class="panel-head"><h2 id="h-son">Sonido de alertas</h2></div>
        <div class="panel-body stack">
            <label class="switch"><input type="checkbox" id="sw-sonido" checked><span class="track"></span><span>Avisarme con sonido</span></label>
            <div class="field"><label for="vol">Volumen</label><input id="vol" type="range" min="0" max="100" step="5" class="range"></div>
            <div><p class="label" style="margin-bottom:8px">Probar sonidos</p>
                <div class="row" style="gap:8px">
                    <button type="button" class="btn btn-sm" data-probar="info">Informativa</button><button type="button" class="btn btn-sm" data-probar="medio">Media</button>
                    <button type="button" class="btn btn-sm" data-probar="alto">Alta</button><button type="button" class="btn btn-sm" data-probar="critico">Crítica</button>
                </div></div>
            <p class="t-meta">Cada nivel suena distinto. Las alertas críticas se repiten cada 10 minutos hasta que las leas. Tu navegador necesita que toques la pantalla una vez para poder reproducir sonido.</p>
        </div>
    </section>
    <section class="panel" aria-labelledby="h-niv">
        <div class="panel-head"><h2 id="h-niv">Cómo leer los niveles</h2></div>
        <div class="panel-body stack" style="gap:12px">
            @foreach(['critico' => 'El cultivo está en riesgo. Actúa ahora.', 'alto' => 'Conviene actuar hoy: regar o proteger.', 'medio' => 'Ve preparándote para los próximos días.', 'info' => 'Información útil, sin urgencia.'] as $k => $t)
                <div class="row" style="flex-wrap:nowrap;align-items:flex-start"><span class="tag lvl lvl-{{ $k }}" style="min-width:92px">{{ $niveles[$k][0] }}</span><span class="t-sec muted">{{ $t }}</span></div>
            @endforeach
            <p class="t-meta">Hay una sola alerta por tema y parcela. Si la situación empeora, sube de nivel y vuelve a sonar.</p>
        </div>
    </section>
</aside>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var csrf = document.querySelector('meta[name=csrf-token]').content;
  function archivar(url, el) {
    return fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }
  document.querySelectorAll('[data-archivar]').forEach(function (b) {
    b.addEventListener('click', function () {
      b.disabled = true;
      archivar(b.dataset.url).then(function (j) {
        if (!j.success) { b.disabled = false; MK.toast({ tipo: 'critico', titulo: 'No se pudo archivar', mensaje: j.message || '' }); return; }
        var a = document.getElementById('alerta-' + b.dataset.archivar), sec = a.closest('section'); a.remove();
        if (sec && !sec.querySelector('article')) sec.remove();
        MK.toast({ tipo: 'ok', titulo: 'Alerta archivada', mensaje: 'La encuentras en Archivadas.', ms: 4000 });
        if (!document.querySelector('#feed article')) location.reload();
      });
    });
  });
  var todas = document.getElementById('btn-todas');
  if (todas) todas.addEventListener('click', function () {
    MK.confirmar('¿Archivar todas las alertas activas?', 'Se moverán a Archivadas. Volverán a aparecer solo si la situación empeora.', 'Archivar todas').then(function (si) {
      if (!si) return;
      fetch(@json(route('alertas.marcar-todas-leidas')), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function () { location.reload(); });
    });
  });

  /* sonido */
  var sw = document.getElementById('sw-sonido'), vol = document.getElementById('vol');
  function leer() { sw.checked = MKSound.activo; vol.value = Math.round(MKSound.volumen * 100); }
  leer();
  sw.addEventListener('change', function () { MKSound.fijar(sw.checked); MKSound.desbloquear(); MK.guardarPrefs({ sonido: sw.checked, volumen: MKSound.volumen }); });
  vol.addEventListener('input', function () { MKSound.fijar(undefined, vol.value / 100); MK.guardarPrefs({ volumen: vol.value / 100 }); });
  document.querySelectorAll('[data-probar]').forEach(function (b) { b.addEventListener('click', function () { MKSound.desbloquear(); setTimeout(function () { if (!MKSound.tocar(b.dataset.probar, true)) MK.toast({ tipo: 'medio', titulo: 'Toca la pantalla otra vez', mensaje: 'El navegador necesita un toque para activar el sonido.' }); }, 40); }); });
});
</script>
@endpush
