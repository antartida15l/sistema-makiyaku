@extends('layouts.app')
@section('title', $titulo)
@section('content')
@php
    $fmt = fn ($n, $d = 0) => number_format($n, $d, ',', '.');
    $agua = fn ($l) => $l >= 10000 ? $fmt($l / 1000, 1) . ' m³' : $fmt($l) . ' L';
    $nombreNivel = ['critico' => 'Crítica', 'alto' => 'Alta', 'medio' => 'Media', 'info' => 'Informativa'];
    $grupos = ['riego' => 'Riego', 'helada' => 'Helada', 'calor' => 'Calor', 'lluvia' => 'Lluvia', 'ciclo' => 'Ciclo', 'hidrogel' => 'Hidrogel'];
    $q = request()->query();
    // secciones segun el tipo
    $R = $reporte;
    $aguaR = $tipo === 'general' ? $R['agua'] : ($tipo === 'agua' ? $R : null);
    $alertasR = $tipo === 'general' ? $R['alertas'] : ($tipo === 'alertas' ? $R : null);
    $climaR = $tipo === 'general' ? $R['clima'] : ($tipo === 'clima' ? $R : null);
    $cicloR = $tipo === 'general' ? $R['ciclo'] : ($tipo === 'ciclo' ? $R : null);
@endphp

<header class="page-head no-print">
    <div><h1>{{ $titulo }}</h1><p>{{ $desde->translatedFormat('j M Y') }} al {{ $hasta->translatedFormat('j M Y') }}, {{ $parcelaFiltro ? $parcelaFiltro->nombre : 'todas las parcelas' }}.</p></div>
    <div class="head-actions">
        <a href="{{ route('reportes.index') }}" class="btn">Cambiar</a>
        <a href="{{ route('reportes.csv', $q) }}" class="btn"><x-icono name="descargar" :size="18" />Descargar CSV</a>
        <button type="button" class="btn btn-primary" onclick="window.print()"><x-icono name="imprimir" :size="18" />Imprimir o guardar PDF</button>
    </div>
</header>

