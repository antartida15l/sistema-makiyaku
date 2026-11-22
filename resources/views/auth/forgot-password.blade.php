@extends('layouts.auth')
@section('title', 'Recuperar contraseña')
@section('content')
    <h2>Recupera tu contraseña</h2>
    <p class="sub">Escribe tu correo y te enviaremos un enlace para elegir una contraseña nueva.</p>
    @if(session('status'))<div class="flash lvl lvl-ok" role="status"><x-icono name="ok" /><div>{{ session('status') }}</div></div>@endif
    <form method="POST" action="{{ route('password.email') }}" class="stack" novalidate>
        @csrf
        <div class="field">
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autofocus @error('email') aria-invalid="true" @enderror>
            @error('email')<span class="err">{{ $message }}</span>@enderror
        </div>
        <button type="submit" class="btn btn-primary btn-block" data-ocupado="Enviando...">Enviar enlace</button>
    </form>
    <p class="auth-sep"><a href="{{ route('login') }}">Volver a entrar</a></p>
@endsection
