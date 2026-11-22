@extends('layouts.app')
@section('title', 'Clima')

@section('content')
@php
    $a = $datos['actual'] ?? null;
    $ag = $datos['agro'] ?? null;
    $kmh = fn ($ms) => $ms === null ? '-' : number_format($ms * 3.6, 0);
    $n = fn ($v, $d = 1) => $v === null ? '-' : number_format($v, $d, ',', '.');
    $dir = function ($g) { $d = ['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSO','SO','OSO','O','ONO','NO','NNO']; return $d[(int) round(($g % 360) / 22.5) % 16]; };
    $uvCat = fn ($v) => $v === null ? '-' : ($v < 3 ? 'Bajo' : ($v < 6 ? 'Moderado' : ($v < 8 ? 'Alto' : ($v < 11 ? 'Muy alto' : 'Extremo'))));
    $temas = ['sol' => ['#0a3a4d', '#1d7f8c'], 'luna' => ['#050f1d', '#122a45'], 'parcial' => ['#0b3447', '#2b6f86'], 'nublado' => ['#16262c', '#3a5560'], 'niebla' => ['#1b2a2e', '#4a6168'],
              'llovizna' => ['#14242b', '#34505c'], 'lluvia' => ['#111e27', '#2a4656'], 'tormenta' => ['#14111f', '#35304f'], 'nieve' => ['#1a2a38', '#4c6a82']];
    [$wa, $wb] = $temas[$a['icono'] ?? 'parcial'] ?? $temas['parcial'];
    $nivelTag = ['critico' => 'Crítico', 'alto' => 'Atención', 'medio' => 'Aviso', 'ok' => 'Favorable', 'info' => 'Dato'];
@endphp

<header class="page-head">
    <div>
        <h1>Clima</h1>
        <p>@if($datos){{ $datos['lugar']['nombre'] }}@if($datos['lugar']['elevacion']), {{ number_format($datos['lugar']['elevacion'], 0, ',', '.') }} m s. n. m.{{ '' }}@endif Actualizado a las {{ \Carbon\Carbon::parse($datos['consultado_en'])->setTimezone($datos['lugar']['zona'])->format('H:i') }}. Datos de Open-Meteo.@else No pudimos obtener el clima.@endif</p>
    </div>
    <div class="head-actions">
        @if($datos)<a href="{{ request()->fullUrlWithQuery(['refrescar' => 1]) }}" class="btn"><x-icono name="actualizar" :size="18" />Actualizar</a>@endif
    </div>
</header>

<form method="GET" class="panel" style="padding:16px;margin-bottom:24px;display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end" role="search">
    @if($parcelas->count())
    <div class="field" style="min-width:220px"><label for="sel-p">Parcela</label>
        <select id="sel-p" name="parcela" class="select" onchange="this.form.submit()">
            <option value="">Elegir una parcela</option>
            @foreach($parcelas as $p)<option value="{{ $p->id }}" @selected($parcelaId === $p->id)>{{ $p->nombre }}</option>@endforeach
        </select></div>
    @endif
    <div class="field" style="flex:1;min-width:240px;position:relative"><label for="ciudad">Buscar otro lugar</label>
        <input id="ciudad" name="ciudad" type="search" class="input" placeholder="Ciudad o distrito" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="res-ciudades">
        <ul id="res-ciudades" class="menu-pop" role="listbox" style="left:0;right:0;top:calc(100% + 4px)" hidden></ul>
    </div>
    <button class="btn btn-primary" type="submit">Ver clima</button>
</form>

@if(!$datos)
    <section class="panel"><div class="empty"><x-icono name="nube" :size="40" :stroke="1.5" /><h3>No pudimos obtener el clima</h3><p>Puede ser un problema de conexión o que el servicio de datos esté ocupado. Prueba de nuevo en un momento.</p><a href="{{ request()->fullUrlWithQuery(['refrescar' => 1]) }}" class="btn btn-primary">Intentar de nuevo</a></div></section>
@else

<section class="wx-hero" style="--wx-a:{{ $wa }};--wx-b:{{ $wb }}" aria-label="Clima actual">
    <div class="row between" style="align-items:flex-start;flex-wrap:nowrap;gap:24px">
        <div>
            <div class="place">{{ ucfirst($a['fecha']) }}, {{ $a['hora'] }}</div>
            <div class="temp">{{ number_format($a['temp'], 0) }}°</div>
            <div class="cond">{{ $a['descripcion'] }}</div>
            <div class="feels">Sensación de {{ number_format($a['sensacion'], 0) }} °C. Hoy entre {{ $n($a['tmin_hoy'], 0) }} y {{ $n($a['tmax_hoy'], 0) }} °C.</div>
        </div>
        <img class="ico" src="{{ asset('img/clima/' . $a['icono'] . '.svg') }}" alt="" width="96" height="96">
    </div>
    <div class="wx-strip">
        <div><div class="k">Humedad</div><div class="v">{{ $a['humedad'] }}%</div></div>
        <div><div class="k">Viento</div><div class="v">{{ $kmh($a['viento']) }} km/h {{ $dir($a['viento_dir']) }}</div></div>
        <div><div class="k">Lluvia, 24 h</div><div class="v">{{ $n($ag['lluvia_24h']) }} mm</div></div>
        <div><div class="k">Índice UV</div><div class="v">{{ $n($a['uv']) }}, {{ $uvCat($a['uv']) }}</div></div>
        <div><div class="k">Presión</div><div class="v">{{ $n($a['presion'], 0) }} hPa</div></div>
        <div><div class="k">Visibilidad</div><div class="v">{{ $n($a['visibilidad_km']) }} km</div></div>
    </div>
