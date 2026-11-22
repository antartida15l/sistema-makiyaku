<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0a2420">
<title>{{ $titulo ?? 'Makiyaku' }}</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="stylesheet" href="{{ asset('css/makiyaku.css') }}?v={{ @filemtime(public_path('css/makiyaku.css')) }}">
<script>(function(){try{var t=localStorage.getItem('mk-tema');if(t)document.documentElement.setAttribute('data-theme',t)}catch(e){}})()</script>
