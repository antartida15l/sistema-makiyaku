@props(['auto' => false])
{{-- Presentación animada (canvas + audio sintetizado). Se muestra al entrar al sistema. --}}
<div id="intro" role="dialog" aria-modal="true" aria-label="Presentación de Makiyaku" data-auto="{{ $auto ? '1' : '0' }}">
    <canvas aria-hidden="true"></canvas>
    <p class="intro-hint" id="intro-hint" hidden>Toca la pantalla para activar el sonido</p>
    <div class="intro-ui"><button type="button" class="btn" id="intro-saltar">Saltar presentación</button></div>
</div>
@push('scripts')
<script src="{{ asset('js/makiyaku/intro.js') }}?v={{ @filemtime(public_path('js/makiyaku/intro.js')) }}" defer></script>
@endpush