</section>

@if(count($avisos))
<section class="panel" style="margin-top:24px" aria-labelledby="h-av">
    <div class="panel-head"><h2 id="h-av">Lo que importa hoy en el campo</h2></div>
    @foreach($avisos as $av)
        <div class="item lvl lvl-{{ $av['nivel'] }}" style="border-top:1px solid var(--border)">
            <span class="ic"><x-icono :name="$av['icono']" :size="20" /></span>
            <div class="body"><span class="title">{{ $av['titulo'] }}</span> <span class="tag lvl lvl-{{ $av['nivel'] }}" style="margin-left:8px">{{ $nivelTag[$av['nivel']] ?? 'Dato' }}</span><p class="t-sec muted" style="margin-top:4px">{{ $av['texto'] }}</p></div>
        </div>
    @endforeach
</section>
@endif

<div style="margin-top:32px" data-tabs data-hash>
    <div class="tabs" role="tablist" aria-label="Secciones del clima">
        @foreach(['t-resumen' => 'Resumen', 't-horas' => 'Por hora', 't-dias' => '14 días', 't-agro' => 'Agricultura', 't-atmos' => 'Atmósfera', 't-hist' => 'Historial'] as $id => $txt)
            <button class="tab" role="tab" id="{{ $id }}" aria-controls="p-{{ $id }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? 0 : -1 }}">{{ $txt }}</button>
        @endforeach
    </div>

    {{-- ================= RESUMEN ================= --}}
    <div class="tabpanel stack-lg" id="p-t-resumen" role="tabpanel" aria-labelledby="t-resumen" style="padding-top:24px">
        <div class="grid g-2">
            <section class="panel"><div class="panel-head"><h3>Temperatura, 24 horas</h3></div><div class="panel-body"><div class="legend" style="margin-bottom:8px"><span><i style="background:var(--chart-1)"></i>Aire</span><span><i style="background:var(--chart-3)"></i>Sensación</span></div><div id="c-r-temp" aria-label="Temperatura de las próximas 24 horas"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>Lluvia, 24 horas</h3></div><div class="panel-body"><div id="c-r-lluvia" aria-label="Lluvia de las próximas 24 horas"></div></div></section>
        </div>
        <div class="metrics">
            <div class="metric"><span class="l">Mínima en 24 h</span><span class="v">{{ $ag['min_24h'] ? $n($ag['min_24h']['valor']) . ' °C' : '-' }}</span><span class="d">{{ $ag['min_24h'] ? 'hacia las ' . $ag['min_24h']['hora'] : '' }}</span></div>
            <div class="metric"><span class="l">Máxima en 24 h</span><span class="v">{{ $ag['max_24h'] ? $n($ag['max_24h']['valor']) . ' °C' : '-' }}</span><span class="d">{{ $ag['max_24h'] ? 'hacia las ' . $ag['max_24h']['hora'] : '' }}</span></div>
            <div class="metric"><span class="l">Lluvia en 72 h</span><span class="v">{{ $n($ag['lluvia_72h']) }} mm</span><span class="d">{{ $n($ag['lluvia_24h']) }} mm hoy, {{ $n($ag['lluvia_48h']) }} mm en 48 h</span></div>
            <div class="metric"><span class="l">Probabilidad de lluvia</span><span class="v">{{ $ag['prob_lluvia_24h'] }}%</span><span class="d">máxima en 24 h</span></div>
            <div class="metric"><span class="l">Punto de rocío</span><span class="v">{{ $n($a['punto_rocio']) }} °C</span><span class="d">Humedad {{ $a['humedad'] }}%</span></div>
            <div class="metric"><span class="l">Nubosidad</span><span class="v">{{ $a['nubes'] }}%</span><span class="d">cielo cubierto</span></div>
            <div class="metric"><span class="l">Evapotranspiración, 24 h</span><span class="v">{{ $n($ag['et0_24h']) }} mm</span><span class="d">agua que pierde el cultivo</span></div>
            <div class="metric"><span class="l">Horas de sol hoy</span><span class="v">{{ $n($datos['sol']['horas_sol']) }} h</span><span class="d">de {{ $n($datos['sol']['duracion_h']) }} h de luz</span></div>
        </div>
        <div class="grid g-2">
            <section class="panel"><div class="panel-head"><h3>Sol</h3></div><div class="panel-body"><div id="c-sol" aria-label="Recorrido del sol hoy"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>Calidad del aire</h3></div><div class="panel-body">
                @if($datos['aire'])<div id="c-aqi-r" aria-label="Índice de calidad del aire"></div><p class="t-sec muted" style="text-align:center;margin-top:8px">{{ $datos['aire']['categoria'][0] }}. PM2,5: {{ $n($datos['aire']['pm25']) }} µg/m³</p>
                @else<p class="muted">No hay datos de calidad del aire para este lugar.</p>@endif
            </div></section>
        </div>
    </div>

    {{-- ================= POR HORA ================= --}}
    <div class="tabpanel stack-lg" id="p-t-horas" role="tabpanel" aria-labelledby="t-horas" style="padding-top:24px" hidden>
        <section class="panel"><div class="panel-head"><h3>Próximas 24 horas</h3></div>
            <div class="hours" role="list">
                @foreach(array_slice($datos['horas'], 0, 24) as $h)
                    <div class="hour" role="listitem"><div class="t">{{ \Carbon\Carbon::createFromTimestamp($h['ts'], $datos['lugar']['zona'])->format('H:i') }}</div><img src="{{ asset('img/clima/' . $h['icono'] . '.svg') }}" alt="{{ $h['descripcion'] }}"><div class="num" style="font-weight:var(--w-semibold)">{{ number_format($h['temperature_2m'], 0) }}°</div><div class="p">{{ ($h['precipitation_probability'] ?? 0) >= 20 ? $h['precipitation_probability'] . '%' : '' }}</div></div>
                @endforeach
            </div>
        </section>
        <div class="grid g-2">
            <section class="panel"><div class="panel-head"><h3>Temperatura</h3></div><div class="panel-body"><div class="legend" style="margin-bottom:8px"><span><i style="background:var(--chart-1)"></i>Aire</span><span><i style="background:var(--chart-3)"></i>Sensación</span><span><i style="background:var(--chart-5)"></i>Punto de rocío</span></div><div id="c-h-temp" aria-label="Temperatura por hora"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>Lluvia y probabilidad</h3></div><div class="panel-body"><div id="c-h-lluvia" aria-label="Lluvia por hora"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>Humedad relativa</h3></div><div class="panel-body"><div id="c-h-hum" aria-label="Humedad relativa por hora"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>Viento</h3></div><div class="panel-body"><div class="legend" style="margin-bottom:8px"><span><i style="background:var(--chart-1)"></i>10 m</span><span><i style="background:var(--chart-3)"></i>80 m</span><span><i style="background:var(--chart-5)"></i>120 m</span><span><i style="background:var(--chart-4)"></i>Ráfagas</span></div><div id="c-h-viento" aria-label="Viento por hora en km/h"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>Presión atmosférica</h3></div><div class="panel-body"><div id="c-h-pres" aria-label="Presión a nivel del mar"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>Nubosidad por capas</h3></div><div class="panel-body"><div class="legend" style="margin-bottom:8px"><span><i style="background:var(--chart-1)"></i>Bajas</span><span><i style="background:var(--chart-2)"></i>Medias</span><span><i style="background:var(--chart-5)"></i>Altas</span></div><div id="c-h-nubes" aria-label="Nubosidad por capas"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>Índice UV</h3></div><div class="panel-body"><div id="c-h-uv" aria-label="Índice UV por hora"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>Visibilidad</h3></div><div class="panel-body"><div id="c-h-vis" aria-label="Visibilidad en kilómetros"></div></div></section>
        </div>
    </div>

    {{-- ================= 14 DIAS ================= --}}
    <div class="tabpanel stack-lg" id="p-t-dias" role="tabpanel" aria-labelledby="t-dias" style="padding-top:24px" hidden>
        @php $diasF = array_values(array_filter($datos['dias'], fn ($x) => $x['fecha'] >= \Carbon\Carbon::now($datos['lugar']['zona'])->toDateString())); $rMin = min(array_column($diasF, 'temperature_2m_min')); $rMax = max(array_column($diasF, 'temperature_2m_max')); @endphp
        <section class="panel"><div class="panel-head"><h3>Próximos días</h3></div>
            <div class="hscroll"><div class="days" style="grid-template-columns:repeat({{ count($diasF) }},minmax(104px,1fr));min-width:max-content">
                @foreach($diasF as $x)
                    @php $pL = ($x['temperature_2m_min'] - $rMin) / max(1, $rMax - $rMin) * 100; $pR = ($x['temperature_2m_max'] - $rMin) / max(1, $rMax - $rMin) * 100; @endphp
                    <div class="day">
                        <strong style="font-size:var(--t-sec)">{{ $x['es_hoy'] ? 'Hoy' : \Carbon\Carbon::parse($x['fecha'])->translatedFormat('D j') }}</strong>
                        <img src="{{ asset('img/clima/' . $x['icono'] . '.svg') }}" alt="{{ $x['descripcion'] }}">
                        <span class="num" style="font-weight:var(--w-semibold)">{{ number_format($x['temperature_2m_max'], 0) }}°</span>
                        <div class="rng" aria-hidden="true"><i style="left:{{ $pL }}%;width:{{ max(6, $pR - $pL) }}%"></i></div>
                        <span class="t-sec muted num">{{ number_format($x['temperature_2m_min'], 0) }}°</span>
                        <span class="t-meta" style="color:var(--accent-ink)">{{ ($x['precipitation_sum'] ?? 0) >= .5 ? $n($x['precipitation_sum']) . ' mm' : 'Seco' }}</span>
                    </div>
                @endforeach
            </div></div>
        </section>
        <div class="grid g-2">
            <section class="panel"><div class="panel-head"><h3>Temperatura máxima y mínima</h3></div><div class="panel-body"><div class="legend" style="margin-bottom:8px"><span><i style="background:var(--chart-2)"></i>Máxima</span><span><i style="background:var(--chart-1)"></i>Mínima</span></div><div id="c-d-temp" aria-label="Temperaturas diarias"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>Lluvia diaria</h3></div><div class="panel-body"><div id="c-d-lluvia" aria-label="Lluvia diaria"></div></div></section>
        </div>
        <section class="table-frame"><div class="panel-head"><h3>Detalle por día</h3></div>
            <div class="table-scroll"><table class="tbl"><thead><tr><th>Día</th><th>Cielo</th><th class="r">Mín.</th><th class="r">Máx.</th><th class="r">Lluvia</th><th class="r">Prob.</th><th class="r">Viento máx.</th><th class="r">Ráfaga</th><th class="r">UV</th><th class="r">ET₀</th><th class="r">Sol</th></tr></thead><tbody>
            @foreach($diasF as $x)
                <tr><td>{{ $x['es_hoy'] ? 'Hoy' : \Carbon\Carbon::parse($x['fecha'])->translatedFormat('D j M') }}</td><td>{{ $x['descripcion'] }}</td><td class="r">{{ $n($x['temperature_2m_min'], 0) }}°</td><td class="r">{{ $n($x['temperature_2m_max'], 0) }}°</td><td class="r">{{ $n($x['precipitation_sum']) }} mm</td><td class="r">{{ $x['precipitation_probability_max'] ?? '-' }}%</td><td class="r">{{ $kmh($x['wind_speed_10m_max']) }} km/h</td><td class="r">{{ $kmh($x['wind_gusts_10m_max']) }} km/h</td><td class="r">{{ $n($x['uv_index_max']) }}</td><td class="r">{{ $n($x['et0_fao_evapotranspiration']) }} mm</td><td class="r">{{ $n(($x['sunshine_duration'] ?? 0) / 3600) }} h</td></tr>
            @endforeach
            </tbody></table></div>
        </section>
    </div>

    {{-- ================= AGRICULTURA ================= --}}
    <div class="tabpanel stack-lg" id="p-t-agro" role="tabpanel" aria-labelledby="t-agro" style="padding-top:24px" hidden>
        <div class="grid g-3">
            <section class="panel"><div class="panel-head"><h3>Mejor hora para regar</h3></div><div class="panel-body">
                @if($ag['ventanas_riego'])<ul style="margin:0;padding-left:18px">@foreach($ag['ventanas_riego'] as $w)<li class="num" style="font-size:var(--t-lg)">{{ $w }}</li>@endforeach</ul><p class="t-sec muted" style="margin-top:8px">De día (antes de las 10:00 o después de las 16:00), con poco viento, sin lluvia y sin riesgo de helada.</p>
                @else<p class="muted">En las próximas 24 horas no hay un buen momento: hay lluvia, viento fuerte o frío intenso.</p>@endif</div></section>
            <section class="panel"><div class="panel-head"><h3>Mejor hora para fumigar</h3></div><div class="panel-body">
                @if($ag['ventanas_fumigar'])<ul style="margin:0;padding-left:18px">@foreach($ag['ventanas_fumigar'] as $w)<li class="num" style="font-size:var(--t-lg)">{{ $w }}</li>@endforeach</ul><p class="t-sec muted" style="margin-top:8px">De día, viento de 12 km/h o menos y Delta T entre 2 y 8.</p>
                @else<p class="muted">En las próximas 24 horas no hay condiciones buenas para fumigar.</p>@endif</div></section>
            <section class="panel"><div class="panel-head"><h3>Frío en 72 horas</h3></div><div class="panel-body">
                <div class="figure"><span class="v">{{ $ag['horas_helada_72h'] }} h</span><span class="l">bajo 0 °C</span><span class="d">{{ $ag['horas_frio_72h'] }} h por debajo de 7 °C</span></div></div></section>
        </div>
        <div class="grid g-2">
            <section class="panel"><div class="panel-head"><h3>Balance de agua por día</h3></div><div class="panel-body"><div class="legend" style="margin-bottom:8px"><span><i style="background:var(--chart-1)"></i>Lluvia</span><span><i style="background:var(--chart-2)"></i>Evapotranspiración</span></div><div id="c-a-balance" aria-label="Lluvia frente a evapotranspiración"></div><p class="t-sec muted" style="margin-top:8px">Cuando la evapotranspiración supera a la lluvia, el suelo pierde agua y hay que regar.</p></div></section>
            <section class="panel"><div class="panel-head"><h3>Radiación solar</h3></div><div class="panel-body"><div class="legend" style="margin-bottom:8px"><span><i style="background:var(--chart-2)"></i>Total</span><span><i style="background:var(--chart-4)"></i>Directa</span><span><i style="background:var(--chart-5)"></i>Difusa</span></div><div id="c-a-rad" aria-label="Radiación solar en W/m²"></div></div></section>
        </div>
        <div class="grid g-2">
            <section class="panel"><div class="panel-head"><h3>Humedad del suelo ahora</h3></div><div class="panel-body"><div id="c-a-hs" aria-label="Humedad del suelo por profundidad"></div><p class="t-sec muted" style="margin-top:8px">Porcentaje del volumen del suelo ocupado por agua. Calculado por modelo, no medido en tu parcela.</p></div></section>
            <section class="panel"><div class="panel-head"><h3>Temperatura del suelo ahora</h3></div><div class="panel-body"><div id="c-a-ts" aria-label="Temperatura del suelo por profundidad"></div></div></section>
        </div>
        <section class="panel"><div class="panel-head"><h3>Temperatura del suelo, 72 horas</h3></div><div class="panel-body"><div class="legend" style="margin-bottom:8px"><span><i style="background:var(--chart-2)"></i>Superficie</span><span><i style="background:var(--chart-1)"></i>6 cm</span><span><i style="background:var(--chart-3)"></i>18 cm</span><span><i style="background:var(--chart-5)"></i>54 cm</span></div><div id="c-a-suelo" aria-label="Temperatura del suelo por profundidad"></div></div></section>
        <div class="grid g-2">
            <section class="panel"><div class="panel-head"><h3>Horas de sol por día</h3></div><div class="panel-body"><div id="c-a-sol" aria-label="Horas de sol por día"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>Déficit de presión de vapor</h3></div><div class="panel-body"><div id="c-a-vpd" aria-label="Déficit de presión de vapor en kPa"></div><p class="t-sec muted" style="margin-top:8px">Valores altos (más de 1,5 kPa) hacen que la planta pierda más agua por las hojas.</p></div></section>
        </div>
    </div>

    {{-- ================= ATMOSFERA ================= --}}
    <div class="tabpanel stack-lg" id="p-t-atmos" role="tabpanel" aria-labelledby="t-atmos" style="padding-top:24px" hidden>
        <div class="grid g-3">
            <section class="panel"><div class="panel-head"><h3>Viento ahora</h3></div><div class="panel-body"><div id="c-brujula" aria-label="Dirección del viento"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>Energía de tormentas (CAPE)</h3></div><div class="panel-body"><div id="c-cape" aria-label="Energía convectiva"></div><p class="t-sec muted" style="text-align:center">{{ ($a['cape'] ?? 0) < 300 ? 'Poco riesgo de tormenta.' : (($a['cape'] ?? 0) < 1000 ? 'Tormentas posibles por la tarde.' : 'Alto riesgo de tormentas fuertes.') }}</p></div></section>
            <section class="panel"><div class="panel-head"><h3>Calidad del aire</h3></div><div class="panel-body">@if($datos['aire'])<div id="c-aqi" aria-label="Índice de calidad del aire"></div><p class="t-sec muted" style="text-align:center">{{ $datos['aire']['categoria'][0] }}</p>@else<p class="muted">Sin datos para este lugar.</p>@endif</div></section>
        </div>
        <div class="metrics">
            <div class="metric"><span class="l">Presión a nivel del mar</span><span class="v">{{ $n($a['presion'], 0) }} hPa</span></div>
            <div class="metric"><span class="l">Presión en el lugar</span><span class="v">{{ $n($a['presion_estacion'], 0) }} hPa</span><span class="d">según la altitud</span></div>
            <div class="metric"><span class="l">Nivel de 0 °C</span><span class="v">{{ $a['isoterma_0'] !== null ? number_format($a['isoterma_0'], 0, ',', '.') . ' m' : '-' }}</span><span class="d">altura donde el aire se congela</span></div>
            <div class="metric"><span class="l">Ráfaga máxima</span><span class="v">{{ $kmh($a['rafaga']) }} km/h</span></div>
            <div class="metric"><span class="l">Nieve en el suelo</span><span class="v">{{ $n(($a['nieve_profundidad'] ?? 0) * 100, 0) }} cm</span></div>
            <div class="metric"><span class="l">Visibilidad</span><span class="v">{{ $n($a['visibilidad_km']) }} km</span></div>
            @if($datos['aire'])
            <div class="metric"><span class="l">PM10</span><span class="v">{{ $n($datos['aire']['pm10']) }} µg/m³</span></div>
            <div class="metric"><span class="l">PM2,5</span><span class="v">{{ $n($datos['aire']['pm25']) }} µg/m³</span></div>
            <div class="metric"><span class="l">Ozono</span><span class="v">{{ $n($datos['aire']['o3'], 0) }} µg/m³</span></div>
            <div class="metric"><span class="l">Dióxido de nitrógeno</span><span class="v">{{ $n($datos['aire']['no2']) }} µg/m³</span></div>
            <div class="metric"><span class="l">Polvo en el aire</span><span class="v">{{ $n($datos['aire']['polvo']) }} µg/m³</span></div>
            <div class="metric"><span class="l">Monóxido de carbono</span><span class="v">{{ $n($datos['aire']['co'], 0) }} µg/m³</span></div>
            @endif
        </div>
        <div class="grid g-2">
            <section class="panel"><div class="panel-head"><h3>Nivel de 0 °C, 72 horas</h3></div><div class="panel-body"><div id="c-t-iso" aria-label="Altura de la isoterma de cero grados"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>CAPE, 72 horas</h3></div><div class="panel-body"><div id="c-t-cape" aria-label="Energía convectiva disponible"></div></div></section>
            @if($datos['aire'])<section class="panel" style="grid-column:1/-1"><div class="panel-head"><h3>Partículas PM2,5, 48 horas</h3></div><div class="panel-body"><div id="c-t-pm" aria-label="Partículas finas PM2,5"></div></div></section>@endif
        </div>
    </div>

    {{-- ================= HISTORIAL ================= --}}
    <div class="tabpanel stack-lg" id="p-t-hist" role="tabpanel" aria-labelledby="t-hist" style="padding-top:24px" hidden>
        @if($datos['historial'])
        @php $hi = $datos['historial']; $bal = round($hi['lluvia_30d'] - $hi['et0_30d'], 1); @endphp
        <div class="metrics">
            <div class="metric"><span class="l">Lluvia en 30 días</span><span class="v">{{ $n($hi['lluvia_30d']) }} mm</span><span class="d">{{ $hi['lluvia_30d_anterior'] !== null ? 'Hace un año: ' . $n($hi['lluvia_30d_anterior']) . ' mm' : 'Sin dato del año anterior' }}</span></div>
            <div class="metric"><span class="l">Días con lluvia</span><span class="v">{{ $hi['dias_con_lluvia_30d'] }}</span><span class="d">de los últimos 30</span></div>
            <div class="metric"><span class="l">Días de helada</span><span class="v">{{ $hi['dias_helada_60d'] }}</span><span class="d">en 60 días</span></div>
            <div class="metric"><span class="l">Temperatura media</span><span class="v">{{ $n($hi['tmedia_30d']) }} °C</span><span class="d">últimos 30 días</span></div>
            <div class="metric"><span class="l">Grados-día (base 10 °C)</span><span class="v">{{ $n($hi['gdd_30d'], 0) }}</span><span class="d">calor acumulado en 30 días</span></div>
            <div class="metric"><span class="l">Evapotranspiración, 30 días</span><span class="v">{{ $n($hi['et0_30d']) }} mm</span></div>
            <div class="metric"><span class="l">Balance de agua, 30 días</span><span class="v">{{ $bal > 0 ? '+' : '' }}{{ $n($bal) }} mm</span><span class="d">{{ $bal >= 0 ? 'Llovió más de lo que se evaporó' : 'Se evaporó más de lo que llovió' }}</span></div>
        </div>
        <div class="grid g-2">
            <section class="panel"><div class="panel-head"><h3>Temperatura, 60 días</h3></div><div class="panel-body"><div class="legend" style="margin-bottom:8px"><span><i style="background:var(--chart-2)"></i>Máxima</span><span><i style="background:var(--chart-1)"></i>Mínima</span></div><div id="c-i-temp" aria-label="Temperaturas de los últimos 60 días"></div></div></section>
            <section class="panel"><div class="panel-head"><h3>Lluvia, 60 días</h3></div><div class="panel-body"><div id="c-i-lluvia" aria-label="Lluvia de los últimos 60 días"></div></div></section>
        </div>
        @else
        <section class="panel"><div class="empty"><h3>Sin historial</h3><p>No pudimos traer el historial de este lugar. Prueba de nuevo más tarde.</p></div></section>
        @endif
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="{{ asset('js/makiyaku/charts.js') }}?v={{ @filemtime(public_path('js/makiyaku/charts.js')) }}" defer></script>
<script defer>
document.addEventListener('DOMContentLoaded', function () {
  /* ---- buscador de lugares ---- */
  var inp = document.getElementById('ciudad'), lista = document.getElementById('res-ciudades'), t;
  inp.addEventListener('input', function () {
    clearTimeout(t); var q = inp.value.trim();
    if (q.length < 2) { lista.hidden = true; return; }
    t = setTimeout(function () {
      fetch(@json(route('clima.buscar')) + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (r) {
        lista.innerHTML = ''; inp.setAttribute('aria-expanded', r.length ? 'true' : 'false');
        r.forEach(function (c) { var li = document.createElement('li'); li.setAttribute('role', 'option'); var b = document.createElement('button'); b.type = 'button'; b.className = 'menu-item'; b.textContent = c.display_name;
          b.onclick = function () { location.href = @json(route('clima.index')) + '?lat=' + c.lat + '&lon=' + c.lon + '&nombre=' + encodeURIComponent(c.display_name); }; li.appendChild(b); lista.appendChild(li); });
        lista.hidden = !r.length;
      });
    }, 280);
  });
  document.addEventListener('click', function (e) { if (!e.target.closest('#ciudad, #res-ciudades')) lista.hidden = true; });

  @if($datos)
  var S = @json($serie), C = MKCharts, g = function (id) { return document.getElementById(id); };
  var f0 = function (v) { return C.fnum(v, 0); }, f1 = function (v) { return C.fnum(v, 1); };
  var cortar = function (a, n) { return a.slice(0, n); };
  var H = S.h, N24 = 24;
  var base = { titulos: H.tit };

  /* resumen */
  C.linea(g('c-r-temp'), { labels: cortar(H.lab, N24), titulos: cortar(H.tit, N24), series: [{ nombre: 'Aire', datos: cortar(H.temp, N24), color: 'chart-1', area: true }, { nombre: 'Sensación', datos: cortar(H.sens, N24), color: 'chart-3', dash: '5 4', ancho: 2 }], alto: 210, unidadY: '°C', fmtY: f0, fmtTip: function (v) { return f1(v) + ' °C'; }, lineas: [{ valor: 0, texto: 'Helada', color: 'medio' }], maxX: 6 });
  C.barras(g('c-r-lluvia'), { labels: cortar(H.lab, N24), titulos: cortar(H.tit, N24), series: [{ nombre: 'Lluvia', datos: cortar(H.lluvia, N24), color: 'chart-1' }], linea: { nombre: 'Probabilidad', datos: cortar(H.prob, N24), color: 'chart-2', max: 100, fmt: function (v) { return f0(v) + '%'; } }, alto: 210, unidadY: 'mm', fmtY: f1, fmtTip: function (v) { return f1(v) + ' mm'; }, maxX: 6 });
  C.arcoSolar(g('c-sol'), { amanecer: @json($datos['sol']['amanecer']), atardecer: @json($datos['sol']['atardecer']), ahora: @json($datos['sol']['ahora']), duracion: @json($n($datos['sol']['duracion_h']) . ' h de luz') });
  @if($datos['aire'])
  var aqiOpts = { valor: {{ (int) ($datos['aire']['aqi'] ?? 0) }}, min: 0, max: 300, tam: 240, zonas: [{ hasta: 50, color: 'ok' }, { hasta: 100, color: 'chart-2' }, { hasta: 150, color: 'alto' }, { hasta: 200, color: 'critico' }, { hasta: 300, color: 'chart-6' }] };
  C.medidor(g('c-aqi-r'), aqiOpts); if (g('c-aqi')) C.medidor(g('c-aqi'), aqiOpts);
  if (g('c-t-pm')) C.linea(g('c-t-pm'), { labels: S.aire.lab, series: [{ nombre: 'PM2,5', datos: S.aire.pm25, color: 'chart-4', area: true }], alto: 200, unidadY: 'µg/m³', fmtY: f0, fmtTip: function (v) { return f1(v) + ' µg/m³'; }, maxX: 8, lineas: [{ valor: 15, texto: 'Guía OMS (24 h)', color: 'alto' }] });
  @endif

  /* por hora */
  C.linea(g('c-h-temp'), { labels: H.lab, titulos: H.tit, series: [{ nombre: 'Aire', datos: H.temp, color: 'chart-1', area: true }, { nombre: 'Sensación', datos: H.sens, color: 'chart-3', dash: '5 4', ancho: 2 }, { nombre: 'Rocío', datos: H.rocio, color: 'chart-5', ancho: 1.8 }], alto: 230, unidadY: '°C', fmtY: f0, fmtTip: function (v) { return f1(v) + ' °C'; }, lineas: [{ valor: 0, texto: 'Helada', color: 'medio' }], maxX: 8 });
  C.barras(g('c-h-lluvia'), { labels: H.lab, titulos: H.tit, series: [{ nombre: 'Lluvia', datos: H.lluvia, color: 'chart-1' }], linea: { nombre: 'Probabilidad', datos: H.prob, color: 'chart-2', max: 100, fmt: function (v) { return f0(v) + '%'; } }, alto: 230, unidadY: 'mm', fmtY: f1, fmtTip: function (v) { return f1(v) + ' mm'; }, maxX: 8 });
  C.linea(g('c-h-hum'), { labels: H.lab, titulos: H.tit, series: [{ nombre: 'Humedad', datos: H.hum, color: 'chart-1', area: true }], alto: 200, yMin: 0, yMax: 100, unidadY: '%', fmtY: f0, fmtTip: function (v) { return f0(v) + '%'; }, maxX: 8 });
  C.linea(g('c-h-viento'), { labels: H.lab, titulos: H.tit, series: [{ nombre: '10 m', datos: H.v10, color: 'chart-1', area: true }, { nombre: '80 m', datos: H.v80, color: 'chart-3', ancho: 1.8 }, { nombre: '120 m', datos: H.v120, color: 'chart-5', ancho: 1.8 }, { nombre: 'Ráfagas', datos: H.raf, color: 'chart-4', dash: '4 4', ancho: 1.8 }], alto: 220, yMin: 0, unidadY: 'km/h', fmtY: f0, fmtTip: function (v) { return f0(v) + ' km/h'; }, lineas: [{ valor: 36, texto: 'Viento fuerte', color: 'alto' }], maxX: 8 });
  C.linea(g('c-h-pres'), { labels: H.lab, titulos: H.tit, series: [{ nombre: 'Presión', datos: H.pres, color: 'chart-3', area: true }], alto: 200, unidadY: 'hPa', fmtY: f0, fmtTip: function (v) { return f1(v) + ' hPa'; }, maxX: 8 });
  C.linea(g('c-h-nubes'), { labels: H.lab, titulos: H.tit, series: [{ nombre: 'Bajas', datos: H.nub_b, color: 'chart-1' }, { nombre: 'Medias', datos: H.nub_m, color: 'chart-2' }, { nombre: 'Altas', datos: H.nub_a, color: 'chart-5' }], alto: 200, yMin: 0, yMax: 100, unidadY: '%', fmtY: f0, fmtTip: function (v) { return f0(v) + '%'; }, maxX: 8 });
  C.linea(g('c-h-uv'), { labels: H.lab, titulos: H.tit, series: [{ nombre: 'UV', datos: H.uv, color: 'chart-2', area: true }], alto: 200, yMin: 0, fmtY: f0, fmtTip: function (v) { return f1(v); }, lineas: [{ valor: 8, texto: 'Muy alto', color: 'critico' }, { valor: 3, texto: 'Moderado', color: 'alto' }], maxX: 8 });
  C.linea(g('c-h-vis'), { labels: H.lab, titulos: H.tit, series: [{ nombre: 'Visibilidad', datos: H.vis, color: 'chart-5', area: true }], alto: 200, yMin: 0, unidadY: 'km', fmtY: f0, fmtTip: function (v) { return f1(v) + ' km'; }, maxX: 8 });

  /* 14 dias */
  var D = S.d;
  C.linea(g('c-d-temp'), { labels: D.lab, titulos: D.tit, series: [{ nombre: 'Máxima', datos: D.tmax, color: 'chart-2', area: true }, { nombre: 'Mínima', datos: D.tmin, color: 'chart-1', area: true }], alto: 240, unidadY: '°C', fmtY: f0, fmtTip: function (v) { return f1(v) + ' °C'; }, lineas: [{ valor: 0, texto: 'Helada', color: 'medio' }], maxX: 8 });
  C.barras(g('c-d-lluvia'), { labels: D.lab, titulos: D.tit, series: [{ nombre: 'Lluvia', datos: D.lluvia, color: 'chart-1' }], linea: { nombre: 'Probabilidad', datos: D.prob, color: 'chart-2', max: 100, fmt: function (v) { return f0(v) + '%'; } }, alto: 240, unidadY: 'mm', fmtY: f1, fmtTip: function (v) { return f1(v) + ' mm'; }, maxX: 8 });

  /* agricultura */
  C.barras(g('c-a-balance'), { labels: D.lab, titulos: D.tit, series: [{ nombre: 'Lluvia', datos: D.lluvia, color: 'chart-1' }, { nombre: 'Evapotranspiración', datos: D.et0, color: 'chart-2' }], alto: 230, unidadY: 'mm', fmtY: f1, fmtTip: function (v) { return f1(v) + ' mm'; }, maxX: 8 });
  C.linea(g('c-a-rad'), { labels: H.lab, titulos: H.tit, series: [{ nombre: 'Total', datos: H.rad, color: 'chart-2', area: true }, { nombre: 'Directa', datos: H.rad_dir, color: 'chart-4', ancho: 1.8 }, { nombre: 'Difusa', datos: H.rad_dif, color: 'chart-5', ancho: 1.8 }], alto: 230, yMin: 0, unidadY: 'W/m²', fmtY: f0, fmtTip: function (v) { return f0(v) + ' W/m²'; }, maxX: 8 });
  var hs = @json($ag['humedad_suelo']);
  C.perfil(g('c-a-hs'), { items: hs.map(function (x) { return { nombre: x.profundidad, valor: x.humedad }; }), min: 0, max: 60, color: 'chart-1', fmt: function (v) { return f1(v) + '%'; } });
  C.perfil(g('c-a-ts'), { items: hs.filter(function (x) { return x.temperatura !== null; }).map(function (x) { return { nombre: x.profundidad, valor: x.temperatura }; }), min: -5, max: 30, color: 'chart-2', fmt: function (v) { return f1(v) + ' °C'; } });
  C.linea(g('c-a-suelo'), { labels: H.lab, titulos: H.tit, series: [{ nombre: 'Superficie', datos: H.s0, color: 'chart-2' }, { nombre: '6 cm', datos: H.s6, color: 'chart-1' }, { nombre: '18 cm', datos: H.s18, color: 'chart-3' }, { nombre: '54 cm', datos: H.s54, color: 'chart-5' }], alto: 230, unidadY: '°C', fmtY: f0, fmtTip: function (v) { return f1(v) + ' °C'; }, lineas: [{ valor: 0, texto: 'Helada', color: 'medio' }], maxX: 8 });
  C.barras(g('c-a-sol'), { labels: D.lab, titulos: D.tit, series: [{ nombre: 'Horas de sol', datos: D.sol, color: 'chart-2' }], linea: { nombre: 'UV máximo', datos: D.uv, color: 'chart-4', max: 14, fmt: f1 }, alto: 220, unidadY: 'h', fmtY: f0, fmtTip: function (v) { return f1(v) + ' h'; }, maxX: 8 });
  C.linea(g('c-a-vpd'), { labels: H.lab, titulos: H.tit, series: [{ nombre: 'VPD', datos: H.vpd, color: 'chart-3', area: true }], alto: 220, yMin: 0, unidadY: 'kPa', fmtY: f1, fmtTip: function (v) { return f1(v) + ' kPa'; }, lineas: [{ valor: 1.5, texto: 'Estrés hídrico', color: 'alto' }], maxX: 8 });

  /* atmosfera */
  C.brujula(g('c-brujula'), { grados: {{ (int) $a['viento_dir'] }}, velocidad: {{ round($a['viento'] * 3.6, 1) }}, rafaga: {{ round(($a['rafaga'] ?? 0) * 3.6, 1) }}, unidad: 'km/h' });
  C.medidor(g('c-cape'), { valor: {{ (int) ($a['cape'] ?? 0) }}, min: 0, max: 2500, tam: 240, zonas: [{ hasta: 300, color: 'ok' }, { hasta: 1000, color: 'chart-2' }, { hasta: 2000, color: 'alto' }, { hasta: 2500, color: 'critico' }], texto: '{{ (int) ($a['cape'] ?? 0) }} J/kg' });
  C.linea(g('c-t-iso'), { labels: H.lab, titulos: H.tit, series: [{ nombre: 'Nivel de 0 °C', datos: H.iso0, color: 'chart-5', area: true }], alto: 210, unidadY: 'm', fmtY: function (v) { return C.fnum(v, 0); }, fmtTip: function (v) { return C.fnum(v, 0) + ' m'; }, maxX: 8 });
  C.linea(g('c-t-cape'), { labels: H.lab, titulos: H.tit, series: [{ nombre: 'CAPE', datos: H.cape, color: 'chart-4', area: true }], alto: 210, yMin: 0, unidadY: 'J/kg', fmtY: f0, fmtTip: function (v) { return f0(v) + ' J/kg'; }, maxX: 8 });

  /* historial */
  var I = S.hist;
  if (g('c-i-temp')) {
    C.linea(g('c-i-temp'), { labels: I.lab, series: [{ nombre: 'Máxima', datos: I.tmax, color: 'chart-2', area: true }, { nombre: 'Mínima', datos: I.tmin, color: 'chart-1', area: true }], alto: 240, unidadY: '°C', fmtY: f0, fmtTip: function (v) { return f1(v) + ' °C'; }, lineas: [{ valor: 0, texto: 'Helada', color: 'medio' }], maxX: 8 });
    C.barras(g('c-i-lluvia'), { labels: I.lab, series: [{ nombre: 'Lluvia', datos: I.lluvia, color: 'chart-1' }], linea: { nombre: 'Evapotranspiración', datos: I.et0, color: 'chart-2', fmt: f1 }, alto: 240, unidadY: 'mm', fmtY: f0, fmtTip: function (v) { return f1(v) + ' mm'; }, maxX: 8 });
  }
  @endif
});
</script>
@endpush
