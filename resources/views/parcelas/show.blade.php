@extends('layouts.app')
@section('title', $parcela->nombre)

@section('content')
@php
    $c = $parcela->climaActual; $cu = $parcela->cultivo; $calc = $plan['calculo'];
    $a = $parcela->alertas->where('grupo', 'riego')->first();
    $otras = $parcela->alertas->where('grupo', '!=', 'riego');
    $nivelTxt = ['critico' => 'Riego urgente', 'alto' => 'Riego hoy', 'medio' => 'Riego pronto', 'info' => 'Aviso'];
    $estadoTag = ['activa' => ['ok', 'Activa'], 'inactiva' => ['info', 'Inactiva'], 'mantenimiento' => ['alto', 'En mantenimiento']][$parcela->estado] ?? ['info', $parcela->estado];
    $fmt = fn ($n, $d = 0) => number_format($n, $d, ',', '.');
    $dias = $plan['dias_sin_regar'];
    $progresoCiclo = ($cu && $cu->cicloProductivo && $parcela->fecha_siembra) ? min(100, (int) round(\Carbon\Carbon::parse($parcela->fecha_siembra)->diffInDays(today()) / $cu->cicloProductivo * 100)) : null;
    $h48 = $pronostico ? array_slice($pronostico['horas'], 0, 48) : [];
    $z = $pronostico['lugar']['zona'] ?? 'America/Lima';
    $lbl = array_map(fn ($x) => \Carbon\Carbon::createFromTimestamp($x['ts'], $z)->format('H:i'), $h48);
    $ttl = array_map(fn ($x) => \Carbon\Carbon::createFromTimestamp($x['ts'], $z)->translatedFormat('D j, H:i'), $h48);
@endphp

<header class="page-head">
    <div>
        <div class="row" style="gap:8px"><h1>{{ $parcela->nombre }}</h1><span class="tag lvl lvl-{{ $estadoTag[0] }}">{{ $estadoTag[1] }}</span></div>
        <p>{{ $cu?->nombreCultivo ?? 'Sin cultivo' }}, {{ $fmt($parcela->area) }} m²@if($parcela->ubicacion), {{ $parcela->ubicacion->distrito }}, {{ $parcela->ubicacion->provincia }}@endif</p>
    </div>
    <div class="head-actions">
        <a href="{{ route('parcelas.edit', $parcela) }}" class="btn"><x-icono name="editar" :size="18" />Editar</a>
        <form method="POST" action="{{ route('parcelas.actualizar-clima', $parcela) }}">@csrf<button class="btn" type="submit" data-ocupado="Actualizando..."><x-icono name="actualizar" :size="18" />Actualizar clima</button></form>
        <button type="button" class="btn btn-primary" data-dlg-open="dlg-riego"><x-icono name="agua" :size="18" />Registrar riego</button>
    </div>
</header>

