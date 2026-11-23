@props(['forzar' => false])
{{-- Presentacion de entrada (canvas + sonido sintetizado). No tiene boton para verla: es parte de la entrada al sistema. --}}
<div id="intro" role="dialog" aria-modal="true" aria-label="Bienvenida a Makiyaku" data-forzar="{{ $forzar ? '1' : '0' }}">
    <canvas aria-hidden="true"></canvas>
    <p class="intro-hint" id="intro-hint" hidden>Toca la pantalla para activar el sonido</p>
    <div class="intro-ui"><button type="button" class="btn" id="intro-saltar">Omitir</button></div>
</div>
@push('scripts')
<script src="{{ asset('js/makiyaku/intro.js') }}?v={{ @filemtime(public_path('js/makiyaku/intro.js')) }}" defer></script>
@endpush
<script>(function(){var e=document.getElementById('intro'),v=false;try{v=sessionStorage.getItem('mk-intro')==='1'}catch(x){}if(e.getAttribute('data-forzar')==='1'||!v){e.classList.add('on');e.style.background='#f6fcfa';}})();</script>
