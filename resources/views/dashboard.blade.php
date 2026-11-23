@extends('layouts.app')
@section('title', 'Panel')

@section('content')
@php
    $nombre = explode(' ', trim(auth()->user()->name))[0];
    $fmtL = fn ($l) => $l >= 10000 ? number_format($l / 1000, 1, ',', '.') . ' m³' : number_format($l, 0, ',', '.') . ' L';
    $nivelTxt = ['critico' => 'Urgente', 'alto' => 'Hoy', 'medio' => 'Pronto', 'info' => 'Aviso', 'ok' => 'Al día'];
@endphp

<header class="page-head">
    <div>
        <h1>{{ $saludo }}, {{ $nombre }}</h1>
        <p>{{ now()->translatedFormat('l j \d\e F') }}. @if($kpis['riego_hoy'] > 0) Tienes {{ $kpis['riego_hoy'] }} {{ $kpis['riego_hoy'] === 1 ? 'parcela que necesita' : 'parcelas que necesitan' }} riego. @elseif($kpis['parcelas'] > 0) Ninguna parcela necesita riego por ahora. @else Empieza registrando tu primera parcela. @endif</p>
    </div>
    <div class="head-actions">
        @if($kpis['parcelas'] > 0)
        <form method="POST" action="{{ route('alertas.refrescar') }}">@csrf
            <button class="btn" type="submit" data-ocupado="Actualizando..."><x-icono name="actualizar" :size="18" />Actualizar clima y alertas</button>
        </form>
        @endif
        <a href="{{ route('parcelas.create') }}" class="btn btn-primary"><x-icono name="mas" :size="18" />Nueva parcela</a>
    </div>
</header>

@if($kpis['parcelas'] === 0)
    <section class="panel">
        <div class="empty">
            <x-icono name="parcela" :size="40" :stroke="1.5" />
            <h3>Aún no tienes parcelas</h3>
            <p>Registra una parcela con su cultivo y ubicación. Con eso Makiyaku empieza a traer el clima y a calcular cuándo regar.</p>
            <a href="{{ route('parcelas.create') }}" class="btn btn-primary">Crear parcela</a>
        </div>
    </section>
