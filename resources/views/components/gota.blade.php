@props(['nivel' => 50, 'tono' => 'medio'])
{{--
    Gota: medidor de balance hidrico. nivel = % de agua que le queda al suelo (0-100).
    El color sigue la escala de alertas (critico, alto, medio, ok).
--}}
@php
    $n = max(0, min(100, (int) $nivel));
    $id = 'gt' . substr(md5(uniqid('', true)), 0, 6);
    $y = 68 - ($n / 100) * 62;
@endphp
<svg {{ $attributes->merge(['class' => 'gota lvl lvl-' . $tono]) }} viewBox="0 0 56 72" role="img" aria-label="Agua en el suelo: {{ $n }}%">
    <defs><clipPath id="{{ $id }}"><path d="M28 4C28 4 6 30 6 46a22 22 0 0 0 44 0C50 30 28 4 28 4Z"/></clipPath></defs>
    <path class="g-shell" d="M28 4C28 4 6 30 6 46a22 22 0 0 0 44 0C50 30 28 4 28 4Z"/>
    <g clip-path="url(#{{ $id }})">
        <g class="g-wave"><path class="g-water" d="M-10 {{ $y }}q9.5 -5 19 0t19 0t19 0t19 0t19 0V80H-10Z"/></g>
    </g>
    <path class="g-line" d="M17 49a11 11 0 0 0 6 9"/>
</svg>
