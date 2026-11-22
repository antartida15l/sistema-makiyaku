@extends('layouts.app')
@section('title', 'Editar ' . $parcela->nombre)
@section('content')
<header class="page-head">
    <div><h1>Editar parcela</h1><p>{{ $parcela->nombre }}</p></div>
    <div class="head-actions">
        <form method="POST" action="{{ route('parcelas.destroy', $parcela) }}" data-confirmar="¿Mover «{{ $parcela->nombre }}» a la papelera?" data-confirmar-texto="Dejará de generar alertas y clima. Puedes restaurarla después desde la papelera." data-confirmar-verbo="Mover a la papelera">@csrf @method('DELETE')
            <button type="submit" class="btn btn-danger"><x-icono name="borrar" :size="18" />Mover a la papelera</button>
        </form>
    </div>
</header>
<div style="max-width:880px">@include('parcelas._form')</div>
@endsection