@else
<div class="stack-lg">
    <section class="kpis" aria-label="Resumen">
        <div class="figure"><span class="l">Parcelas activas</span><span class="v">{{ $kpis['parcelas'] }}</span><span class="d">{{ number_format($kpis['area'] / 10000, 2, ',', '.') }} ha en total</span></div>
        <div class="figure"><span class="l">Necesitan riego</span><span class="v">{{ $kpis['riego_hoy'] }}</span><span class="d">{{ $kpis['riego_hoy'] ? 'Mira el registro de abajo' : 'Todo al día' }}</span></div>
        <div class="figure"><span class="l">Alertas activas</span><span class="v">{{ $kpis['alertas'] }}</span><span class="d">{{ $kpis['criticas'] }} {{ $kpis['criticas'] === 1 ? 'crítica' : 'críticas' }}</span></div>
        <div class="figure"><span class="l">Agua en 14 días</span><span class="v">{{ $fmtL($kpis['agua14']) }}</span>
            <span class="d">@if($kpis['agua_cambio'] === null) Sin riegos en el periodo anterior @else {{ $kpis['agua_cambio'] > 0 ? 'Subió' : ($kpis['agua_cambio'] < 0 ? 'Bajó' : 'Igual que') }} {{ $kpis['agua_cambio'] !== 0 ? abs($kpis['agua_cambio']) . '% ' : '' }}frente a los 14 días anteriores @endif</span></div>
    </section>

    <div class="grid g-main">
        <div class="stack-lg" style="min-width:0">
            {{-- Registro del campo --}}
            <section aria-labelledby="h-campo">
                <div class="row between" style="margin-bottom:12px"><h2 id="h-campo">Hoy en el campo</h2><a href="{{ route('parcelas.index') }}" class="t-sec">Ver todas las parcelas</a></div>
                <div class="ledger">
                    <div class="ledger-head"><span></span><span>Parcela</span><span>Riego</span><span>Clima ahora</span><span></span></div>
                    @foreach($registro as $p)
                        @php
                            $a = $p->alertas->where('grupo', 'riego')->first();
                            $tono = $p->tono_riego; $c = $p->climaActual;
                            $otras = $p->alertas->where('grupo', '!=', 'riego');
                        @endphp
                        <a href="{{ route('parcelas.show', $p) }}" class="ledger-row">
                            <x-gota :nivel="$p->nivel_agua" :tono="$tono" />
                            <div><div class="nm">{{ $p->nombre }}</div><div class="sub">{{ $p->cultivo?->nombreCultivo ?? 'Sin cultivo' }}, {{ number_format($p->area, 0, ',', '.') }} m²</div></div>
                            <div>
                                <span class="tag lvl lvl-{{ $a ? $a->nivel : 'ok' }}">{{ $a ? ($nivelTxt[$a->nivel] ?? 'Aviso') : 'Al día' }}</span>
                                <div class="sub" style="margin-top:4px">@if($a) {{ $a->titulo }} @elseif($p->proximo_riego_estimado) Próximo riego el {{ $p->proximo_riego_estimado->translatedFormat('j M') }} @else Sin fecha de riego @endif</div>
                            </div>
                            <div>
                                @if($c)<div class="big">{{ number_format($c->temperatura, 0) }}&nbsp;°C</div><div class="sub">{{ $c->descripcion }}@if($c->temp_min_prevista !== null), mín. {{ number_format($c->temp_min_prevista, 0) }}°@endif</div>
                                @else<div class="sub">Sin clima todavía</div>@endif
                            </div>
                            <div class="row" style="gap:4px">
                                @foreach($otras->take(2) as $o)<span class="tag lvl lvl-{{ $o->nivel }} plain" title="{{ $o->titulo }}"><x-icono :name="$o->icono" :size="14" /></span>@endforeach
                                <x-icono name="derecha" :size="18" />
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- Agua --}}
            <section class="panel" aria-labelledby="h-agua">
                <div class="panel-head"><h2 id="h-agua">Agua aplicada, últimos 14 días</h2><a href="{{ route('reportes.ver', ['tipo' => 'agua']) }}" class="t-sec">Ver reporte</a></div>
                <div class="panel-body">
                    @if($kpis['agua14'] > 0)
                        <div id="ch-agua" aria-label="Litros de agua aplicados por día"></div>
                    @else
                        <div class="empty" style="padding:24px 0"><h3>Sin riegos en este periodo</h3><p>Cuando registres un riego en una parcela, aquí verás cuánta agua usaste cada día.</p><a href="{{ route('parcelas.index') }}" class="btn">Elegir una parcela</a></div>
                    @endif
                </div>
            </section>

            <section class="panel" aria-labelledby="h-clima">
                <div class="panel-head">
                    <h2 id="h-clima">Próximas 48 horas</h2>
                    @if($parcelas->filter(fn($p) => $p->tiene_gps)->count())
                    <div class="row"><label for="sel-parcela" class="sr-only">Parcela</label>
                        <select id="sel-parcela" class="select" style="min-height:36px;width:auto">
                            @foreach($parcelas->filter(fn($p) => $p->tiene_gps) as $p)<option value="{{ $p->id }}" @selected($parcelaClima && $parcelaClima->id === $p->id)>{{ $p->nombre }}</option>@endforeach
                        </select>
                        <a href="{{ route('clima.index', ['parcela' => $parcelaClima?->id]) }}" id="lnk-clima" class="btn btn-sm">Clima completo</a>
                    </div>
                    @endif
                </div>
                <div class="panel-body">
                    @if($parcelaClima)
                        
                        <div class="agro" id="agro-dash" style="margin-bottom:20px">
                            <div class="agro-card"><span class="l">ET₀ hoy</span><span class="v" data-a="et0">-</span><span class="d">mm de agua que se evaporan</span></div>
                            <div class="agro-card" style="--k:var(--lima-hondo)"><span class="l">Humedad del suelo</span><span class="v" data-a="suelo">-</span><span class="d">capa de 0 a 9 cm</span></div>
                            <div class="agro-card" style="--k:var(--chart-4)"><span class="l">Radiación solar</span><span class="v" data-a="radiacion">-</span><span class="d">kWh/m² en el día</span></div>
                            <div class="agro-card" style="--k:var(--chart-5)"><span class="l">Prob. de lluvia</span><span class="v" data-a="prob">-</span><span class="d" data-a="lluvia">en 24 horas</span></div>
                        </div>
                        <div id="ch-temp" aria-label="Temperatura prevista"></div>
                        <div id="ch-lluvia" aria-label="Lluvia prevista" style="margin-top:8px"></div>
                        <p id="msg-clima" class="t-sec muted hide"></p>
                    @else
                        <div class="empty" style="padding:24px 0"><h3>Todavía no hay clima</h3><p>Agrega las coordenadas GPS a una parcela para ver su pronóstico aquí.</p></div>
                    @endif
                </div>
            </section>
        </div>

        <aside class="stack-lg" style="min-width:0">
            <section class="panel" aria-labelledby="h-alertas">
                <div class="panel-head"><h2 id="h-alertas">Alertas</h2><a href="{{ route('alertas.index') }}" class="t-sec">Ver todas</a></div>
                @forelse($alertas as $a)
                    <a href="{{ route('alertas.index') }}" class="item lvl lvl-{{ $a->nivel }}" style="color:inherit;border-top:1px solid var(--border)">
                        <span class="ic"><x-icono :name="$a->icono" :size="20" /></span>
                        <span class="body"><span class="title">{{ $a->titulo }}</span><br><span class="t-sec muted">{{ $a->parcela->nombre ?? '' }}</span></span>
                        <span class="tag lvl lvl-{{ $a->nivel }}">{{ $a->etiqueta_nivel }}</span>
                    </a>
                @empty
                    <div class="empty" style="padding:32px 24px"><x-icono name="ok" :size="32" /><h3>Sin alertas</h3><p>Todo está en orden. Te avisaremos si algo cambia.</p></div>
                @endforelse
            </section>

            @if($recomendaciones->count())
            <section class="panel" aria-labelledby="h-reco">
                <div class="panel-head"><h2 id="h-reco">Recomendado para ti</h2><a href="{{ route('recomendaciones.todas') }}" class="t-sec">Ver todas</a></div>
                @foreach($recomendaciones as $r)
                    <div class="item" style="border-top:1px solid var(--border)">
                        <span class="ic"><x-icono :name="$r->icono" :size="20" /></span>
                        <span class="body"><span class="title">{{ $r->titulo }}</span><br><span class="t-sec muted">{{ $r->parcela->nombre ?? '' }}</span></span>
                    </div>
                @endforeach
            </section>
            @endif

            <section class="panel" aria-labelledby="h-cult">
                <div class="panel-head"><h2 id="h-cult">Superficie por cultivo</h2></div>
                <div class="panel-body">
                    <div id="ch-cultivos" aria-label="Superficie por cultivo"></div>
                    <ul class="legend" style="list-style:none;padding:0;margin:16px 0 0;flex-direction:column;gap:8px">
                        @foreach($distribucion as $i => $d)
                            <li class="row between" style="flex-wrap:nowrap"><span><i style="background:var(--chart-{{ ($i % 6) + 1 }})"></i>{{ $d['nombre'] }}</span><span class="num">{{ number_format($d['valor'], 0, ',', '.') }} m²</span></li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section class="panel" aria-labelledby="h-act">
                <div class="panel-head"><h2 id="h-act">Actividad reciente</h2></div>
                <div class="panel-body">
                    @if($actividad->isEmpty())<p class="t-sec muted">Todavía no hay actividad.</p>@else
                    <ul class="timeline">
                        @foreach($actividad as $x)
                            <li class="lvl lvl-{{ $x['nivel'] }}" style="--c:var(--{{ $x['nivel'] === 'ok' ? 'ok' : $x['nivel'] }})"><div style="font-size:var(--t-sec)">{{ $x['texto'] }}</div><div class="t-meta">{{ $x['cuando']->diffForHumans() }}</div></li>
                        @endforeach
                    </ul>@endif
                </div>
            </section>
        </aside>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="{{ asset('js/makiyaku/charts.js') }}?v={{ @filemtime(public_path('js/makiyaku/charts.js')) }}" defer></script>
