@props(['size' => 40, 'dark' => false])
{{-- Logo de Makiyaku (simbolo definido una vez por pagina en partials/logo-symbol). $dark = sobre fondo oscuro. --}}
<svg {{ $attributes->class(['logo', 'on-dark' => $dark]) }} width="{{ round($size * 1221 / 1400) }}" height="{{ $size }}" viewBox="0 0 1221 1400" role="img" aria-label="Makiyaku"><use href="#mk-logo"/></svg>
