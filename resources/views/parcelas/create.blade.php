@extends('layouts.app')
@section('title', 'Nueva parcela')
@section('content')
<header class="page-head">
    <div><h1>Nueva parcela</h1><p>Con esto Makiyaku calcula el riego y trae el clima de tu terreno.</p></div>
</header>
<div style="max-width:880px">@include('parcelas._form')</div>
@endsection
