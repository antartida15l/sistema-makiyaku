@extends('layouts.auth')
@section('title', 'Crear cuenta')
@section('content')
    <h2>Crea tu cuenta</h2>
    <p class="sub">En unos minutos tendras tu primera parcela con clima y alertas.</p>

    <form method="POST" action="{{ route('register') }}" class="stack" novalidate>
        @csrf
        <div class="field">
            <label for="name">Nombre completo</label>
            <input id="name" name="name" type="text" class="input" value="{{ old('name') }}" required autofocus autocomplete="name" @error('name') aria-invalid="true" @enderror>
            @error('name')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autocomplete="username" @error('email') aria-invalid="true" @enderror>
            @error('email')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="password">Contraseña</label>
            <div class="input-wrap">
                <input id="password" name="password" type="password" class="input" required autocomplete="new-password" style="padding-right:48px" @error('password') aria-invalid="true" @enderror>
                <button type="button" class="icon-btn toggle-pass" data-pass-toggle="password" aria-label="Mostrar contraseña"><x-icono name="ver" /></button>
            </div>
            <span class="hint">Mínimo 8 caracteres.</span>
            @error('password')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Repite la contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block" data-ocupado="Creando cuenta...">Crear cuenta</button>
    </form>
    <p class="auth-sep">¿Ya tienes cuenta? <a href="{{ route('login') }}">Entrar</a></p>
@endsection