<div class="report-sheet stack-lg">
    <div class="print-only" style="margin-bottom:8px"><div class="row" style="gap:12px"><x-logo :size="48" /><div><strong style="font-family:var(--font-display);font-size:var(--t-xl)">Makiyaku</strong><div class="t-sec">{{ $titulo }}: {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}</div></div></div></div>

    @if($tipo === 'general')
    <section class="kpis" aria-label="Resumen del periodo">
        <div class="figure"><span class="l">Agua aplicada</span><span class="v">{{ $agua($R['agua']['totales']['litros']) }}</span><span class="d">{{ $R['agua']['totales']['riegos'] }} riegos</span></div>
        <div class="figure"><span class="l">Alertas generadas</span><span class="v">{{ $R['alertas']['totales']['total'] }}</span><span class="d">{{ $R['alertas']['totales']['criticas'] }} críticas</span></div>
        <div class="figure"><span class="l">Temperatura media</span><span class="v">{{ $R['clima']['totales']['temp_media'] !== null ? $fmt($R['clima']['totales']['temp_media'], 1) . ' °C' : '-' }}</span><span class="d">{{ $R['clima']['totales']['dias'] }} días con datos</span></div>
        <div class="figure"><span class="l">Recomendaciones</span><span class="v">{{ $R['recomendaciones']['total'] }}</span><span class="d">{{ $R['recomendaciones']['aplicadas'] }} aplicadas</span></div>
    </section>
    @endif

    @if($aguaR)
    <section aria-labelledby="r-agua">
        <h2 id="r-agua">Consumo de agua</h2>
        @if($tipo === 'agua')
        <div class="kpis" style="margin-top:16px"><div class="figure"><span class="l">Total aplicado</span><span class="v">{{ $agua($aguaR['totales']['litros']) }}</span></div><div class="figure"><span class="l">Riegos</span><span class="v">{{ $aguaR['totales']['riegos'] }}</span></div><div class="figure"><span class="l">Promedio por riego</span><span class="v">{{ $agua($aguaR['totales']['promedio']) }}</span></div><div class="figure"><span class="l">Parcelas regadas</span><span class="v">{{ $aguaR['totales']['parcelas'] }}</span></div></div>
        @endif
        @if(array_sum($aguaR['serie']['datos']) > 0)<div id="rc-agua" style="margin-top:16px" aria-label="Litros aplicados por día"></div>@endif
        @if($aguaR['por_parcela']->count())
        <div class="table-frame" style="margin-top:16px"><div class="table-scroll"><table class="tbl"><thead><tr><th>Parcela</th><th>Cultivo</th><th class="r">Superficie</th><th class="r">Riegos</th><th class="r">Agua total</th><th class="r">L por m²</th><th class="r">Promedio por riego</th></tr></thead><tbody>
            @foreach($aguaR['por_parcela'] as $f)<tr><td>{{ $f['parcela'] }}</td><td>{{ $f['cultivo'] }}</td><td class="r">{{ $fmt($f['area']) }} m²</td><td class="r">{{ $f['riegos'] }}</td><td class="r">{{ $fmt($f['litros']) }} L</td><td class="r">{{ $fmt($f['litros_m2'], 1) }}</td><td class="r">{{ $fmt($f['promedio']) }} L</td></tr>@endforeach
        </tbody></table></div></div>
        @else<p class="muted" style="margin-top:12px">No hay riegos registrados en este periodo.</p>@endif
        @if($tipo === 'agua' && $aguaR['detalle']->count())
        <h3 style="margin-top:32px">Detalle de riegos</h3>
        <div class="table-frame" style="margin-top:12px"><div class="table-scroll"><table class="tbl"><thead><tr><th>Fecha</th><th>Parcela</th><th class="r">Agua</th><th>Tipo</th><th>Notas</th></tr></thead><tbody>
            @foreach($aguaR['detalle'] as $f)<tr><td>{{ $f['fecha'] }}</td><td>{{ $f['parcela'] }}</td><td class="r">{{ $fmt($f['litros']) }} L</td><td>{{ $f['tipo'] }}</td><td class="muted">{{ $f['notas'] }}</td></tr>@endforeach
        </tbody></table></div></div>
        @endif
    </section>
    @endif

    @if($alertasR)
    <section aria-labelledby="r-al">
        <h2 id="r-al">Alertas</h2>
        <div class="row" style="gap:12px;margin-top:12px">@foreach($alertasR['por_nivel'] as $x)<span class="tag lvl lvl-{{ $x['nivel'] }}">{{ $nombreNivel[$x['nivel']] }}: {{ $x['total'] }}</span>@endforeach @foreach($alertasR['por_grupo'] as $x)<span class="tag lvl lvl-info plain">{{ $grupos[$x['grupo']] ?? $x['grupo'] }}: {{ $x['total'] }}</span>@endforeach</div>
        @if($alertasR['totales']['total'] > 0 && $tipo === 'alertas')<div id="rc-alertas" style="margin-top:16px" aria-label="Alertas por día"></div>@endif
        @if($tipo === 'alertas')
            @if($alertasR['detalle']->count())
            <div class="table-frame" style="margin-top:16px"><div class="table-scroll"><table class="tbl"><thead><tr><th>Fecha</th><th>Parcela</th><th>Nivel</th><th>Alerta</th><th>Estado</th><th>Cierre</th></tr></thead><tbody>
                @foreach($alertasR['detalle'] as $f)<tr><td style="white-space:nowrap">{{ $f['fecha'] }}</td><td>{{ $f['parcela'] }}</td><td><span class="tag lvl lvl-{{ $f['nivel'] }}">{{ $nombreNivel[$f['nivel']] ?? $f['nivel'] }}</span></td><td><strong>{{ $f['titulo'] }}</strong><div class="t-sec muted" style="max-width:56ch">{{ \Illuminate\Support\Str::limit($f['mensaje'], 140) }}</div></td><td>{{ $f['estado'] }}</td><td class="muted">{{ $f['cierre'] }}</td></tr>@endforeach
            </tbody></table></div></div>
            @else<p class="muted" style="margin-top:12px">No se generaron alertas en este periodo.</p>@endif
        @endif
    </section>
    @endif

    @if($climaR)
    <section aria-labelledby="r-cl">
        <h2 id="r-cl">Clima registrado</h2>
        @if(count($climaR['serie']['labels']))<div id="rc-clima" aria-label="Temperaturas diarias registradas"></div>
        <div class="table-frame" style="margin-top:16px"><div class="table-scroll"><table class="tbl"><thead><tr><th>Parcela</th><th class="r">Registros</th><th class="r">Temp. media</th><th class="r">Mínima</th><th class="r">Máxima</th><th class="r">Humedad</th><th class="r">Lluvia máx. 3 días</th></tr></thead><tbody>
            @foreach($climaR['por_parcela'] as $f)<tr><td>{{ $f['parcela'] }}</td><td class="r">{{ $f['registros'] }}</td><td class="r">{{ $fmt($f['temp_media'], 1) }} °C</td><td class="r">{{ $fmt($f['temp_min'], 1) }} °C</td><td class="r">{{ $fmt($f['temp_max'], 1) }} °C</td><td class="r">{{ $f['humedad_media'] }}%</td><td class="r">{{ $fmt($f['lluvia_max_72h'], 1) }} mm</td></tr>@endforeach
        </tbody></table></div></div>
        @else<p class="muted" style="margin-top:12px">No hay registros de clima en este periodo. El sistema los guarda cada 30 minutos cuando las parcelas tienen ubicación.</p>@endif
    </section>
    @endif

    @if($cicloR)
    <section aria-labelledby="r-ci">
        <h2 id="r-ci">Estado de los cultivos</h2>
        @if($cicloR['filas']->count())
        <div class="table-frame" style="margin-top:16px"><div class="table-scroll"><table class="tbl"><thead><tr><th>Parcela</th><th>Cultivo</th><th class="r">Superficie</th><th>Siembra</th><th style="min-width:160px">Avance del ciclo</th><th>Último riego</th><th class="r">Agua total</th><th class="r">L por m²</th></tr></thead><tbody>
            @foreach($cicloR['filas'] as $f)<tr><td>{{ $f['parcela'] }}<div class="t-meta">{{ $f['estado'] }}</div></td><td>{{ $f['cultivo'] }}</td><td class="r">{{ $fmt($f['area']) }} m²</td><td>{{ $f['siembra'] }}</td>
                <td>@if($f['avance'] !== null)<div class="progress" role="progressbar" aria-valuenow="{{ $f['avance'] }}" aria-valuemin="0" aria-valuemax="100"><i style="width:{{ $f['avance'] }}%"></i></div><span class="t-meta">{{ $f['avance'] }}%, día {{ $f['dias'] }} de {{ $f['ciclo'] }}</span>@else<span class="muted">Sin fecha de siembra</span>@endif</td>
                <td>{{ $f['ultimo_riego'] }}</td><td class="r">{{ $agua($f['agua_total']) }}</td><td class="r">{{ $fmt($f['agua_m2'], 1) }}</td></tr>@endforeach
        </tbody></table></div></div>
        @else<p class="muted" style="margin-top:12px">No hay parcelas para mostrar.</p>@endif
    </section>
    @endif

    <p class="t-meta">Generado el {{ $generado->translatedFormat('j \d\e F \d\e Y, H:i') }}. Fuente: registros de Makiyaku y datos de Open-Meteo.</p>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/makiyaku/charts.js') }}?v={{ @filemtime(public_path('js/makiyaku/charts.js')) }}" defer></script>