<div class="grid g-main">
<div class="stack-lg" style="min-width:0">

    {{-- Plan de riego --}}
    <section class="panel" aria-labelledby="h-plan">
        <div class="panel-body" style="display:grid;grid-template-columns:auto 1fr;gap:24px;align-items:center">
            <x-gota :nivel="$parcela->nivel_agua" :tono="$parcela->tono_riego" style="width:88px;height:112px" />
            <div>
                <h2 id="h-plan">@if($a){{ $nivelTxt[$a->nivel] ?? 'Riego' }}@else Plan de riego @endif</h2>
                <p class="muted" style="margin-top:4px;max-width:60ch">
                    @if($a){{ $a->mensaje }}
                    @elseif($plan['proximo'])No hace falta regar todavía. Próximo riego el <strong>{{ $plan['proximo']->translatedFormat('l j \d\e F') }}</strong>: {{ $plan['texto'] }}.
                    @else Registra tu último riego para calcular el siguiente. @endif
                </p>
                <div class="progress" style="margin-top:16px;max-width:420px" role="progressbar" aria-valuenow="{{ 100 - $parcela->nivel_agua }}" aria-valuemin="0" aria-valuemax="100" aria-label="Avance del ciclo de riego"><i style="width:{{ 100 - $parcela->nivel_agua }}%"></i></div>
                <div class="t-meta" style="margin-top:4px">{{ $dias === null ? 'Sin riegos registrados' : ($dias === 0 ? 'Regada hoy' : "Hace {$dias} " . ($dias === 1 ? 'día' : 'días') . ' del último riego') }}, se riega cada {{ $plan['frecuencia'] }} días</div>
            </div>
        </div>
        <dl class="dl" style="padding:0 24px 24px;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 32px">
            <div><dt>Cantidad recomendada hoy</dt><dd style="text-align:left;font-size:var(--t-lg)" class="num">{{ $calc['litros_total'] >= 1000 ? $fmt($calc['litros_total'] / 1000, 1) . ' m³' : $fmt($calc['litros_total']) . ' L' }}</dd><span class="t-meta">{{ $fmt($calc['litros_m2'], 1) }} L por m²@if($calc['litros_total'] < 1000), unos {{ $calc['baldes'] }} baldes de 20 L @endif</span></div>
            <div><dt>Agua usada en 30 días</dt><dd style="text-align:left;font-size:var(--t-lg)" class="num">{{ $totalAgua30 >= 1000 ? $fmt($totalAgua30 / 1000, 1) . ' m³' : $fmt($totalAgua30) . ' L' }}</dd><span class="t-meta">{{ $parcela->area > 0 ? $fmt($totalAgua30 / $parcela->area, 1) : 0 }} L por m²</span></div>
            <div><dt>Último riego</dt><dd style="text-align:left">{{ $parcela->ultimo_riego?->translatedFormat('j M Y') ?? 'Sin registro' }}</dd></div>
            <div><dt>Ajuste por clima</dt><dd style="text-align:left">{{ $calc['factor_clima'] > 1.05 ? 'Más seco de lo normal (+' . round(($calc['factor_clima'] - 1) * 100) . '%)' : ($calc['factor_clima'] < .95 ? 'Más fresco de lo normal (' . round(($calc['factor_clima'] - 1) * 100) . '%)' : 'Normal') }}</dd></div>
        </dl>
    </section>

    @if($otras->count())
    <section class="panel" aria-labelledby="h-al">
        <div class="panel-head"><h2 id="h-al">Alertas de esta parcela</h2><a href="{{ route('alertas.index', ['parcela' => $parcela->id]) }}" class="t-sec">Ver en alertas</a></div>
        @foreach($otras as $o)
            <div class="item lvl lvl-{{ $o->nivel }}" style="border-top:1px solid var(--border)">
                <span class="ic"><x-icono :name="$o->icono" :size="20" /></span>
                <div class="body"><span class="title">{{ $o->titulo }}</span> <span class="tag lvl lvl-{{ $o->nivel }}" style="margin-left:8px">{{ $o->etiqueta_nivel }}</span><p class="t-sec muted" style="margin-top:4px">{{ $o->mensaje }}</p></div>
            </div>
        @endforeach
    </section>
    @endif

    <section class="panel" aria-labelledby="h-ag">
        <div class="panel-head"><h2 id="h-ag">Agua aplicada, 45 días</h2><span class="t-sec muted">{{ $riegos->count() }} {{ $riegos->count() === 1 ? 'riego' : 'riegos' }}</span></div>
        <div class="panel-body">
            @if(array_sum($serieRiego['datos']) > 0)<div id="ch-riego" aria-label="Litros aplicados por día"></div>
            @else<div class="empty" style="padding:24px 0"><h3>Aún no hay riegos</h3><p>Registra el primero y verás aquí cuánta agua usas.</p><button class="btn btn-primary" data-dlg-open="dlg-riego">Registrar riego</button></div>@endif
        </div>
    </section>

    @if($pronostico)
    <section class="panel" aria-labelledby="h-pr">
        <div class="panel-head"><h2 id="h-pr">Próximas 48 horas</h2><a href="{{ route('clima.index', ['parcela' => $parcela->id]) }}" class="t-sec">Clima completo</a></div>
        <div class="panel-body">
            <div class="legend" style="margin-bottom:8px"><span><i style="background:var(--chart-1)"></i>Temperatura</span><span><i style="background:var(--chart-3)"></i>Temperatura del suelo</span></div>
            <div id="ch-pt" aria-label="Temperatura del aire y del suelo"></div>
            <div id="ch-pl" style="margin-top:8px" aria-label="Lluvia prevista"></div>
        </div>
    </section>
    @endif

    <section class="table-frame" aria-labelledby="h-hist">
        <div class="panel-head"><h2 id="h-hist">Historial de riegos</h2></div>
        @if($riegos->count())
        <div class="table-scroll"><table class="tbl"><thead><tr><th>Fecha</th><th class="r">Agua</th><th>Tipo</th><th>Notas</th></tr></thead><tbody>
            @foreach($parcela->eventosRiego()->latest('fecha_riego')->limit(12)->get() as $e)
                <tr><td>{{ $e->fecha_riego->translatedFormat('j M Y, H:i') }}</td><td class="r">{{ $fmt($e->cantidad_agua) }} L</td><td>{{ ucfirst($e->tipo_riego) }}</td><td class="muted">{{ $e->notas ?? '' }}</td></tr>
            @endforeach
        </tbody></table></div>
        @else<div class="empty" style="padding:32px"><p>Todavía no hay riegos registrados.</p></div>@endif
    </section>
