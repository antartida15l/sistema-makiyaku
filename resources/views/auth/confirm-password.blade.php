@extends('layouts.auth')
@section('title', 'Confirma tu contraseña')
@section('content')
    <h2>Confirma tu contraseña</h2>
    <p class="sub">Es una zona protegida. Escribe tu contraseña para continuar.</p>
    <form method="POST" action="{{ route('password.confirm') }}" class="stack" novalidate>
        @csrf
        <div class="field">
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" class="input" required autofocus autocomplete="current-password">
            @error('password')<span class="err">{{ $message }}</span>@enderror
        </div>
        <button type="submit" class="btn btn-primary btn-block" data-ocupado="Confirmando...">Confirmar</button>
    </form>
@endsection
