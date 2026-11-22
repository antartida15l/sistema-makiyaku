@extends('layouts.app')
@section('title', 'Parcelas')

@section('content')
@php
    $nivelTxt = ['critico' => 'Urgente', 'alto' => 'Hoy', 'medio' => 'Pronto', 'info' => 'Aviso', 'ok' => 'Al día'];
    $hayFiltros = collect($filtros)->filter(fn ($v, $k) => $v !== null && $v !== '' && $k !== 'orden')->isNotEmpty();
@endphp

<header class="page-head">
    <div>
        <h1>Parcelas</h1>
        <p>{{ $resumen['total'] }} {{ $resumen['total'] === 1 ? 'parcela' : 'parcelas' }}, {{ number_format($resumen['area'] / 10000, 2, ',', '.') }} ha activas.
            @if($resumen['riego_urgente']) <strong>{{ $resumen['riego_urgente'] }}</strong> necesitan riego. @endif</p>
    </div>
    <div class="head-actions">
        @if($resumen['papelera'])<a href="{{ route('parcelas.trashed') }}" class="btn btn-ghost"><x-icono name="borrar" :size="18" />Papelera ({{ $resumen['papelera'] }})</a>@endif
        <a href="{{ route('parcelas.create') }}" class="btn btn-primary"><x-icono name="mas" :size="18" />Nueva parcela</a>
    </div>
</header>

<div class="table-frame">
    <form method="GET" class="toolbar" id="filtros" role="search">
        <div class="search">
            <x-icono name="buscar" :size="18" />
            <label for="q" class="sr-only">Buscar parcela</label>
            <input id="q" name="q" type="search" class="input" placeholder="Buscar por nombre" value="{{ $filtros['q'] ?? '' }}">
        </div>
        <label class="sr-only" for="f-estado">Estado</label>
        <select id="f-estado" name="estado" class="select" style="width:auto" data-auto>
            <option value="">Todos los estados</option>
            @foreach(['activa' => 'Activas', 'inactiva' => 'Inactivas', 'mantenimiento' => 'En mantenimiento'] as $k => $v)<option value="{{ $k }}" @selected(($filtros['estado'] ?? '') === $k)>{{ $v }}</option>@endforeach
        </select>
        <label class="sr-only" for="f-cultivo">Cultivo</label>
        <select id="f-cultivo" name="cultivo" class="select" style="width:auto" data-auto>
            <option value="">Todos los cultivos</option>
            @foreach($cultivos as $c)<option value="{{ $c->idCultivo }}" @selected(($filtros['cultivo'] ?? '') == $c->idCultivo)>{{ $c->nombreCultivo }}</option>@endforeach
        </select>
        <label class="sr-only" for="f-orden">Ordenar</label>
        <select id="f-orden" name="orden" class="select" style="width:auto" data-auto>
            @foreach(['urgencia' => 'Más urgentes primero', 'nombre' => 'Nombre', 'area' => 'Mayor superficie', 'reciente' => 'Más recientes'] as $k => $v)<option value="{{ $k }}" @selected(($filtros['orden'] ?? 'urgencia') === $k)>{{ $v }}</option>@endforeach
        </select>
        <button class="btn" type="submit">Buscar</button>
        @if($hayFiltros)<a href="{{ route('parcelas.index') }}" class="btn btn-ghost">Limpiar filtros</a>@endif
    </form>

    @if($parcelas->count())
    <div class="table-scroll">
        <table class="tbl">
            <caption class="sr-only">Lista de parcelas</caption>
            <thead><tr><th scope="col" style="width:72px"><span class="sr-only">Agua</span></th><th scope="col">Parcela</th><th scope="col" class="r">Superficie</th><th scope="col">Riego</th><th scope="col">Clima ahora</th><th scope="col">Alertas</th><th scope="col"><span class="sr-only">Acciones</span></th></tr></thead>
            <tbody>
            @foreach($parcelas as $p)
                @php $a = $p->alertas->where('grupo', 'riego')->first(); $c = $p->climaActual; $otras = $p->alertas->where('grupo', '!=', 'riego'); @endphp
                <tr>
                    <td><a href="{{ route('parcelas.show', $p) }}" aria-label="Abrir {{ $p->nombre }}"><x-gota :nivel="$p->nivel_agua" :tono="$p->tono_riego" style="width:40px;height:52px" /></a></td>
                    <td>
                        <a href="{{ route('parcelas.show', $p) }}" style="color:var(--ink);font-weight:var(--w-medium);font-size:var(--t-body)">{{ $p->nombre }}</a>
                        <div class="t-sec muted">{{ $p->cultivo?->nombreCultivo ?? 'Sin cultivo' }}@if($p->estado !== 'activa'), {{ $p->estado }}@endif</div>
                    </td>
                    <td class="r">{{ number_format($p->area, 0, ',', '.') }} m²</td>
                    <td>
                        <span class="tag lvl lvl-{{ $a ? $a->nivel : 'ok' }}">{{ $a ? ($nivelTxt[$a->nivel] ?? 'Aviso') : 'Al día' }}</span>
                        <div class="t-meta" style="margin-top:4px">@if($p->proximo_riego_estimado) Próximo: {{ $p->proximo_riego_estimado->translatedFormat('j M') }} @else Sin fecha @endif</div>
                    </td>
                    <td>@if($c)<span class="num" style="font-size:var(--t-lg);font-weight:var(--w-semibold)">{{ number_format($c->temperatura, 0) }}&nbsp;°C</span><div class="t-meta">{{ $c->descripcion }}</div>@else<span class="t-meta">{{ $p->tiene_gps ? 'Sin datos aún' : 'Sin GPS' }}</span>@endif</td>
                    <td>@forelse($otras as $o)<span class="tag lvl lvl-{{ $o->nivel }}" style="margin:0 4px 4px 0" title="{{ $o->titulo }}"><x-icono :name="$o->icono" :size="14" />{{ \Illuminate\Support\Str::limit($o->titulo, 22) }}</span>@empty<span class="t-meta">Ninguna</span>@endforelse</td>
                    <td class="r" style="white-space:nowrap"><a class="btn btn-sm" href="{{ route('parcelas.show', $p) }}">Abrir</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @elseif($hayFiltros)
        <div class="empty"><x-icono name="buscar" :size="40" :stroke="1.5" /><h3>Ninguna parcela coincide</h3><p>Prueba con otro nombre o quita algún filtro.</p><a href="{{ route('parcelas.index') }}" class="btn">Limpiar filtros</a></div>
    @else
        <div class="empty"><x-icono name="parcela" :size="40" :stroke="1.5" /><h3>Aún no tienes parcelas</h3><p>Registra tu primera parcela para ver su clima, saber cuándo regar y recibir alertas.</p><a href="{{ route('parcelas.create') }}" class="btn btn-primary">Crear parcela</a></div>
    @endif
    {{ $parcelas->links() }}
</div>
@endsection

@push('scripts')
<script>document.querySelectorAll('#filtros [data-auto]').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });</script>
@endpush
