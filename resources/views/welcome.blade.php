<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    @include('partials.head', ['titulo' => 'Makiyaku - Riego inteligente para tu parcela'])
    <meta name="description" content="Makiyaku calcula cuándo regar y cuánta agua usar según el clima real de tu parcela y lo que pide tu cultivo.">
</head>
<body>
@include('partials.logo-symbol')
<div class="landing">
    <section class="hero">
        <canvas id="hojas-hero" aria-hidden="true"></canvas>
        <nav class="land-nav" aria-label="Principal">
            <a href="{{ url('/') }}" class="brand"><x-logo :size="40" /><span class="wordmark">makiyaku</span></a>
            <div class="row">
                @auth<a href="{{ route('dashboard') }}" class="btn btn-primary">Ir al panel</a>
                @else<a href="{{ route('login') }}" class="btn">Entrar</a>@endauth
            </div>
        </nav>
        <div class="hero-in">
            <div>
                <h1>Riega solo cuando el cultivo lo necesita.</h1>
                <p class="lead">Makiyaku cruza el clima real de tu parcela con lo que pide cada cultivo. Te dice cuándo regar, cuánta agua usar y cuándo proteger de la helada.</p>
                <div class="cta">
                    @auth<a href="{{ route('dashboard') }}" class="btn btn-primary">Ir al panel</a>
                    @else<a href="{{ route('register') }}" class="btn btn-primary">Crear cuenta gratis</a><a href="{{ route('login') }}" class="btn">Ya tengo cuenta</a>@endauth
                </div>
                <p class="t-sec muted" style="margin-top:24px">{{ $cultivos }} cultivos de costa, sierra y selva en el catálogo{{ '' }}@if($parcelas), {{ number_format($parcelas, 0, ',', '.') }} {{ $parcelas === 1 ? 'parcela monitoreada' : 'parcelas monitoreadas' }}@endif.</p>
            </div>

            <section class="hero-card" aria-labelledby="h-vivo">
                <div class="hc-head"><strong id="h-vivo" style="font-family:var(--font-display);font-size:var(--t-lg)">Ahora en el campo peruano</strong>@if($lugares)<span class="vivo">En vivo, {{ now()->format('H:i') }}</span>@endif</div>
                @if($lugares)
                    @foreach($lugares as $l)
                        <div class="hc-row">
                            <img src="{{ asset('img/clima/' . $l['icono'] . '.svg') }}" alt="">
                            <div style="min-width:0"><strong>{{ $l['nombre'] }} <span class="t-meta" style="font-weight:var(--w-regular)">{{ $l['zona'] }}</span></strong>
                                <span class="tag lvl lvl-{{ $l['estado'][0] }}" style="margin-top:4px">{{ $l['estado'][1] }}</span></div>
                            <div><div class="t">{{ number_format($l['temp'], 0) }}°</div><div class="t-meta" style="text-align:right">@if($l['et0'] !== null)ET₀ {{ number_format($l['et0'], 1, ',', '') }} mm @endif</div></div>
                        </div>
                    @endforeach
                    <p class="t-meta" style="padding:12px 24px;margin:0;border-top:1px solid var(--border)">Datos de Open-Meteo, actualizados cada 15 minutos. ET₀: agua que pierde un cultivo de referencia en el día.</p>
                @else
                    <div class="empty" style="padding:32px 24px"><x-icono name="nube" :size="32" /><h3>Sin conexión con el servicio de clima</h3><p>En cuanto vuelva la conexión verás aquí el clima de ahora en Puno, Cusco, Huancayo, Ica, Piura y Tarapoto.</p></div>
                @endif
            </section>
        </div>
    </section>

    <section class="land-sec">
        <h2>Datos de tu parcela, no de la ciudad más cercana</h2>
        <p class="sub">Cada parcela se consulta por sus coordenadas. El sistema traduce el clima del campo en decisiones simples.</p>
        <div class="cols-3">
            <div class="feat"><h3>Clima completo y en vivo</h3><p>Pronóstico por hora y de 14 días, lluvia real, evapotranspiración, humedad y temperatura del suelo, radiación y calidad del aire.</p></div>
            <div class="feat"><h3>Agua en litros y baldes</h3><p>La cantidad se calcula por cultivo, superficie y evapotranspiración, y descuenta la lluvia que ya mojó el suelo.</p></div>
            <div class="feat"><h3>Alertas que no se repiten</h3><p>Una alerta por tema, con un nivel claro y un sonido distinto. Solo vuelven a sonar si la situación empeora.</p></div>
        </div>
    </section>

    <section class="land-sec" style="padding-top:0">
        <h2>Empieza en tres pasos</h2>
        <div class="steps">
            <div><div class="n">1</div><h3>Registra tu parcela</h3><p class="muted" style="margin-top:8px">Marca dónde está con el GPS del teléfono e indica su superficie.</p></div>
            <div><div class="n">2</div><h3>Elige el cultivo</h3><p class="muted" style="margin-top:8px">Hay {{ $cultivos }} fichas con lo que necesita cada uno de agua y temperatura.</p></div>
            <div><div class="n">3</div><h3>Recibe avisos y riega</h3><p class="muted" style="margin-top:8px">Registra cada riego y el sistema ajusta el siguiente.</p></div>
        </div>
    </section>

    <section class="band"><div class="land-sec">
        <h2>Pensado para zonas con internet inestable</h2>
        <p class="sub">Si se cae la conexión, Makiyaku usa el último dato guardado y te avisa que puede estar atrasado. Después sigue desde donde quedó.</p>
        @guest<div style="margin-top:24px"><a href="{{ route('register') }}" class="btn btn-leaf">Crear cuenta gratis</a></div>@endguest
    </div></section>
    <footer class="land-foot">makiyaku: agua en tus manos. Datos meteorológicos de Open-Meteo.</footer>
</div>
<script src="{{ asset('js/makiyaku/particulas.js') }}?v={{ @filemtime(public_path('js/makiyaku/particulas.js')) }}" defer></script>
</body>
</html>
