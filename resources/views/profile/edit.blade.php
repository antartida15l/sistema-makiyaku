@extends('layouts.app')
@section('title', 'Perfil y ajustes')
@section('content')
@php $pref = $user->preferenciasPublicas(); @endphp
<header class="page-head"><div><h1>Perfil y ajustes</h1><p>Tus datos, tu contraseña y cómo quieres que se vea y suene Makiyaku.</p></div></header>

<div class="stack-lg" style="max-width:760px">
    <section class="panel" aria-labelledby="h-datos">
        <form method="POST" action="{{ route('profile.update') }}" novalidate>@csrf @method('PATCH')
            <div class="form-section"><h2 id="h-datos">Tus datos</h2><p class="t-sec muted" style="margin-bottom:16px">Usamos tu correo para entrar a tu cuenta y recuperar la contraseña.</p>
                <div class="form-grid">
                    <x-campo name="name" label="Nombre" :value="$user->name" required autocomplete="name" />
                    <x-campo name="email" label="Correo electrónico" type="email" :value="$user->email" required autocomplete="username" />
                </div></div>
            <div class="form-actions"><button class="btn btn-primary" type="submit" data-ocupado="Guardando...">Guardar datos</button></div>
        </form>
    </section>

    <section class="panel" aria-labelledby="h-apar">
        <div class="form-section"><h2 id="h-apar">Apariencia y sonido</h2><p class="t-sec muted" style="margin-bottom:16px">Se guardan al instante y te acompañan en cualquier dispositivo.</p>
            <div class="stack">
                <div class="row between"><div><div class="label">Tema</div><div class="t-sec muted">Claro para el día, oscuro para la noche.</div></div>
                    <div class="segmented" role="group" aria-label="Tema"><button type="button" data-tema="light">Claro</button><button type="button" data-tema="dark">Oscuro</button></div></div>
                <label class="switch" style="justify-content:space-between;width:100%"><span><span class="label" style="display:block">Presentación al entrar</span><span class="t-sec muted">Un video corto con hojas y sonido cada vez que inicias sesión.</span></span><input type="checkbox" data-pref="intro" @checked($pref['intro'])><span class="track"></span></label>
                <label class="switch" style="justify-content:space-between;width:100%"><span><span class="label" style="display:block">Hojas flotantes</span><span class="t-sec muted">Hojas suaves al fondo de la aplicación.</span></span><input type="checkbox" data-pref="hojas" @checked($pref['hojas'])><span class="track"></span></label>
                <label class="switch" style="justify-content:space-between;width:100%"><span><span class="label" style="display:block">Sonido de alertas</span><span class="t-sec muted">Una señal distinta según el nivel de la alerta.</span></span><input type="checkbox" id="p-sonido" @checked($pref['sonido'])><span class="track"></span></label>
                <div class="field"><label for="p-vol">Volumen</label><input id="p-vol" type="range" min="0" max="100" step="5" class="range" value="{{ (int) ($pref['volumen'] * 100) }}"></div>
                <div class="row"><button type="button" class="btn btn-sm" data-probar="info">Probar informativa</button><button type="button" class="btn btn-sm" data-probar="alto">Probar alta</button><button type="button" class="btn btn-sm" data-probar="critico">Probar crítica</button><a href="{{ route('intro.ver') }}" class="btn btn-sm"><x-icono name="play" :size="16" />Ver la presentación</a></div>
            </div></div>
    </section>

    <section class="panel" aria-labelledby="h-pass">
        <form method="POST" action="{{ route('password.update') }}" novalidate>@csrf @method('PUT')
            <div class="form-section"><h2 id="h-pass">Contraseña</h2><p class="t-sec muted" style="margin-bottom:16px">Usa al menos 8 caracteres.</p>
                <div class="stack" style="max-width:420px">
                    @php $e = $errors->updatePassword; @endphp
                    <div class="field"><label for="current_password">Contraseña actual</label><input id="current_password" name="current_password" type="password" class="input" autocomplete="current-password">@if($e->has('current_password'))<span class="err">{{ $e->first('current_password') }}</span>@endif</div>
                    <div class="field"><label for="n-pass">Contraseña nueva</label><input id="n-pass" name="password" type="password" class="input" autocomplete="new-password">@if($e->has('password'))<span class="err">{{ $e->first('password') }}</span>@endif</div>
                    <div class="field"><label for="n-pass2">Repite la contraseña nueva</label><input id="n-pass2" name="password_confirmation" type="password" class="input" autocomplete="new-password"></div>
                </div></div>
            <div class="form-actions"><button class="btn btn-primary" type="submit" data-ocupado="Guardando...">Cambiar contraseña</button></div>
        </form>
    </section>

    <section class="panel" aria-labelledby="h-del" style="border-color:color-mix(in srgb,var(--critico) 35%,transparent)">
        <div class="form-section"><h2 id="h-del">Eliminar la cuenta</h2><p class="t-sec muted" style="margin-bottom:16px;max-width:60ch">Se borrarán tus parcelas, riegos, alertas y reportes. No se puede deshacer. Si solo quieres dejar de recibir avisos, desactiva el sonido o archiva las alertas.</p>
            <button type="button" class="btn btn-danger" data-dlg-open="dlg-borrar">Eliminar mi cuenta</button></div>
    </section>