</div>

<aside class="stack-lg" style="min-width:0">
    <section class="panel" aria-labelledby="h-cl">
        <div class="panel-head"><h2 id="h-cl">Clima ahora</h2>@if($c)<span class="t-meta">{{ $c->fecha_registro->diffForHumans() }}</span>@endif</div>
        @if($c)
        <div class="panel-body">
            <div class="row" style="gap:16px;flex-wrap:nowrap"><img src="{{ $c->icono_url }}" alt="" width="64" height="64"><div><div class="num" style="font-size:var(--t-3xl);line-height:1">{{ number_format($c->temperatura, 0) }}&nbsp;°C</div><div class="muted">{{ $c->descripcion }}</div></div></div>
            <dl class="dl" style="margin-top:16px">
                <dt>Mínima prevista</dt><dd>{{ $c->temp_min_prevista !== null ? number_format($c->temp_min_prevista, 1, ',', '') . ' °C' : '-' }}</dd>
                <dt>Máxima prevista</dt><dd>{{ $c->temp_max_prevista !== null ? number_format($c->temp_max_prevista, 1, ',', '') . ' °C' : '-' }}</dd>
                <dt>Humedad del aire</dt><dd>{{ $c->humedad }}%</dd>
                <dt>Viento</dt><dd>{{ number_format($c->viento_velocidad * 3.6, 0) }} km/h {{ $c->viento_direccion_texto }}</dd>
                <dt>Lluvia, últimos 3 días</dt><dd>{{ number_format($c->lluvia_72h_mm, 1, ',', '') }} mm</dd>
                <dt>Lluvia prevista, 24 h</dt><dd>{{ number_format($c->lluvia_24h, 1, ',', '') }} mm ({{ $c->probabilidad_lluvia_24h }}%)</dd>
                <dt>Evapotranspiración</dt><dd>{{ $c->et0_mm !== null ? number_format($c->et0_mm, 1, ',', '') . ' mm' : '-' }}</dd>
            </dl>
        </div>
        @else
        <div class="empty" style="padding:32px 24px"><x-icono name="clima" :size="32" /><h3>{{ $parcela->tiene_gps ? 'Sin datos todavía' : 'Falta la ubicación' }}</h3><p>{{ $parcela->tiene_gps ? 'Pulsa «Actualizar clima» para traer los datos.' : 'Agrega las coordenadas GPS para ver el clima de esta parcela.' }}</p>@unless($parcela->tiene_gps)<a href="{{ route('parcelas.edit', $parcela) }}" class="btn">Agregar coordenadas</a>@endunless</div>
        @endif
    </section>

    @if($recomendaciones->count())
    <section class="panel" aria-labelledby="h-rc">
        <div class="panel-head"><h2 id="h-rc">Recomendaciones</h2><a href="{{ route('parcelas.recomendaciones', $parcela) }}" class="t-sec">Ver todas</a></div>
        @foreach($recomendaciones as $r)<div class="item" style="border-top:1px solid var(--border)"><span class="ic"><x-icono :name="$r->icono" :size="20" /></span><div class="body"><span class="title">{{ $r->titulo }}</span></div></div>@endforeach
    </section>
    @endif

    <section class="panel" aria-labelledby="h-cv">
        <div class="panel-head"><h2 id="h-cv">Cultivo</h2>@if($cu)<a href="{{ route('cultivos.show', $cu) }}" class="t-sec">Ver ficha</a>@endif</div>
        <div class="panel-body">
            @if($cu)
            <dl class="dl">
                <dt>Cultivo</dt><dd>{{ $cu->nombreCultivo }}</dd>
                <dt>Riego cada</dt><dd>{{ $cu->frecuenciaRiegoMin }} a {{ $cu->frecuenciaRiegoMax }} días</dd>
                <dt>Consumo</dt><dd>{{ rtrim(rtrim(number_format($cu->litrosPorM2Dia, 2, ',', ''), '0'), ',') }} L/m² al día</dd>
                <dt>Soporta</dt><dd>{{ rtrim(rtrim(number_format($cu->tempCriticaMinima, 1, ',', ''), '0'), ',') }} a {{ rtrim(rtrim(number_format($cu->tempCriticaMaxima, 1, ',', ''), '0'), ',') }} °C</dd>
                @if($parcela->fecha_siembra)<dt>Sembrado</dt><dd>{{ $parcela->fecha_siembra->translatedFormat('j M Y') }}</dd>@endif
                @if($parcela->tieneHidrogelAplicado)<dt>Hidrogel</dt><dd>{{ $parcela->hidrogel_cantidad_kg }} kg</dd>@endif
            </dl>
            @if($progresoCiclo !== null)<div style="margin-top:16px"><div class="row between t-sec"><span>Avance del ciclo</span><span class="num">{{ $progresoCiclo }}%</span></div><div class="progress" style="margin-top:6px" role="progressbar" aria-valuenow="{{ $progresoCiclo }}" aria-valuemin="0" aria-valuemax="100"><i style="width:{{ $progresoCiclo }}%"></i></div><div class="t-meta" style="margin-top:4px">{{ $parcela->dias_desde_siembra }} de {{ $cu->cicloProductivo }} días</div></div>@endif
            @else<p class="muted">Esta parcela no tiene cultivo asignado.</p>@endif
        </div>
    </section>

    <section class="panel" aria-labelledby="h-ub">
        <div class="panel-head"><h2 id="h-ub">Ubicación</h2></div>
        <div class="panel-body">
            <dl class="dl">
                <dt>Departamento</dt><dd>{{ $parcela->ubicacion?->region ?? '-' }}</dd>
                <dt>Provincia</dt><dd>{{ $parcela->ubicacion?->provincia ?? '-' }}</dd>
                <dt>Distrito</dt><dd>{{ $parcela->ubicacion?->distrito ?? '-' }}</dd>
                @if($parcela->tiene_gps)<dt>Coordenadas</dt><dd class="num">{{ number_format($parcela->latitud_efectiva, 4) }}, {{ number_format($parcela->longitud_efectiva, 4) }}</dd>@endif
                @if($parcela->altitud)<dt>Altitud</dt><dd>{{ $fmt($parcela->altitud) }} m s. n. m.</dd>@endif
            </dl>
            @if($parcela->tiene_gps)
                <p class="t-meta" style="margin-top:8px">{{ $parcela->gps_propio ? 'GPS de la propia parcela.' : 'Usando el centro del distrito.' }} <a href="https://www.openstreetmap.org/?mlat={{ $parcela->latitud_efectiva }}&mlon={{ $parcela->longitud_efectiva }}#map=15/{{ $parcela->latitud_efectiva }}/{{ $parcela->longitud_efectiva }}" target="_blank" rel="noopener">Ver en el mapa</a></p>
            @endif
        </div>
    </section>

    @if($parcela->notas_usuario)<section class="panel"><div class="panel-head"><h2>Notas</h2></div><div class="panel-body"><p class="prose">{{ $parcela->notas_usuario }}</p></div></section>@endif
