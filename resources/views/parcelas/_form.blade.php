@php
    $editando = $parcela->exists;
    $u = $parcela->ubicacion;
    $v = fn ($campo, $defecto = null) => old($campo, $defecto);
    $departamentos = ['Amazonas','Áncash','Apurímac','Arequipa','Ayacucho','Cajamarca','Callao','Cusco','Huancavelica','Huánuco','Ica','Junín','La Libertad','Lambayeque','Lima','Loreto','Madre de Dios','Moquegua','Pasco','Piura','Puno','San Martín','Tacna','Tumbes','Ucayali'];
    $hidro = (bool) old('tieneHidrogelAplicado', $parcela->tieneHidrogelAplicado);
@endphp

<form method="POST" action="{{ $editando ? route('parcelas.update', $parcela) : route('parcelas.store') }}" class="panel" id="form-parcela" novalidate>
    @csrf
    @if($editando) @method('PUT') @endif

    @if($errors->any())
        <div class="flash lvl lvl-critico" role="alert" style="margin:24px 24px 0"><x-icono name="aviso" /><div><strong>Revisa {{ $errors->count() === 1 ? 'este dato' : 'estos ' . $errors->count() . ' datos' }}.</strong> Los campos con problema están marcados abajo.</div></div>
    @endif

    <section class="form-section">
        <h3>Datos de la parcela</h3>
        <p class="t-sec muted">El cultivo define cada cuántos días regar y cuánta agua necesita.</p>
        <div class="form-grid">
            <x-campo name="nombreParcela" label="Nombre" :value="$parcela->nombre" class="span-2" required autofocus maxlength="255" placeholder="Ej.: Lote alto" />
            <div class="field">
                <label for="idCultivo">Cultivo</label>
                <select id="idCultivo" name="idCultivo" class="select" required @error('idCultivo') aria-invalid="true" @enderror>
                    <option value="">Elige un cultivo</option>
                    @foreach($cultivos as $c)
                        <option value="{{ $c->idCultivo }}" data-riego="{{ $c->frecuenciaRiegoMin }} a {{ $c->frecuenciaRiegoMax }} días" data-litros="{{ rtrim(rtrim(number_format($c->litrosPorM2Dia, 2, ',', ''), '0'), ',') }}" data-ciclo="{{ $c->cicloProductivo }}" data-temp="{{ rtrim(rtrim(number_format($c->tempCriticaMinima, 1, ',', ''), '0'), ',') }} a {{ rtrim(rtrim(number_format($c->tempCriticaMaxima, 1, ',', ''), '0'), ',') }} °C" @selected($v('idCultivo', $parcela->idCultivo) == $c->idCultivo)>{{ $c->nombreCultivo }}@if($c->region) ({{ $c->region }})@endif</option>
                    @endforeach
                </select>
                @error('idCultivo')<span class="err">{{ $message }}</span>@enderror
                <span class="hint" id="info-cultivo" aria-live="polite"></span>
            </div>
            <x-campo name="area" label="Superficie" type="number" step="0.01" min="0.01" :value="$parcela->area" required sufijo="m²" inputmode="decimal" hint="1 hectárea son 10.000 m². " />
            @if($editando)
            <div class="field">
                <label for="estado">Estado</label>
                <select id="estado" name="estado" class="select">
                    @foreach(['activa' => 'Activa', 'inactiva' => 'Inactiva (sin alertas ni clima)', 'mantenimiento' => 'En mantenimiento'] as $k => $t)<option value="{{ $k }}" @selected($v('estado', $parcela->estado) === $k)>{{ $t }}</option>@endforeach
                </select>
            </div>
            @endif
        </div>
    </section>

    <section class="form-section">
        <h3>Ubicación</h3>
        <p class="t-sec muted">Con las coordenadas exactas, el clima y las alertas son los de tu parcela y no los del distrito.</p>
        <div class="form-grid">
            <div class="field">
                <label for="region">Departamento</label>
                <input id="region" name="region" list="lista-deptos" class="input" value="{{ $v('region', $u?->region) }}" required autocomplete="off" @error('region') aria-invalid="true" @enderror>
                <datalist id="lista-deptos">@foreach($departamentos as $d)<option value="{{ $d }}">@endforeach</datalist>
                @error('region')<span class="err">{{ $message }}</span>@enderror
            </div>
            <x-campo name="provincia" label="Provincia" :value="$u?->provincia" required maxlength="100" />
            <x-campo name="distrito" label="Distrito" :value="$u?->distrito" required maxlength="100" />
            <div></div>
            <x-campo name="latitud" label="Latitud" :value="$parcela->latitud" opcional inputmode="decimal" placeholder="-14.8833" hint="Número negativo en el sur del país." />
            <x-campo name="longitud" label="Longitud" :value="$parcela->longitud" opcional inputmode="decimal" placeholder="-70.5833" />
            <div class="span-2 row">
                <button type="button" class="btn" id="btn-gps"><x-icono name="gps" :size="18" />Usar el GPS de este dispositivo</button>
                <button type="button" class="btn" id="btn-geo"><x-icono name="buscar" :size="18" />Buscar por distrito</button>
                <span class="t-sec muted" id="msg-gps" role="status" aria-live="polite"></span>
            </div>
            @if($editando && !$parcela->gps_propio && $parcela->tiene_gps)
                <p class="t-sec muted span-2">Ahora se usan las coordenadas del distrito. Agrega las de la parcela para mayor precisión.</p>
            @endif
        </div>
    </section>

    <section class="form-section">
        <h3>Siembra y riego</h3>
        <div class="form-grid">
            <x-campo name="fecha_siembra" label="Fecha de siembra" type="date" :value="$parcela->fecha_siembra?->format('Y-m-d')" opcional max="{{ now()->toDateString() }}" hint="Sirve para saber en qué etapa está el cultivo y cuándo cosechar." />
            @unless($editando)
            <x-campo name="ultimo_riego" label="Último riego" type="date" opcional max="{{ now()->toDateString() }}" hint="Si lo dejas vacío, se asume que toca regar hoy." />
            @endunless
        </div>
    </section>

    <section class="form-section">
        <h3>Hidrogel</h3>
        <p class="t-sec muted">Si aplicaste hidrogel al suelo, el sistema reduce el agua recomendada.</p>
        <label class="switch"><input type="checkbox" name="tieneHidrogelAplicado" value="1" id="hidro" @checked($hidro)><span class="track"></span><span>Esta parcela tiene hidrogel</span></label>
        <div class="form-grid" id="hidro-campos" style="margin-top:16px" @if(!$hidro) hidden @endif>
            <x-campo name="hidrogel_fecha_aplicacion" label="Fecha de aplicación" type="date" :value="$parcela->hidrogel_fecha_aplicacion?->format('Y-m-d')" max="{{ now()->toDateString() }}" />
            <x-campo name="hidrogel_cantidad_kg" label="Cantidad aplicada" type="number" step="0.01" min="0" :value="$parcela->hidrogel_cantidad_kg" sufijo="kg" inputmode="decimal" />
        </div>
    </section>

    <section class="form-section">
        <div class="field">
            <label for="notas_usuario">Notas <span class="muted" style="font-weight:var(--w-regular)">(opcional)</span></label>
            <textarea id="notas_usuario" name="notas_usuario" class="textarea" maxlength="1000" placeholder="Tipo de suelo, pendiente, fuente de agua...">{{ $v('notas_usuario', $parcela->notas_usuario) }}</textarea>
        </div>
    </section>

    <div class="form-actions">
        <a href="{{ $editando ? route('parcelas.show', $parcela) : route('parcelas.index') }}" class="btn">Cancelar</a>
        <button type="submit" class="btn btn-primary" data-ocupado="Guardando...">{{ $editando ? 'Guardar cambios' : 'Crear parcela' }}</button>
    </div>
