@extends('layouts.app')
@section('title', 'Editar ' . $cultivo->nombreCultivo)
@section('content')
<header class="page-head"><div><h1>Editar cultivo</h1><p>{{ $cultivo->nombreCultivo }}. Los cambios se aplican a todas las parcelas que lo usan.</p></div></header>
<div style="max-width:880px">@include('cultivos._form')</div>
@endsection
