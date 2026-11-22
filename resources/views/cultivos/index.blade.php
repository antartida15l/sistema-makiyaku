@extends('layouts.app')
@section('title', 'Cultivos')
@section('content')
@php
    $hayFiltros = collect($filtros)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $niveles = ['Muy Baja' => 1, 'Baja' => 2, 'Media' => 3, 'Media-Alta' => 4, 'Alta' => 5, 'Muy Alta' => 6];
    $n = fn ($v) => rtrim(rtrim(number_format($v, 1, ',', ''), '0'), ',');
@endphp
<header class="page-head">
    <div><h1>Cultivos</h1><p>Fichas de {{ $total }} cultivos con lo que necesitan de agua y lo que soportan de frío y calor. Son la base de los cálculos de riego.</p></div>
    <div class="head-actions"><a href="{{ route('cultivos.create') }}" class="btn btn-primary"><x-icono name="mas" :size="18" />Nuevo cultivo</a></div>
</header>

<div class="table-frame">
    <form method="GET" class="toolbar" id="filtros" role="search">
        <div class="search"><x-icono name="buscar" :size="18" /><label for="q" class="sr-only">Buscar cultivo</label><input id="q" name="q" type="search" class="input" placeholder="Buscar por nombre" value="{{ $filtros['q'] ?? '' }}"></div>
        <label class="sr-only" for="f-tipo">Tipo</label>
        <select id="f-tipo" name="tipo" class="select" style="width:auto" data-auto><option value="">Todos los tipos</option>@foreach(\App\Models\Cultivo::TIPOS as $t)<option value="{{ $t }}" @selected(($filtros['tipo'] ?? '') === $t)>{{ $t }}</option>@endforeach</select>
        <label class="sr-only" for="f-reg">Región</label>
        <select id="f-reg" name="region" class="select" style="width:auto" data-auto><option value="">Todas las regiones</option>@foreach(\App\Models\Cultivo::REGIONES as $t)<option value="{{ $t }}" @selected(($filtros['region'] ?? '') === $t)>{{ $t }}</option>@endforeach</select>
        <label class="sr-only" for="f-seq">Sensibilidad a la sequía</label>
        <select id="f-seq" name="sequia" class="select" style="width:auto" data-auto><option value="">Cualquier sensibilidad a sequía</option>@foreach(\App\Models\Cultivo::SENSIBILIDAD as $t)<option value="{{ $t }}" @selected(($filtros['sequia'] ?? '') === $t)>Sequía: {{ $t }}</option>@endforeach</select>
        <button class="btn" type="submit">Buscar</button>
        @if($hayFiltros)<a href="{{ route('cultivos.index') }}" class="btn btn-ghost">Limpiar filtros</a>@endif
    </form>

    @if($cultivos->count())
    <div class="table-scroll"><table class="tbl">
        <caption class="sr-only">Catálogo de cultivos</caption>
        <thead><tr><th>Cultivo</th><th>Región</th><th>Riego cada</th><th class="r">Agua</th><th>Soporta</th><th>Sequía</th><th>Tus parcelas</th><th></th></tr></thead>
        <tbody>
        @foreach($cultivos as $c)
            <tr>
                <td><a href="{{ route('cultivos.show', $c) }}" style="color:var(--ink);font-weight:var(--w-medium);font-size:var(--t-body)">{{ $c->nombreCultivo }}</a><div class="t-meta"><i>{{ $c->nombreCientifico }}</i>@if($c->nombreCientifico && $c->tipo), @endif{{ $c->tipo }}</div></td>
                <td>{{ $c->region ?? '-' }}</td>
                <td>{{ $c->frecuenciaRiegoMin }} a {{ $c->frecuenciaRiegoMax }} días</td>
                <td class="r">{{ $n($c->litrosPorM2Dia) }} L/m²/día</td>
                <td>{{ $n($c->tempCriticaMinima) }} a {{ $n($c->tempCriticaMaxima) }} °C</td>
                <td><span class="tag lvl lvl-{{ ($niveles[$c->sensibilidadSequia] ?? 3) >= 5 ? 'alto' : (($niveles[$c->sensibilidadSequia] ?? 3) <= 2 ? 'ok' : 'info') }}">{{ $c->sensibilidadSequia }}</span></td>
                <td>{{ $c->mis_parcelas ?: '-' }}</td>
                <td class="r"><a class="btn btn-sm" href="{{ route('cultivos.show', $c) }}">Ver ficha</a></td>
            </tr>
        @endforeach
        </tbody></table></div>
    @elseif($hayFiltros)
        <div class="empty"><x-icono name="buscar" :size="40" :stroke="1.5" /><h3>Ningún cultivo coincide</h3><p>Prueba con otro nombre o quita algún filtro.</p><a href="{{ route('cultivos.index') }}" class="btn">Limpiar filtros</a></div>
    @else
        <div class="empty"><x-icono name="cultivo" :size="40" :stroke="1.5" /><h3>El catálogo está vacío</h3><p>Agrega un cultivo con su frecuencia de riego y sus temperaturas críticas. Lo usarás al crear parcelas.</p><a href="{{ route('cultivos.create') }}" class="btn btn-primary">Crear cultivo</a></div>
    @endif
    {{ $cultivos->links() }}
</div>
@endsection
@push('scripts')<script>document.querySelectorAll('#filtros [data-auto]').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });</script>@endpush