</aside>
</div>

{{-- Registrar riego --}}
<dialog class="dlg" id="dlg-riego" aria-labelledby="t-riego">
    <form method="POST" action="{{ route('parcelas.registrar-riego', $parcela) }}">@csrf
        <div class="dlg-head"><h3 id="t-riego">Registrar riego</h3><p class="t-sec muted" style="margin-top:4px">{{ $parcela->nombre }}</p></div>
        <div class="dlg-body stack">
            <div class="field"><label for="r-cant">Agua aplicada (litros)</label><input id="r-cant" name="cantidad_agua" type="number" min="0" step="1" class="input" value="{{ old('cantidad_agua', (int) $calc['litros_total'] ?: '') }}" inputmode="numeric"><span class="hint">Sugerido hoy: {{ $plan['texto'] }}. Si lo dejas vacío se usa esa cantidad.</span>@error('cantidad_agua')<span class="err">{{ $message }}</span>@enderror</div>
            <div class="form-grid"><div class="field"><label for="r-fecha">Fecha</label><input id="r-fecha" name="fecha" type="date" class="input" value="{{ old('fecha', now()->toDateString()) }}" max="{{ now()->toDateString() }}">@error('fecha')<span class="err">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="r-tipo">Tipo de riego</label><select id="r-tipo" name="tipo_riego" class="select"><option value="manual">Manual</option><option value="goteo">Goteo</option><option value="aspersion">Aspersión</option></select></div></div>
            <div class="field"><label for="r-notas">Notas <span class="muted" style="font-weight:var(--w-regular)">(opcional)</span></label><textarea id="r-notas" name="notas" class="textarea" style="min-height:64px" maxlength="500"></textarea></div>
        </div>
        <div class="dlg-foot"><button type="button" class="btn" data-dlg-close>Cancelar</button><button type="submit" class="btn btn-primary" data-ocupado="Guardando...">Registrar riego</button></div>
    </form>
