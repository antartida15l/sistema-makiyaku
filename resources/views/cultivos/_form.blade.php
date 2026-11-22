@php $c = $cultivo ?? new \App\Models\Cultivo(); $editando = $c->exists; $niveles = \App\Models\Cultivo::SENSIBILIDAD; $val = fn ($k) => old($k, $c->$k); @endphp
<form method="POST" action="{{ $editando ? route('cultivos.update', $c) : route('cultivos.store') }}" class="panel" novalidate>
    @csrf @if($editando) @method('PUT') @endif
    @if($errors->any())<div class="flash lvl lvl-critico" role="alert" style="margin:24px 24px 0"><x-icono name="aviso" /><div><strong>Revisa {{ $errors->count() === 1 ? 'este dato' : 'estos ' . $errors->count() . ' datos' }}.</strong> Los campos con problema están marcados abajo.</div></div>@endif

    <section class="form-section"><h3>Datos básicos</h3>
        <div class="form-grid">
            <x-campo name="nombreCultivo" label="Nombre" :value="$c->nombreCultivo" required autofocus maxlength="100" />
            <x-campo name="nombreCientifico" label="Nombre científico" :value="$c->nombreCientifico" opcional maxlength="150" />
            <div class="field"><label for="tipo">Tipo</label><select id="tipo" name="tipo" class="select" required>@foreach(\App\Models\Cultivo::TIPOS as $t)<option value="{{ $t }}" @selected($val('tipo') === $t)>{{ $t }}</option>@endforeach</select>@error('tipo')<span class="err">{{ $message }}</span>@enderror</div>
            <div class="field"><label for="region">Región natural <span class="muted" style="font-weight:var(--w-regular)">(opcional)</span></label><select id="region" name="region" class="select"><option value="">Cualquiera</option>@foreach(\App\Models\Cultivo::REGIONES as $t)<option value="{{ $t }}" @selected($val('region') === $t)>{{ $t }}</option>@endforeach</select></div>
        </div>
    </section>

    <section class="form-section"><h3>Agua</h3><p class="t-sec muted">Estos valores se usan para calcular cuándo regar y cuántos litros aplicar.</p>
        <div class="form-grid">
            <x-campo name="frecuenciaRiegoMin" label="Regar cada (mínimo)" type="number" min="1" :value="$c->frecuenciaRiegoMin" sufijo="días" required />
            <x-campo name="frecuenciaRiegoMax" label="Regar cada (máximo)" type="number" min="1" :value="$c->frecuenciaRiegoMax" sufijo="días" required />
            <x-campo name="litrosPorM2Dia" label="Consumo de agua" type="number" step="0.01" min="0.01" :value="$c->litrosPorM2Dia" sufijo="L/m² al día" required hint="1 litro por m² equivale a 1 mm de lluvia." />
            <x-campo name="diasCriticosSinAgua" label="Días máximos sin agua" type="number" min="1" :value="$c->diasCriticosSinAgua" sufijo="días" required hint="Pasado este tiempo la alerta de riego es urgente." />
            <x-campo name="humedadOptimaMin" label="Humedad óptima del suelo (mínima)" type="number" step="0.1" min="0" max="100" :value="$c->humedadOptimaMin" sufijo="%" required />
            <x-campo name="humedadOptimaMax" label="Humedad óptima del suelo (máxima)" type="number" step="0.1" min="0" max="100" :value="$c->humedadOptimaMax" sufijo="%" required />
            <div class="field"><label for="sensibilidadSequia">Sensibilidad a la sequía</label><select id="sensibilidadSequia" name="sensibilidadSequia" class="select" required>@foreach($niveles as $t)<option value="{{ $t }}" @selected($val('sensibilidadSequia') === $t)>{{ $t }}</option>@endforeach</select></div>
            <x-campo name="reduccionConHidrogel" label="Ahorro de agua con hidrogel" type="number" step="0.1" min="0" max="100" :value="$c->reduccionConHidrogel" sufijo="%" opcional />
            <x-campo name="etapaMayorConsumo" label="Etapa de mayor consumo" :value="$c->etapaMayorConsumo" required maxlength="255" placeholder="Ej.: Floración y llenado" class="span-2" />
            <x-campo name="diasEtapaConsumoMin" label="La etapa empieza el día" type="number" min="0" :value="$c->diasEtapaConsumoMin" opcional />
            <x-campo name="diasEtapaConsumoMax" label="y termina el día" type="number" min="0" :value="$c->diasEtapaConsumoMax" opcional />
        </div>
    </section>

    <section class="form-section"><h3>Temperatura</h3><p class="t-sec muted">Fuera de este rango el cultivo se daña. Las alertas de helada y calor usan estos límites.</p>
        <div class="form-grid">
            <x-campo name="tempCriticaMinima" label="Temperatura mínima que soporta" type="number" step="0.1" :value="$c->tempCriticaMinima" sufijo="°C" required />
            <x-campo name="tempCriticaMaxima" label="Temperatura máxima que soporta" type="number" step="0.1" :value="$c->tempCriticaMaxima" sufijo="°C" required />
            <div class="field"><label for="sensibilidadHeladas">Sensibilidad a heladas</label><select id="sensibilidadHeladas" name="sensibilidadHeladas" class="select" required>@foreach($niveles as $t)<option value="{{ $t }}" @selected($val('sensibilidadHeladas') === $t)>{{ $t }}</option>@endforeach</select></div>
            <div></div>
            <x-campo name="efectoTempMinima" label="Qué pasa si hace demasiado frío" :value="$c->efectoTempMinima" opcional maxlength="255" />
            <x-campo name="efectoTempMaxima" label="Qué pasa si hace demasiado calor" :value="$c->efectoTempMaxima" opcional maxlength="255" />
        </div>
    </section>

    <section class="form-section"><h3>Ciclo y notas</h3>
        <div class="form-grid">
            <x-campo name="cicloProductivo" label="Días de siembra a cosecha" type="number" min="1" :value="$c->cicloProductivo" opcional sufijo="días" />
            <div></div>
            <div class="field span-2"><label for="descripcion">Descripción <span class="muted" style="font-weight:var(--w-regular)">(opcional)</span></label><textarea id="descripcion" name="descripcion" class="textarea" maxlength="5000">{{ $val('descripcion') }}</textarea></div>
            <div class="field span-2"><label for="observaciones">Observaciones <span class="muted" style="font-weight:var(--w-regular)">(opcional)</span></label><textarea id="observaciones" name="observaciones" class="textarea" maxlength="5000">{{ $val('observaciones') }}</textarea></div>
        </div>
    </section>

    <div class="form-actions"><a href="{{ $editando ? route('cultivos.show', $c) : route('cultivos.index') }}" class="btn">Cancelar</a><button class="btn btn-primary" type="submit" data-ocupado="Guardando...">{{ $editando ? 'Guardar cambios' : 'Crear cultivo' }}</button></div>
</form>
