@extends('layouts.app')
@section('title', 'Reportes')
@section('content')
<header class="page-head"><div><h1>Reportes</h1><p>Genera un informe del agua que usaste, las alertas, el clima y el estado de tus cultivos. Puedes imprimirlo, guardarlo como PDF o descargarlo para Excel.</p></div></header>

@if($parcelas->isEmpty())
    <section class="panel"><div class="empty"><x-icono name="reportes" :size="40" :stroke="1.5" /><h3>Aún no hay datos para reportar</h3><p>Los reportes se arman con los riegos, alertas y clima de tus parcelas. Crea una parcela para empezar.</p><a href="{{ route('parcelas.create') }}" class="btn btn-primary">Crear parcela</a></div></section>
@else
<form method="GET" action="{{ route('reportes.ver') }}" class="panel" id="form-reporte">
    <section class="form-section">
        <h3>1. Qué quieres ver</h3>
        <div class="grid g-3" style="margin-top:16px;gap:12px" role="radiogroup" aria-label="Tipo de reporte">
            @foreach($tipos as $k => [$titulo, $desc])
                <label class="panel" style="padding:16px;cursor:pointer;display:block;position:relative">
                    <input type="radio" name="tipo" value="{{ $k }}" class="sr-only" @checked($loop->first) required>
                    <strong style="display:block;font-family:var(--font-display);font-size:var(--t-lg)">{{ $titulo }}</strong>
                    <span class="t-sec muted">{{ $desc }}</span>
                </label>
            @endforeach
        </div>
    </section>
    <section class="form-section">
        <h3>2. De qué parcela y de qué fechas</h3>
        <div class="form-grid" style="margin-top:16px">
            <div class="field"><label for="parcela">Parcela</label><select id="parcela" name="parcela" class="select"><option value="">Todas las parcelas</option>@foreach($parcelas as $p)<option value="{{ $p->id }}">{{ $p->nombre }}</option>@endforeach</select></div>
            <div class="field"><label>Periodo rápido</label><div class="segmented" id="rapido" role="group" aria-label="Periodo rápido"><button type="button" data-d="7">7 días</button><button type="button" data-d="30" aria-pressed="true">30 días</button><button type="button" data-d="90">90 días</button><button type="button" data-d="365">1 año</button></div></div>
            <div class="field"><label for="desde">Desde</label><input id="desde" name="desde" type="date" class="input" value="{{ $desde }}" max="{{ $hasta }}"></div>
            <div class="field"><label for="hasta">Hasta</label><input id="hasta" name="hasta" type="date" class="input" value="{{ $hasta }}" max="{{ $hasta }}"></div>
        </div>
    </section>
    <div class="form-actions">
        <button type="submit" formaction="{{ route('reportes.csv') }}" class="btn"><x-icono name="descargar" :size="18" />Descargar CSV</button>
        <button type="submit" class="btn btn-primary">Ver reporte</button>
    </div>
</form>
@endif
@endsection

@push('scripts')
<style>label.panel:has(input[name=tipo]:checked) { border-color: var(--accent); box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 30%, transparent); } label.panel:has(input:focus-visible) { outline: 2px solid var(--accent); outline-offset: 2px; }</style>
<script>
document.querySelectorAll('#rapido button').forEach(function (b) {
  b.addEventListener('click', function () {
    document.querySelectorAll('#rapido button').forEach(function (x) { x.setAttribute('aria-pressed', 'false'); }); b.setAttribute('aria-pressed', 'true');
    var h = new Date(document.getElementById('hasta').value || Date.now()), d = new Date(h); d.setDate(d.getDate() - (+b.dataset.d - 1));
    document.getElementById('desde').value = d.toISOString().slice(0, 10);
  });
});
</script>
@endpush