</dialog>
@if($errors->has('cantidad_agua') || $errors->has('fecha'))
@push('scripts')<script>document.addEventListener('DOMContentLoaded', function () { document.getElementById('dlg-riego').showModal(); });</script>@endpush
@endif
@endsection

@push('scripts')
<script src="{{ asset('js/makiyaku/charts.js') }}?v={{ @filemtime(public_path('js/makiyaku/charts.js')) }}" defer></script>
<script defer>
document.addEventListener('DOMContentLoaded', function () {
  var r = document.getElementById('ch-riego');
  if (r) MKCharts.barras(r, { labels: @json($serieRiego['labels']), series: [{ nombre: 'Agua', datos: @json($serieRiego['datos']), color: 'chart-1' }], alto: 200, unidadY: 'L', fmtY: function (v) { return v >= 1000 ? MKCharts.fnum(v / 1000) + 'k' : MKCharts.fnum(v); }, fmtTip: function (v) { return MKCharts.fnum(v, 0) + ' L'; }, maxX: 9 });
  @if($pronostico)
  var lab = @json($lbl), tit = @json($ttl), H = @json($h48);
  MKCharts.linea(document.getElementById('ch-pt'), { labels: lab, titulos: tit, series: [
    { nombre: 'Aire', datos: H.map(function (h) { return h.temperature_2m; }), color: 'chart-1', area: true },
    { nombre: 'Suelo (6 cm)', datos: H.map(function (h) { return h.soil_temperature_6cm; }), color: 'chart-3', dash: '5 4', ancho: 2 }
  ], alto: 190, unidadY: '°C', fmtY: function (v) { return MKCharts.fnum(v, 0); }, fmtTip: function (v) { return MKCharts.fnum(v, 1) + ' °C'; }, lineas: [{ valor: {{ (float) ($cu->tempCriticaMinima ?? 0) }}, texto: 'Límite del cultivo', color: 'critico' }], ahora: 0, maxX: 8 });
  MKCharts.barras(document.getElementById('ch-pl'), { labels: lab, titulos: tit, series: [{ nombre: 'Lluvia', datos: H.map(function (h) { return h.precipitation; }), color: 'chart-2' }], linea: { nombre: 'Probabilidad', datos: H.map(function (h) { return h.precipitation_probability; }), color: 'chart-3', max: 100, fmt: function (v) { return MKCharts.fnum(v, 0) + '%'; } }, alto: 140, unidadY: 'mm', fmtY: function (v) { return MKCharts.fnum(v, 1); }, fmtTip: function (v) { return MKCharts.fnum(v, 1) + ' mm'; }, maxX: 8 });
  @endif
});
</script>
@endpush
