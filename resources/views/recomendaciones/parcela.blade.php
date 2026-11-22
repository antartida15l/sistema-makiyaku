@extends('layouts.app')
@section('title', 'Recomendaciones de ' . $parcela->nombre)
@section('content')
<header class="page-head">
    <div><h1>Recomendaciones</h1><p>{{ $parcela->nombre }}, {{ $parcela->cultivo?->nombreCultivo ?? 'sin cultivo' }}.</p></div>
    <div class="head-actions">
        <a href="{{ route('parcelas.show', $parcela) }}" class="btn">Volver a la parcela</a>
        <form method="POST" action="{{ route('recomendaciones.regenerar', $parcela) }}">@csrf<button class="btn" type="submit" data-ocupado="Revisando..."><x-icono name="actualizar" :size="18" />Revisar de nuevo</button></form>
    </div>
</header>
<div class="grid g-main">
<div style="min-width:0">
    @if($recomendaciones->count())@include('recomendaciones._lista')
    @else<section class="panel"><div class="empty"><x-icono name="ok" :size="40" :stroke="1.5" /><h3>Estás al día</h3><p>No hay recomendaciones pendientes para esta parcela.</p></div></section>@endif
</div>
<aside><section class="panel"><div class="panel-head"><h2>Últimas cerradas</h2></div>
    @forelse($historico as $h)<div class="item" style="border-top:1px solid var(--border)"><span class="ic"><x-icono :name="$h->aplicada ? 'ok' : 'cerrar'" :size="18" /></span><div class="body"><span class="title" style="font-size:var(--t-sec)">{{ $h->titulo }}</span><br><span class="t-meta">{{ $h->aplicada ? 'Aplicada' : 'Descartada' }} {{ $h->updated_at->diffForHumans() }}</span></div></div>@empty<div class="panel-body"><p class="t-sec muted">Aún no cerraste ninguna.</p></div>@endforelse
</section></aside>
</div>
@endsection
