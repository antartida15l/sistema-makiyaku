@php
    $u = auth()->user();
    $nav = [
        ['Principal', [
            ['dashboard', 'Panel', 'panel', 'dashboard'],
            ['parcelas.index', 'Parcelas', 'parcela', 'parcelas.*'],
            ['cultivos.index', 'Cultivos', 'cultivo', 'cultivos.*'],
            ['clima.index', 'Clima', 'clima', 'clima.*'],
        ]],
        ['Seguimiento', [
            ['alertas.index', 'Alertas', 'alerta', 'alertas.*'],
            ['recomendaciones.todas', 'Recomendaciones', 'consejo', 'recomendaciones.*'],
            ['reportes.index', 'Reportes', 'reportes', 'reportes.*'],
        ]],
        ['Cuenta', [
            ['profile.edit', 'Perfil y ajustes', 'ajustes', 'profile.*'],
        ]],
    ];
@endphp
<!DOCTYPE html>
<html lang="es" data-theme="{{ $u?->pref('tema', 'light') ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a2420">
    <title>@yield('title', 'Panel') - Makiyaku</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/makiyaku.css') }}?v={{ @filemtime(public_path('css/makiyaku.css')) }}">
    <script>(function(){try{var t=localStorage.getItem('mk-tema');if(t)document.documentElement.setAttribute('data-theme',t)}catch(e){}})()</script>
    @stack('styles')
</head>
<body class="{{ trim($__env->yieldContent('body-class')) }}">
@include('partials.logo-symbol')
<canvas id="hojas" aria-hidden="true"></canvas>
<a href="#contenido" class="sr-only">Saltar al contenido</a>

@if(session('intro'))
    <x-intro :auto="true" />
@endif

<div class="app">
    <aside class="sidebar" id="menu-lateral" aria-label="Navegación principal">
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <x-logo :size="44" :dark="true" />
            <span class="wordmark">Makiyaku</span>
        </a>
        <nav class="sidebar-nav">
            @foreach($nav as [$grupo, $items])
                <div class="nav-group">{{ $grupo }}</div>
                @foreach($items as [$ruta, $texto, $icono, $patron])
                    <a href="{{ route($ruta) }}" class="nav-link" @if(request()->routeIs($patron)) aria-current="page" @endif>
                        <x-icono :name="$icono" :size="20" />
                        <span>{{ $texto }}</span>
                        @if($ruta === 'alertas.index')
                            <span class="nav-count" data-alertas-count @if(!($alertasActivas ?? 0)) hidden @endif>{{ $alertasActivas ?? 0 }}</span>
                        @endif
                    </a>
                @endforeach
            @endforeach
        </nav>
        <div class="sidebar-foot">
            <a href="{{ route('profile.edit') }}" class="userchip nav-link" style="min-height:56px">
                <span class="avatar">{{ mb_strtoupper(mb_substr($u->name ?? 'U', 0, 1)) }}</span>
                <span class="who"><strong>{{ $u->name }}</strong><span>{{ $u->email }}</span></span>
            </a>
        </div>
    </aside>
    <div class="scrim" aria-hidden="true"></div>

    <div class="main-wrap">
        <header class="topbar">
            <button class="icon-btn menu-btn" data-nav-toggle aria-controls="menu-lateral" aria-label="Abrir menú"><x-icono name="menu" :size="22" /></button>
            <div class="crumb"><strong>@yield('title', 'Panel')</strong></div>
            <div class="spacer"></div>
            <button class="icon-btn" data-sonido-toggle aria-pressed="true" aria-label="Silenciar alertas" title="Sonido de alertas">
                <span class="i-on"><x-icono name="sonido" :size="20" /></span><span class="i-off hide"><x-icono name="silencio" :size="20" /></span>
            </button>
            <button class="icon-btn" data-tema-toggle aria-pressed="false" aria-label="Cambiar a tema oscuro" title="Tema">
                <span class="i-luna"><x-icono name="luna" :size="20" /></span><span class="i-sol hide"><x-icono name="sol" :size="20" /></span>
            </button>
            <a href="{{ route('alertas.index') }}" class="icon-btn" aria-label="Ver alertas" title="Alertas">
                <x-icono name="alerta" :size="20" />
                <span class="badge-dot" data-alertas-count @if(!($alertasActivas ?? 0)) hidden @endif>{{ $alertasActivas ?? 0 }}</span>
            </a>
            <div class="menu">
                <button class="icon-btn" data-menu aria-haspopup="true" aria-expanded="false" aria-label="Menú de {{ $u->name }}"><span class="avatar" style="width:32px;height:32px;font-size:14px">{{ mb_strtoupper(mb_substr($u->name ?? 'U', 0, 1)) }}</span></button>
                <div class="menu-pop" hidden>
                    <div style="padding:8px 12px"><strong class="t-sec" style="color:var(--ink)">{{ $u->name }}</strong><div class="t-meta">{{ $u->email }}</div></div>
                    <div class="menu-sep"></div>
                    <a class="menu-item" href="{{ route('profile.edit') }}"><x-icono name="usuario" :size="18" />Perfil y ajustes</a>
                    <a class="menu-item" href="{{ route('intro.ver') }}"><x-icono name="play" :size="18" />Ver la presentación</a>
                    <div class="menu-sep"></div>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="menu-item" type="submit"><x-icono name="salir" :size="18" />Cerrar sesión</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="page" id="contenido">
            @foreach (['success' => ['ok', 'ok'], 'status' => ['ok', 'ok'], 'error' => ['critico', 'aviso'], 'warning' => ['alto', 'aviso']] as $clave => [$nivel, $ico])
                @if(session($clave) && is_string(session($clave)))
                    <div class="flash lvl lvl-{{ $nivel }}" role="{{ $clave === 'error' ? 'alert' : 'status' }}"><x-icono :name="$ico" :size="20" /><div>{{ session($clave) }}</div></div>
                @endif
            @endforeach
            @yield('content')
        </main>
    </div>
</div>

<div class="toasts" aria-live="polite"></div>

<script>
window.MK_CFG = {
    csrf: @json(csrf_token()),
    rutas: {
        recientes: @json(route('alertas.recientes')),
        contador: @json(route('alertas.contador')),
        preferencias: @json(route('preferencias.guardar')),
    },
    prefs: @json($u?->preferenciasPublicas() ?? []),
};
</script>
<script src="{{ asset('js/makiyaku/particulas.js') }}?v={{ @filemtime(public_path('js/makiyaku/particulas.js')) }}" defer></script>
<script src="{{ asset('js/makiyaku/sonidos.js') }}?v={{ @filemtime(public_path('js/makiyaku/sonidos.js')) }}" defer></script>
<script src="{{ asset('js/makiyaku/app.js') }}?v={{ @filemtime(public_path('js/makiyaku/app.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
