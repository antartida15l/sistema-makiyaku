@props(['size' => 36, 'mono' => false])
{{-- Logo (gota de dos hojas). mono = version blanca para fondos de color. --}}
<svg {{ $attributes->class(['logo', 'logo-mono' => $mono]) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 100 100" role="img" aria-label="Makiyaku"><use href="#mk-logo"/></svg>
