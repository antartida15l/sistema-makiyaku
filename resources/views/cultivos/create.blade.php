@extends('layouts.app')
@section('title', 'Nuevo cultivo')
@section('content')
<header class="page-head"><div><h1>Nuevo cultivo</h1><p>Define cuánta agua pide y qué temperaturas soporta. Quedará disponible para todas tus parcelas.</p></div></header>
<div style="max-width:880px">@include('cultivos._form')</div>
@endsection
