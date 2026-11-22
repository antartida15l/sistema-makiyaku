@extends('layouts.auth')
@section('title', 'Verifica tu correo')
@section('content')
    <h2>Verifica tu correo</h2>
    <p class="sub">Te enviamos un enlace de confirmación. Ábrelo para activar tu cuenta; si no llega, podemos enviarlo otra vez.</p>
    @if (session('status') == 'verification-link-sent')
        <div class="flash lvl lvl-ok" role="status"><x-icono name="ok" /><div>Enviamos un nuevo enlace a tu correo.</div></div>
    @endif
    <div class="stack">
        <form method="POST" action="{{ route('verification.send') }}">@csrf
            <button type="submit" class="btn btn-primary btn-block" data-ocupado="Enviando...">Reenviar correo</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit" class="btn btn-ghost btn-block">Cerrar sesión</button>
        </form>
    </div>
@endsection
