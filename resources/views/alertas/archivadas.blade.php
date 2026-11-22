@extends('layouts.app')

@section('title', 'Alertas archivadas')

@push('styles')
<style>
    .arch-wrap { max-width: 980px; margin: 0 auto; padding: 24px 16px 48px; }
    .arch-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
    .arch-head h1 { font-size: 24px; margin: 0; }
    .arch-volver { padding: 8px 16px; border-radius: 10px; background: #28a745; color: #fff; text-decoration: none; font-weight: 600; }
    .arch-item { display: flex; gap: 14px; background: #fff; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; border-left: 6px solid #adb5bd; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
    .arch-item.critico { border-left-color: #dc3545; }
    .arch-item.alto { border-left-color: #ffc107; }
    .arch-item.medio { border-left-color: #17a2b8; }
    .arch-item.info { border-left-color: #007bff; }
    .arch-icono { color: #6c757d; flex-shrink: 0; }
    .arch-titulo { font-weight: 700; }
    .arch-meta { font-size: 12px; color: #6c757d; margin-top: 4px; }
    .arch-vacio { text-align: center; padding: 48px 0; color: #6c757d; }
</style>
@endpush

@section('content')
<div class="arch-wrap">
    <div class="arch-head">
        <h1>Alertas archivadas</h1>
        <a class="arch-volver" href="{{ route('alertas.index') }}">Volver a alertas</a>
    </div>

    @forelse($alertas as $alerta)
        <div class="arch-item {{ $alerta->nivel }}">
            <div class="arch-icono"><x-icono :name="$alerta->icono" :size="28" /></div>
            <div>
                <div class="arch-titulo">{{ $alerta->titulo }}</div>
                <div>{{ $alerta->mensaje }}</div>
                <div class="arch-meta">
                    {{ $alerta->parcela->nombre ?? 'Parcela eliminada' }}
                    - nivel {{ $alerta->etiqueta_nivel }}
                    - archivada {{ optional($alerta->archivada_en)->diffForHumans() }}
                    @if($alerta->archivada_por)
                        ({{ match($alerta->archivada_por) {
                            'usuario' => 'por ti',
                            'riego' => 'al registrar un riego',
                            default => 'el sistema, ya no aplicaba',
                        } }})
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="arch-vacio">No tienes alertas archivadas.</div>
    @endforelse

    <div style="margin-top: 16px;">{{ $alertas->links() }}</div>
</div>
@endsection
