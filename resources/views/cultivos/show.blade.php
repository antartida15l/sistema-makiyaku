@extends('layouts.app')
@section('title', $cultivo->nombreCultivo)
@section('content')
@php
    $n = fn ($v, $d = 1) => rtrim(rtrim(number_format($v, $d, ',', ''), '0'), ',');
    $niv = ['Muy Baja' => 1, 'Baja' => 2, 'Media' => 3, 'Media-Alta' => 4, 'Alta' => 5, 'Muy Alta' => 6];
    $medidor = function ($valor) use ($niv) { $k = $niv[$valor] ?? 3; return $k; };
    // escala de temperatura de -15 a 45 C
    $pos = fn ($t) => max(0, min(100, ($t + 15) / 60 * 100));
@endphp
<header class="page-head">
    <div><h1>{{ $cultivo->nombreCultivo }}</h1><p><i>{{ $cultivo->nombreCientifico }}</i>@if($cultivo->nombreCientifico), @endif{{ $cultivo->tipo }}@if($cultivo->region), {{ $cultivo->region }}@endif</p></div>
    <div class="head-actions">
        <a href="{{ route('cultivos.edit', $cultivo) }}" class="btn"><x-icono name="editar" :size="18" />Editar</a>
        <form method="POST" action="{{ route('cultivos.destroy', $cultivo) }}" data-confirmar="¿Eliminar el cultivo «{{ $cultivo->nombreCultivo }}»?" data-confirmar-texto="Se quitará del catálogo. Si hay parcelas que lo usan, no se podrá eliminar." data-confirmar-verbo="Eliminar cultivo">@csrf @method('DELETE')<button class="btn btn-danger" type="submit"><x-icono name="borrar" :size="18" />Eliminar</button></form>
        <a href="{{ route('parcelas.create') }}" class="btn btn-primary">Crear parcela con este cultivo</a>
    </div>
</header>

<div class="grid g-main">
<div class="stack-lg" style="min-width:0">
    @if($cultivo->descripcion)<section class="panel"><div class="panel-body"><p class="prose">{{ $cultivo->descripcion }}</p></div></section>@endif

    <section class="panel" aria-labelledby="h-r">
        <div class="panel-head"><h2 id="h-r">Agua</h2></div>
        <div class="panel-body grid g-3" style="gap:24px">
            <div class="figure"><span class="l">Riego cada</span><span class="v">{{ $cultivo->frecuenciaRiegoMin }} a {{ $cultivo->frecuenciaRiegoMax }}</span><span class="d">días</span></div>
            <div class="figure"><span class="l">Consumo</span><span class="v">{{ $n($cultivo->litrosPorM2Dia, 2) }}</span><span class="d">litros por m² al día</span></div>
            <div class="figure"><span class="l">Máximo sin agua</span><span class="v">{{ $cultivo->diasCriticosSinAgua }}</span><span class="d">días antes de dañarse</span></div>
        </div>
        <dl class="dl" style="padding:0 24px 24px">
            <dt>Humedad óptima del suelo</dt><dd>{{ $n($cultivo->humedadOptimaMin, 0) }}% a {{ $n($cultivo->humedadOptimaMax, 0) }}%</dd>
            <dt>Etapa de mayor consumo</dt><dd>{{ $cultivo->etapaMayorConsumo }}@if($cultivo->diasEtapaConsumoMin !== null) (días {{ $cultivo->diasEtapaConsumoMin }} a {{ $cultivo->diasEtapaConsumoMax }})@endif</dd>
            <dt>Ahorro con hidrogel</dt><dd>{{ $cultivo->reduccionConHidrogel ? $n($cultivo->reduccionConHidrogel, 1) . '%' : 'No indicado' }}</dd>
            <dt>Sensibilidad a la sequía</dt><dd><span class="tag lvl lvl-{{ $medidor($cultivo->sensibilidadSequia) >= 5 ? 'alto' : 'info' }}">{{ $cultivo->sensibilidadSequia }}</span></dd>
        </dl>
    </section>

    <section class="panel" aria-labelledby="h-t">
        <div class="panel-head"><h2 id="h-t">Temperatura</h2><span class="tag lvl lvl-info plain">Sensibilidad a heladas: {{ $cultivo->sensibilidadHeladas }}</span></div>
        <div class="panel-body">
            <div role="img" aria-label="Rango de temperatura que soporta: de {{ $n($cultivo->tempCriticaMinima) }} a {{ $n($cultivo->tempCriticaMaxima) }} grados" style="position:relative;height:56px">
                <div style="position:absolute;left:0;right:0;top:20px;height:10px;border-radius:5px;background:linear-gradient(90deg,#4c86c6,#8cc97a 45%,#d99a1e 75%,#b93a47)"></div>
                <div style="position:absolute;left:0;width:{{ $pos($cultivo->tempCriticaMinima) }}%;top:20px;height:10px;border-radius:5px 0 0 5px;background:var(--surface);opacity:.78"></div>
                <div style="position:absolute;left:{{ $pos($cultivo->tempCriticaMaxima) }}%;right:0;top:20px;height:10px;border-radius:0 5px 5px 0;background:var(--surface);opacity:.78"></div>
                <span style="position:absolute;left:{{ $pos($cultivo->tempCriticaMinima) }}%;top:0;transform:translateX(-50%);font-size:var(--t-sec)" class="num">{{ $n($cultivo->tempCriticaMinima) }} °C</span>
                <span style="position:absolute;left:{{ $pos($cultivo->tempCriticaMaxima) }}%;top:0;transform:translateX(-50%);font-size:var(--t-sec)" class="num">{{ $n($cultivo->tempCriticaMaxima) }} °C</span>
                <span class="t-meta" style="position:absolute;left:0;bottom:0">-15 °C</span><span class="t-meta" style="position:absolute;right:0;bottom:0">45 °C</span>
            </div>
            <dl class="dl" style="margin-top:16px">
                @if($cultivo->efectoTempMinima)<dt>Si baja del mínimo</dt><dd>{{ $cultivo->efectoTempMinima }}</dd>@endif
                @if($cultivo->efectoTempMaxima)<dt>Si sube del máximo</dt><dd>{{ $cultivo->efectoTempMaxima }}</dd>@endif
            </dl>
        </div>
    </section>

    @if($cultivo->observaciones)<section class="panel"><div class="panel-head"><h2>Observaciones</h2></div><div class="panel-body"><p class="prose">{{ $cultivo->observaciones }}</p></div></section>@endif
</div>
<aside class="stack-lg" style="min-width:0">
    <section class="panel"><div class="panel-head"><h2>Ciclo</h2></div><div class="panel-body">
        @if($cultivo->cicloProductivo)<div class="figure"><span class="v">{{ $cultivo->cicloProductivo }}</span><span class="l">días de siembra a cosecha</span></div>@else<p class="muted">Sin ciclo registrado.</p>@endif
    </div></section>
    <section class="panel"><div class="panel-head"><h2>Tus parcelas con este cultivo</h2></div>
        @forelse($cultivo->parcelas as $p)
            <a href="{{ route('parcelas.show', $p) }}" class="item" style="color:inherit;border-top:1px solid var(--border)"><span class="ic"><x-icono name="parcela" :size="20" /></span><span class="body"><span class="title">{{ $p->nombre }}</span><br><span class="t-sec muted">{{ number_format($p->area, 0, ',', '.') }} m²</span></span></a>
        @empty
            <div class="empty" style="padding:32px 24px"><p>Todavía no usas este cultivo.</p><a href="{{ route('parcelas.create') }}" class="btn">Crear parcela</a></div>
        @endforelse
    </section>
</aside>
</div>
@endsection
