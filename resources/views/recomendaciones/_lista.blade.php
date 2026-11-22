@php $prio = ['critica' => ['critico', 'Urgente'], 'alta' => ['alto', 'Alta'], 'media' => ['medio', 'Media'], 'baja' => ['info', 'Baja'], 'info' => ['info', 'Informativa']]; @endphp
<div class="panel divide">
@foreach($recomendaciones as $r)
    @php [$lv, $txt] = $prio[$r->prioridad] ?? ['info', 'Info']; @endphp
    <article class="item lvl lvl-{{ $lv }}" style="padding:20px 24px">
        <span class="ic" style="width:44px;height:44px"><x-icono :name="$r->icono" :size="22" /></span>
        <div class="body">
            <div class="row" style="gap:8px"><h3 style="font-size:var(--t-body);font-family:var(--font-ui)">{{ $r->titulo }}</h3><span class="tag lvl lvl-{{ $lv }}">{{ $txt }}</span><span class="t-meta">{{ $r->etiqueta_categoria }}</span></div>
            @if(isset($mostrarParcela) && $r->parcela)<a href="{{ route('parcelas.show', $r->parcela_id) }}" class="t-sec">{{ $r->parcela->nombre }}</a>@endif
            @php $partes = preg_split('/\s*(?:Qué hacer:)\s*/u', $r->descripcion, 2); @endphp
            <p style="margin-top:8px;max-width:72ch">{{ $partes[0] }}</p>
            @if(isset($partes[1]))
                <div style="margin-top:12px"><strong class="t-sec">Qué hacer</strong>
                    <ol style="margin:6px 0 0;padding-left:20px;max-width:70ch" class="t-sec">@foreach(array_filter(preg_split('/\s*\d\)\s*/u', $partes[1])) as $paso)<li style="margin-bottom:4px">{{ trim($paso) }}</li>@endforeach</ol></div>
            @endif
            @if($r->vigente_hasta)<p class="t-meta" style="margin-top:8px">Vigente hasta {{ $r->vigente_hasta->translatedFormat('j M, H:i') }}</p>@endif
            <div class="row" style="margin-top:16px">
                @if($r->accion_url)<a href="{{ $r->accion_url }}" class="btn btn-sm btn-primary">{{ $r->accion_texto ?? 'Abrir' }}</a>@endif
                <form method="POST" action="{{ route('recomendaciones.aplicar', $r) }}">@csrf<button class="btn btn-sm" type="submit">Marcar como aplicada</button></form>
                <form method="POST" action="{{ route('recomendaciones.descartar', $r) }}">@csrf<button class="btn btn-sm btn-ghost" type="submit">Descartar</button></form>
            </div>
        </div>
    </article>
@endforeach
</div>
