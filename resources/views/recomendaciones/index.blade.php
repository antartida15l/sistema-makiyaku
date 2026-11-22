@extends('layouts.app')
@section('title', 'Recomendaciones')
@section('content')
@php
    $hay = collect($filtros)->filter()->isNotEmpty();
    $cats = ['riego' => 'Riego', 'clima' => 'Clima', 'fertilizacion' => 'Abonado', 'cosecha' => 'Cosecha', 'hidrogel' => 'Hidrogel', 'mantenimiento' => 'Mantenimiento', 'plagas' => 'Plagas', 'general' => 'General'];
@endphp
<header class="page-head">
    <div><h1>Recomendaciones</h1><p>Consejos que salen del clima de cada parcela, de sus alertas y de lo que pide el cultivo. {{ $conteo['total'] }} {{ $conteo['total'] === 1 ? 'activa' : 'activas' }}@if($conteo['urgentes']), {{ $conteo['urgentes'] }} {{ $conteo['urgentes'] === 1 ? 'urgente' : 'urgentes' }}@endif.</p></div>
    <div class="head-actions"><form method="POST" action="{{ route('recomendaciones.regenerar-todas') }}">@csrf<button class="btn" type="submit" data-ocupado="Revisando..."><x-icono name="actualizar" :size="18" />Revisar de nuevo</button></form></div>
</header>

<div class="grid g-main">
<div class="stack" style="min-width:0">
    <form method="GET" class="row" role="search" aria-label="Filtros">
        <div class="segmented" role="group" aria-label="Categoría">
            <a href="{{ route('recomendaciones.todas', array_filter(array_merge($filtros, ['categoria' => null]))) }}" @if(empty($filtros['categoria'])) aria-current="true" @endif>Todas</a>
            @foreach($categorias as $k => $n)<a href="{{ route('recomendaciones.todas', array_filter(array_merge($filtros, ['categoria' => $k]))) }}" @if(($filtros['categoria'] ?? '') === $k) aria-current="true" @endif>{{ $cats[$k] ?? ucfirst($k) }} <span class="t-meta">{{ $n }}</span></a>@endforeach
        </div>
        <label class="sr-only" for="f-par">Parcela</label>
        <select id="f-par" name="parcela" class="select" style="width:auto;min-height:40px" onchange="this.form.submit()"><option value="">Todas las parcelas</option>@foreach($parcelas as $p)<option value="{{ $p->id }}" @selected(($filtros['parcela'] ?? '') == $p->id)>{{ $p->nombre }}</option>@endforeach</select>
        <label class="sr-only" for="f-pri">Prioridad</label>
        <select id="f-pri" name="prioridad" class="select" style="width:auto;min-height:40px" onchange="this.form.submit()"><option value="">Cualquier prioridad</option>@foreach(['critica' => 'Urgentes', 'alta' => 'Altas', 'media' => 'Medias', 'baja' => 'Bajas'] as $k => $t)<option value="{{ $k }}" @selected(($filtros['prioridad'] ?? '') === $k)>{{ $t }}</option>@endforeach</select>
        @if(!empty($filtros['categoria']))<input type="hidden" name="categoria" value="{{ $filtros['categoria'] }}">@endif
        @if($hay)<a href="{{ route('recomendaciones.todas') }}" class="btn btn-ghost btn-sm">Limpiar filtros</a>@endif
    </form>

    @if($recomendaciones->count())
        @include('recomendaciones._lista', ['mostrarParcela' => true])
    @else
        <section class="panel"><div class="empty">
            @if($hay)<x-icono name="buscar" :size="40" :stroke="1.5" /><h3>Ninguna recomendación coincide</h3><p>Prueba con otra categoría o parcela.</p><a href="{{ route('recomendaciones.todas') }}" class="btn">Limpiar filtros</a>
            @elseif($parcelas->isEmpty())<x-icono name="consejo" :size="40" :stroke="1.5" /><h3>Todavía no hay recomendaciones</h3><p>Crea una parcela y Makiyaku empezará a darte consejos según su clima y su cultivo.</p><a href="{{ route('parcelas.create') }}" class="btn btn-primary">Crear parcela</a>
            @else<x-icono name="ok" :size="40" :stroke="1.5" /><h3>Estás al día</h3><p>No hay recomendaciones pendientes. Aparecerán cuando el clima o el cultivo lo pidan.</p>@endif
        </div></section>
    @endif
</div>

<aside class="stack-lg" style="min-width:0">
    <section class="panel"><div class="panel-head"><h2>Últimas cerradas</h2></div>
        @forelse($historico as $h)
            <div class="item" style="border-top:1px solid var(--border)"><span class="ic"><x-icono :name="$h->aplicada ? 'ok' : 'cerrar'" :size="18" /></span><div class="body"><span class="title" style="font-size:var(--t-sec)">{{ $h->titulo }}</span><br><span class="t-meta">{{ $h->parcela->nombre ?? '' }}, {{ $h->aplicada ? 'aplicada' : 'descartada' }} {{ $h->updated_at->diffForHumans() }}</span></div></div>
        @empty<div class="panel-body"><p class="t-sec muted">Aquí verás las que marques como aplicadas o descartadas.</p></div>@endforelse
    </section>
</aside>
</div>
@endsection