<script defer>
document.addEventListener('DOMContentLoaded', function () {
  var C = MKCharts, g = function (i) { return document.getElementById(i); };
  @if($aguaR && array_sum($aguaR['serie']['datos']) > 0)
  C.barras(g('rc-agua'), { labels: @json($aguaR['serie']['labels']), series: [{ nombre: 'Agua', datos: @json($aguaR['serie']['datos']), color: 'chart-1' }], alto: 220, unidadY: 'L', fmtY: function (v) { return v >= 1000 ? C.fnum(v / 1000) + 'k' : C.fnum(v); }, fmtTip: function (v) { return C.fnum(v, 0) + ' L'; }, maxX: 12 });
  @endif
  @if($tipo === 'alertas' && $alertasR && $alertasR['totales']['total'] > 0)
  C.barras(g('rc-alertas'), { labels: @json($alertasR['por_dia']['labels']), series: [{ nombre: 'Alertas', datos: @json($alertasR['por_dia']['datos']), color: 'chart-4' }], alto: 200, fmtY: function (v) { return C.fnum(v, 0); }, fmtTip: function (v) { return C.fnum(v, 0); }, maxX: 12 });
  @endif
  @if($climaR && count($climaR['serie']['labels']))
  C.linea(g('rc-clima'), { labels: @json($climaR['serie']['labels']), series: [{ nombre: 'Máxima', datos: @json($climaR['serie']['max']), color: 'chart-2', area: true }, { nombre: 'Mínima', datos: @json($climaR['serie']['min']), color: 'chart-1', area: true }], alto: 230, unidadY: '°C', fmtY: function (v) { return C.fnum(v, 0); }, fmtTip: function (v) { return C.fnum(v, 1) + ' °C'; }, lineas: [{ valor: 0, texto: 'Helada', color: 'medio' }], maxX: 12 });
  @endif
});
</script>
@endpush
