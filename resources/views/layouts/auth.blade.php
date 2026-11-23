<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    @include('partials.head', ['titulo' => trim($__env->yieldContent('title', 'Entrar')) . ' - Makiyaku'])
</head>
<body>
@include('partials.logo-symbol')
<canvas id="hojas" aria-hidden="true"></canvas>
<div class="auth">
    <aside class="auth-brand" aria-hidden="false">
        <canvas id="hojas-auth" aria-hidden="true"></canvas>
        <a href="{{ url('/') }}" class="brand-top" style="color:#fff"><x-logo :size="44" :mono="true" />makiyaku</a>
        <div class="lead">
            <h1>Cada gota, en el momento justo.</h1>
            <p>Makiyaku cruza el clima real de tu parcela con lo que pide cada cultivo y te dice cuándo regar, cuánta agua usar y cuándo proteger de la helada.</p>
        </div>
        <p class="foot">Hecho para el campo peruano: costa, sierra y selva.</p>
    </aside>
    <main class="auth-panel">
        <div class="auth-card">
            <a href="{{ url('/') }}" class="brand-mobile" style="color:var(--ink)"><x-logo :size="40" />makiyaku</a>
            @yield('content')
        </div>
    </main>
</div>
<script src="{{ asset('js/makiyaku/particulas.js') }}?v={{ @filemtime(public_path('js/makiyaku/particulas.js')) }}" defer></script>
<script src="{{ asset('js/makiyaku/app.js') }}?v={{ @filemtime(public_path('js/makiyaku/app.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
