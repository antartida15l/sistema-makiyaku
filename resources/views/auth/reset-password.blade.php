@extends('layouts.auth')
@section('title', 'Nueva contraseña')
@section('content')
    <h2>Elige una contraseña nueva</h2>
    <p class="sub">Usa al menos 8 caracteres.</p>
    <form method="POST" action="{{ route('password.store') }}" class="stack" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="field">
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" class="input" value="{{ old('email', $request->email) }}" required autocomplete="username">
            @error('email')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="password">Contraseña nueva</label>
            <input id="password" name="password" type="password" class="input" required autofocus autocomplete="new-password">
            @error('password')<span class="err">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Repite la contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block" data-ocupado="Guardando...">Guardar contraseña</button>
    </form>
@endsection
