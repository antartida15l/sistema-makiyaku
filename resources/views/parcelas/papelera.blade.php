@extends('layouts.app')
@section('title', 'Papelera de parcelas')
@section('content')
<header class="page-head">
    <div><h1>Papelera</h1><p>Las parcelas eliminadas se guardan aquí. Puedes restaurarlas con todo su historial.</p></div>
    <div class="head-actions"><a href="{{ route('parcelas.index') }}" class="btn">Volver a parcelas</a></div>
</header>
<div class="table-frame">
@if($parcelas->count())
    <div class="table-scroll"><table class="tbl"><thead><tr><th>Parcela</th><th>Cultivo</th><th class="r">Superficie</th><th>Eliminada</th><th></th></tr></thead><tbody>
    @foreach($parcelas as $p)
        <tr><td><strong>{{ $p->nombre }}</strong></td><td>{{ $p->cultivo?->nombreCultivo ?? '-' }}</td><td class="r">{{ number_format($p->area, 0, ',', '.') }} m²</td><td>{{ $p->deleted_at->diffForHumans() }}</td>
        <td class="r" style="white-space:nowrap"><form method="POST" action="{{ route('parcelas.restore', $p->id) }}" style="display:inline">@csrf @method('PATCH')<button class="btn btn-sm" type="submit">Restaurar</button></form>
        <form method="POST" action="{{ route('parcelas.forceDestroy', $p->id) }}" style="display:inline" data-confirmar="¿Eliminar «{{ $p->nombre }}» para siempre?" data-confirmar-texto="Se borrarán sus riegos, clima y alertas. No se puede deshacer." data-confirmar-verbo="Eliminar para siempre">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" type="submit">Eliminar para siempre</button></form></td></tr>
    @endforeach
    </tbody></table></div>
@else
    <div class="empty"><x-icono name="borrar" :size="40" :stroke="1.5" /><h3>La papelera está vacía</h3><p>Cuando elimines una parcela aparecerá aquí.</p><a href="{{ route('parcelas.index') }}" class="btn">Volver a parcelas</a></div>
@endif
</div>
@endsection
