@extends('layouts.app')
@section('title', 'Alertas archivadas')
@section('content')
<header class="page-head">
    <div><h1>Alertas archivadas</h1><p>Historial de alertas que ya cerraste o que dejaron de aplicar. Se borran solas a los 30 días.</p></div>
    <div class="head-actions"><a href="{{ route('alertas.index') }}" class="btn">Volver a alertas</a></div>
</header>
<div class="table-frame">
@if($alertas->count())
    <div class="table-scroll"><table class="tbl"><thead><tr><th>Alerta</th><th>Parcela</th><th>Nivel</th><th>Archivada</th><th>Cómo se cerró</th></tr></thead><tbody>
    @foreach($alertas as $a)
        <tr><td><div class="row" style="flex-wrap:nowrap;align-items:flex-start;gap:12px"><span class="item lvl lvl-{{ $a->nivel }}" style="padding:0"><span class="ic" style="width:32px;height:32px"><x-icono :name="$a->icono" :size="16" /></span></span><div><strong>{{ $a->titulo }}</strong><div class="t-sec muted" style="max-width:56ch">{{ \Illuminate\Support\Str::limit($a->mensaje, 120) }}</div></div></div></td>
        <td>{{ $a->parcela->nombre ?? 'Parcela eliminada' }}</td><td><span class="tag lvl lvl-{{ $a->nivel }}">{{ $a->etiqueta_nivel }}</span></td><td>{{ optional($a->archivada_en)->diffForHumans() }}</td>
        <td>{{ match($a->archivada_por) { 'usuario' => 'La archivaste tú', 'riego' => 'Al registrar un riego', default => 'Ya no aplicaba' } }}</td></tr>
    @endforeach
    </tbody></table></div>
@else
    <div class="empty"><x-icono name="archivo" :size="40" :stroke="1.5" /><h3>No hay alertas archivadas</h3><p>Cuando archives una alerta o deje de aplicar, la verás aquí.</p><a href="{{ route('alertas.index') }}" class="btn">Volver a alertas</a></div>
@endif
{{ $alertas->links() }}
</div>
@endsection
