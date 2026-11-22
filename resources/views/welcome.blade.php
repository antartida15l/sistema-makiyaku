<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    @include('partials.head', ['titulo' => 'Makiyaku - Riego inteligente para tu parcela'])
    <meta name="description" content="Makiyaku calcula cuándo regar y cuánta agua usar según el clima real de tu parcela y lo que pide tu cultivo.">
</head>
<body>
@include('partials.logo-symbol')
<x-intro :auto="false" />

<div class="landing">
    <section class="hero">
        <canvas id="hojas-hero" aria-hidden="true"></canvas>
        <nav class="land-nav" aria-label="Principal">
            <a href="{{ url('/') }}" class="brand"><x-logo :size="48" :dark="true" />Makiyaku</a>
            <div class="row">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-leaf">Ir al panel</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-light">Entrar</a>
                @endauth
            </div>
        </nav>
        <div class="hero-in">
            <div>
                <h1>Riega solo cuando el cultivo lo necesita.</h1>
                <p class="lead">Makiyaku cruza el clima real de tu parcela con lo que pide cada cultivo. Te dice cuándo regar, cuánta agua usar y cuándo proteger de la helada.</p>
                <div class="cta">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-leaf">Ir al panel</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-leaf">Crear cuenta</a>
                        <a href="{{ route('login') }}" class="btn btn-outline-light">Entrar</a>
                    @endauth
                    <button type="button" class="btn btn-outline-light" data-intro-play><x-icono name="play" :size="18" />Ver la presentación</button>
                </div>
            </div>
            <div class="hero-card" aria-label="Ejemplo de parcelas">
                <div class="row-l">
                    <x-gota :nivel="18" tono="critico" style="width:44px;height:56px" />
                    <div><strong>Lote Alto, papa</strong><span class="s">Riega hoy: 6,2 m³ en total</span></div>
                </div>
                <div class="row-l">
                    <x-gota :nivel="62" tono="medio" style="width:44px;height:56px" />
                    <div><strong>Lote Bajo, quinua</strong><span class="s">Próximo riego en 3 días</span></div>
                </div>
                <div class="row-l">
                    <x-gota :nivel="85" tono="ok" style="width:44px;height:56px" />
                    <div><strong>Huerto, habas</strong><span class="s">Helada esta noche hacia las 5:00</span></div>
                </div>
                <p class="t-meta" style="margin-top:16px;color:var(--night-ink-2)">Ejemplo ilustrativo.</p>
            </div>
        </div>
    </section>

    <section class="land-sec">
        <h2>Datos de tu parcela, no de la ciudad más cercana</h2>
        <p class="sub">Cada parcela se consulta por sus coordenadas. El sistema entiende el clima del campo y lo traduce en decisiones simples.</p>
        <div class="cols-3">
            <div class="feat">
                <h3>Clima completo y en vivo</h3>
                <p>Temperatura, lluvia real de los últimos días, pronóstico por hora, radiación, humedad del suelo y calidad del aire, todo de Open-Meteo.</p>
            </div>
            <div class="feat">
                <h3>Agua en litros y baldes</h3>
                <p>La cantidad se calcula por cultivo, área y evapotranspiración, y descuenta la lluvia que ya mojó el suelo.</p>
            </div>
            <div class="feat">
                <h3>Alertas que no se repiten</h3>
                <p>Una alerta por tema, con un nivel claro. Solo vuelven a sonar si la situación empeora.</p>
            </div>
        </div>
    </section>

    <section class="land-sec" style="padding-top:0">
        <h2>Empieza en tres pasos</h2>
        <div class="steps">
            <div><div class="n">1</div><h3>Registra tu parcela</h3><p class="muted" style="margin-top:8px">Indica dónde está y cuántos metros cuadrados tiene.</p></div>
            <div><div class="n">2</div><h3>Elige el cultivo</h3><p class="muted" style="margin-top:8px">Hay fichas de cultivos de costa, sierra y selva.</p></div>
            <div><div class="n">3</div><h3>Recibe avisos y riega</h3><p class="muted" style="margin-top:8px">Registra cada riego y el sistema ajusta el siguiente.</p></div>
        </div>
    </section>

    <section class="band">
        <div class="land-sec">
            <h2>Pensado para zonas con internet inestable</h2>
            <p class="sub" style="color:var(--night-ink-2)">Si se cae la conexión, Makiyaku usa el último dato guardado y te avisa que puede estar atrasado. Después sigue desde donde quedó.</p>
        </div>
    </section>
    <footer class="land-foot">Makiyaku, agua en tus manos. Datos meteorológicos de Open-Meteo.</footer>
</div>

<script src="{{ asset('js/makiyaku/particulas.js') }}?v={{ @filemtime(public_path('js/makiyaku/particulas.js')) }}" defer></script>
<script src="{{ asset('js/makiyaku/sonidos.js') }}?v={{ @filemtime(public_path('js/makiyaku/sonidos.js')) }}" defer></script>
<script src="{{ asset('js/makiyaku/app.js') }}?v={{ @filemtime(public_path('js/makiyaku/app.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