</form>

@push('scripts')
<script>
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var cult = $('idCultivo'), info = $('info-cultivo');
  function pintarCultivo() {
    var o = cult.options[cult.selectedIndex];
    info.textContent = o && o.value ? 'Riego cada ' + o.dataset.riego + ', unos ' + o.dataset.litros + ' L por m² al día' + (o.dataset.ciclo ? ', ciclo de ' + o.dataset.ciclo + ' días' : '') + '. Soporta ' + o.dataset.temp + '.' : '';
  }
  cult.addEventListener('change', pintarCultivo); pintarCultivo();

  var h = $('hidro'), hc = $('hidro-campos'); h.addEventListener('change', function () { hc.hidden = !h.checked; });

  var msg = $('msg-gps'), lat = $('latitud'), lon = $('longitud');
  $('btn-gps').addEventListener('click', function () {
    if (!navigator.geolocation) { msg.textContent = 'Este dispositivo no permite obtener el GPS.'; return; }
    msg.textContent = 'Buscando tu ubicación...';
    navigator.geolocation.getCurrentPosition(function (p) {
      lat.value = p.coords.latitude.toFixed(6); lon.value = p.coords.longitude.toFixed(6);
      msg.textContent = 'Ubicación obtenida con una precisión de ' + Math.round(p.coords.accuracy) + ' m. Ponte en la parcela para mayor exactitud.';
    }, function () { msg.textContent = 'No se pudo obtener la ubicación. Revisa el permiso del navegador o escribe las coordenadas.'; }, { enableHighAccuracy: true, timeout: 15000 });
  });
  $('btn-geo').addEventListener('click', function () {
    var d = $('distrito').value.trim(), p = $('provincia').value.trim(), r = $('region').value.trim();
    if (!d || !p || !r) { msg.textContent = 'Completa departamento, provincia y distrito.'; return; }
    msg.textContent = 'Buscando...';
    fetch(@json(route('api.geocodificar')), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ distrito: d, provincia: p, region: r }) })
      .then(function (x) { return x.json(); }).then(function (j) {
        if (j.success) { lat.value = j.data.latitud; lon.value = j.data.longitud; msg.textContent = j.mensaje + ' Es el centro del distrito: ajusta con el GPS si puedes.'; } else { msg.textContent = j.mensaje; }
      }).catch(function () { msg.textContent = 'No se pudo buscar. Revisa tu conexión.'; });
  });
})();
</script>
@endpush