</div>

<dialog class="dlg" id="dlg-borrar" aria-labelledby="t-borrar">
    <form method="POST" action="{{ route('profile.destroy') }}">@csrf @method('DELETE')
        <div class="dlg-head"><h3 id="t-borrar">¿Eliminar tu cuenta?</h3></div>
        <div class="dlg-body stack"><p class="muted">Escribe tu contraseña para confirmar. Se borrarán todos tus datos.</p>
            <div class="field"><label for="del-pass">Contraseña</label><input id="del-pass" name="password" type="password" class="input" autocomplete="current-password" required>@if($errors->userDeletion->has('password'))<span class="err">{{ $errors->userDeletion->first('password') }}</span>@endif</div></div>
        <div class="dlg-foot"><button type="button" class="btn" data-dlg-close>Cancelar</button><button type="submit" class="btn btn-danger">Eliminar cuenta</button></div>
    </form>
</dialog>
@if($errors->userDeletion->isNotEmpty())
@push('scripts')<script>document.addEventListener('DOMContentLoaded', function () { document.getElementById('dlg-borrar').showModal(); });</script>@endpush
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var tema = function () { return document.documentElement.getAttribute('data-theme'); };
  function marcar() { document.querySelectorAll('[data-tema]').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.tema === tema() ? 'true' : 'false'); }); }
  document.querySelectorAll('[data-tema]').forEach(function (b) { b.addEventListener('click', function () { MK.aplicarTema(b.dataset.tema); MK.guardarPrefs({ tema: b.dataset.tema }); marcar(); }); }); marcar();
  document.querySelectorAll('[data-pref]').forEach(function (i) { i.addEventListener('change', function () { var o = {}; o[i.dataset.pref] = i.checked; MK.guardarPrefs(o); MK.toast({ tipo: 'ok', titulo: 'Guardado', ms: 2500 }); }); });
  var s = document.getElementById('p-sonido'), v = document.getElementById('p-vol');
  s.addEventListener('change', function () { MKSound.fijar(s.checked); MKSound.desbloquear(); MK.guardarPrefs({ sonido: s.checked, volumen: MKSound.volumen }); });
  v.addEventListener('input', function () { MKSound.fijar(undefined, v.value / 100); MK.guardarPrefs({ volumen: v.value / 100 }); });
  document.querySelectorAll('[data-probar]').forEach(function (b) { b.addEventListener('click', function () { MKSound.desbloquear(); setTimeout(function () { MKSound.tocar(b.dataset.probar, true); }, 40); }); });
});
</script>
@endpush
