@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'opcional' => false, 'id' => null, 'sufijo' => null])
@php $id = $id ?? $name; $v = old($name, $value); $err = $errors->has($name); @endphp
<div {{ $attributes->only('class')->merge(['class' => 'field']) }}>
    <label for="{{ $id }}">{{ $label }}@if($opcional) <span class="muted" style="font-weight:var(--w-regular)">(opcional)</span>@endif</label>
    <div class="row" style="flex-wrap:nowrap;gap:8px">
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : $v }}" class="input" @if($err) aria-invalid="true" aria-describedby="{{ $id }}-err" @elseif($hint) aria-describedby="{{ $id }}-hint" @endif {{ $attributes->except('class') }}>
        @if($sufijo)<span class="t-sec muted" style="white-space:nowrap">{{ $sufijo }}</span>@endif
    </div>
    @if($hint && !$err)<span class="hint" id="{{ $id }}-hint">{{ $hint }}</span>@endif
    @error($name)<span class="err" id="{{ $id }}-err">{{ $message }}</span>@enderror
</div>