<script defer>
document.addEventListener('DOMContentLoaded', function () {
  var fL = function (v) { return v >= 10000 ? MKCharts.fnum(v / 1000, 1) + ' m³' : MKCharts.fnum(v, 0) + ' L'; };
  var agua = document.getElementById('ch-agua');
  if (agua) MKCharts.barras(agua, { labels: @json($serieAgua['labels']), series: [{ nombre: 'Agua', datos: @json($serieAgua['datos']), color: 'chart-1' }], alto: 220, fmtY: function (v) { return v >= 1000 ? MKCharts.fnum(v / 1000) + 'k' : MKCharts.fnum(v); }, fmtTip: function (v) { return fL(v); }, unidadY: 'L' });
  var dona = document.getElementById('ch-cultivos');
  if (dona) MKCharts.dona(dona, { items: @json($distribucion->map(fn($d) => ['nombre' => $d['nombre'], 'valor' => $d['valor']])->values()), centro: { valor: '{{ number_format($kpis['area'] / 10000, 1, ',', '.') }} ha', texto: 'sembradas' } });

  var sel = document.getElementById('sel-parcela'); if (!sel) return;
  var rutaP = @json(route('dashboard.pronostico')), rutaC = @json(route('clima.index'));
  function cargar() {
    var id = sel.value, msg = document.getElementById('msg-clima');
    document.getElementById('lnk-clima').href = rutaC + '?parcela=' + id;
    fetch(rutaP + '?parcela_id=' + id, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
      if (!j.success) { msg.textContent = j.message; msg.classList.remove('hide'); return; } msg.classList.add('hide');
      var dd = j.data, ag = j.agro || {}, f1 = function (v) { return v === null || v === undefined ? '-' : MKCharts.fnum(v, 1); };
      var put = function (k, t) { var e = document.querySelector('#agro-dash [data-a="' + k + '"]'); if (e) e.textContent = t; };
      put('et0', f1(ag.et0) + ' mm'); put('suelo', f1(ag.suelo) + '%'); put('radiacion', f1(ag.radiacion)); put('prob', (ag.prob === null || ag.prob === undefined ? '-' : ag.prob) + '%'); put('lluvia', f1(ag.lluvia) + ' mm en 24 horas');
      MKCharts.linea(document.getElementById('ch-temp'), { rangos: [24, 48], labels: dd.labels, titulos: dd.titulos, series: [{ nombre: 'Temperatura', datos: dd.temp, color: 'chart-1', area: true }], alto: 190, unidadY: '°C', fmtY: function (v) { return MKCharts.fnum(v, 0); }, fmtTip: function (v) { return MKCharts.fnum(v, 1) + ' °C'; }, lineas: [{ valor: 0, texto: 'Helada', color: 'medio' }], ahora: 0, maxX: 8 });
      MKCharts.barras(document.getElementById('ch-lluvia'), { rangos: [24, 48], labels: dd.labels, titulos: dd.titulos, series: [{ nombre: 'Lluvia', datos: dd.lluvia, color: 'chart-2' }], linea: { nombre: 'Probabilidad', datos: dd.prob, color: 'chart-3', max: 100, fmt: function (v) { return MKCharts.fnum(v, 0) + '%'; } }, alto: 150, unidadY: 'mm', fmtY: function (v) { return MKCharts.fnum(v, 1); }, fmtTip: function (v) { return MKCharts.fnum(v, 1) + ' mm'; }, maxX: 8 });
      document.dispatchEvent(new Event('mk:redibujar'));
    }).catch(function () { msg.textContent = 'No se pudo cargar el pronóstico.'; msg.classList.remove('hide'); });
  }
  sel.addEventListener('change', cargar); cargar();
});
</script>
@endpush
