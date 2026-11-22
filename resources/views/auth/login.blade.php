@extends('layouts.auth')
@section('title', 'Entrar')
@section('content')
    <h2>Entrar a tu cuenta</h2>
    <p class="sub">Revisa tus parcelas, el clima de hoy y las alertas pendientes.</p>

    @if(session('status'))
        <div class="flash lvl lvl-ok" role="status"><x-icono name="ok" /><div>{{ session('status') }}</div></div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="stack" novalidate>
        @csrf
        <div class="field">
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autofocus autocomplete="username" @error('email') aria-invalid="true" aria-describedby="email-err" @enderror>
            @error('email')<span class="err" id="email-err">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="password">Contraseña</label>
            <div class="input-wrap">
                <input id="password" name="password" type="password" class="input" required autocomplete="current-password" style="padding-right:48px">
                <button type="button" class="icon-btn toggle-pass" data-pass-toggle="password" aria-label="Mostrar contraseña"><x-icono name="ver" /></button>
            </div>
            @error('password')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div class="auth-links">
            <label class="check" style="min-height:auto"><input type="checkbox" name="remember" value="1"><span>Mantener la sesión iniciada</span></label>
            @if (Route::has('password.request'))<a href="{{ route('password.request') }}">Olvidé mi contraseña</a>@endif
        </div>
        <button type="submit" class="btn btn-primary btn-block" data-ocupado="Entrando...">Entrar al sistema</button>
    </form>
    <p class="auth-sep">¿Aún no tienes cuenta? <a href="{{ route('register') }}">Crear una cuenta</a></p>
@endsection
