@extends('layouts.app')

@section('title', 'Editar Parcela')

@push('styles')
<style>
    .form-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 35px;
        padding-bottom: 20px;
        border-bottom: 3px solid #f0f0f0;
    }

    .page-header-content {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .page-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #ffc107, #ff9800);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        box-shadow: 0 8px 20px rgba(255, 193, 7, 0.3);
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }

    .page-title-group h1 {
        font-size: 32px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 5px;
    }

    .page-subtitle {
        color: var(--muted);
        font-size: 15px;
        font-weight: 500;
    }

    .btn-back {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        background: var(--light);
        color: var(--dark);
        text-decoration: none;
        border-radius: 12px;
        font-weight: 700;
        transition: var(--transition);
        border: 2px solid #e9ecef;
    }

    .btn-back:hover {
        background: #e9ecef;
        border-color: #ffc107;
        transform: translateX(-5px);
        box-shadow: var(--shadow-sm);
    }

    .form-card {
        background: var(--white);
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .form-section {
        padding: 35px;
        border-bottom: 2px solid #f8f9fa;
        position: relative;
    }

    .form-section:last-child {
        border-bottom: none;
    }

    .form-section::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, #ffc107, #ff9800);
        opacity: 0;
        transition: var(--transition);
    }

    .form-section:hover::before {
        opacity: 1;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .section-icon {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #ffc107, #ff9800);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.25);
    }

    .section-title h3 {
        font-size: 20px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 3px;
    }

    .section-description {
        font-size: 13px;
        color: var(--muted);
        font-weight: 500;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: block;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 10px;
        font-size: 14px;
    }

    .required-mark {
        color: var(--danger);
        font-size: 16px;
        margin-left: 4px;
    }

    .form-control,
    .form-select {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 15px;
        transition: var(--transition);
        background: var(--white);
        color: var(--dark);
        font-weight: 500;
    }

    .form-control:focus,
    .form-select:focus {
        outline: none;
        border-color: #ffc107;
        box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.1);
        background: #fffef5;
    }

    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: var(--danger);
    }

    .invalid-feedback {
        display: block;
        color: var(--danger);
        font-size: 13px;
        font-weight: 600;
        margin-top: 8px;
        padding-left: 5px;
    }

    .form-text {
        display: block;
        font-size: 13px;
        color: var(--muted);
        margin-top: 8px;
        padding-left: 5px;
        font-weight: 500;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .checkbox-wrapper {
        background: #fffef5;
        padding: 20px;
        border-radius: 12px;
        border: 2px solid #ffeaa7;
        transition: var(--transition);
    }

    .checkbox-wrapper:hover {
        background: #fff9e6;
        border-color: #ffc107;
    }

    .form-check {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 0;
    }

    .form-check-input {
        width: 24px;
        height: 24px;
        border: 2px solid #dee2e6;
        border-radius: 6px;
        cursor: pointer;
        transition: var(--transition);
    }

    .form-check-input:checked {
        background-color: #ffc107;
        border-color: #ffc107;
    }

    .form-check-label {
        font-weight: 600;
        color: var(--dark);
        cursor: pointer;
        font-size: 15px;
    }

    .input-icon-wrapper {
        position: relative;
    }

    .input-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 20px;
        pointer-events: none;
        opacity: 0.5;
    }

    .info-box {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        border-left: 4px solid #ffc107;
        padding: 15px 20px;
        border-radius: 10px;
        margin-top: 15px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13px;
        color: #856404;
        font-weight: 600;
    }

    .info-box-icon {
        font-size: 20px;
        flex-shrink: 0;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        padding: 30px 35px;
        background: #f8f9fa;
        border-top: 2px solid #e9ecef;
    }

    .btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 28px;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
    }

    .btn-cancel {
        background: var(--light);
        color: var(--dark);
        border: 2px solid #e9ecef;
    }

    .btn-cancel:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }

    .btn-submit {
        background: linear-gradient(135deg, #ffc107, #ff9800);
        color: var(--white);
        box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        min-width: 180px;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
    }

    .alert {
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
        animation: slideInDown 0.4s;
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-danger {
        background: linear-gradient(135deg, #f8d7da, #f5c2c7);
        border: 2px solid #dc3545;
        color: #721c24;
    }

    #hidrogel-fields {
        display: none;
        animation: fadeIn 0.3s;
    }

    #hidrogel-fields.show {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }

        .form-section {
            padding: 25px 20px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column-reverse;
        }

        .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="form-container">
    {{-- HEADER --}}
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-icon"></div>
            <div class="page-title-group">
                <h1>Editar Parcela</h1>
                <p class="page-subtitle">Modifica la información de: {{ $parcela->nombre }}</p>
            </div>
        </div>
        <a href="{{ route('parcelas.show', $parcela) }}" class="btn-back">
            <span>←</span>
            <span>Volver</span>
        </a>
    </div>

    {{-- ALERTAS --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <span style="font-size: 24px;"><x-icono name="alerta" :size="20" /></span>
            <div style="flex: 1;">
                <strong style="display: block; margin-bottom: 10px;">Se encontraron errores:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- FORMULARIO --}}
    <form action="{{ route('parcelas.update', $parcela) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-card">
            {{-- INFORMACIÓN BÁSICA --}}
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon"></div>
                    <div class="section-title">
                        <h3>Información Básica</h3>
                        <p class="section-description">Datos principales de tu parcela</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="nombreParcela" class="form-label">
                        Nombre de la Parcela <span class="required-mark">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <input 
                            type="text" 
                            name="nombreParcela" 
                            id="nombreParcela" 
                            value="{{ old('nombreParcela', $parcela->nombre) }}"
                            class="form-control @error('nombreParcela') is-invalid @enderror" 
                            required
                            placeholder="Ej: Parcela Norte, Chacra 1..."
                        >
                        
                    </div>
                    @error('nombreParcela')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="area" class="form-label">
                            Área (m²) <span class="required-mark">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="number" 
                                name="area" 
                                id="area" 
                                value="{{ old('area', $parcela->area) }}"
                                step="0.01"
                                min="0.01"
                                class="form-control @error('area') is-invalid @enderror" 
                                required
                                placeholder="100.00"
                            >
                            
                        </div>
                        @error('area')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="idCultivo" class="form-label">
                            Tipo de Cultivo <span class="required-mark">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <select 
                                name="idCultivo" 
                                id="idCultivo" 
                                class="form-select @error('idCultivo') is-invalid @enderror"
                                required
                            >
                                <option value="">-- Seleccione --</option>
                                @foreach($cultivos as $cultivo)
                                    <option value="{{ $cultivo->idCultivo }}" 
                                            {{ old('idCultivo', $parcela->idCultivo) == $cultivo->idCultivo ? 'selected' : '' }}>
                                        {{ $cultivo->nombreCultivo }} ({{ $cultivo->tipo }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="input-icon"><x-icono name="parcela" :size="20" /></span>
                        </div>
                        @error('idCultivo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="estado" class="form-label">
                        Estado <span class="required-mark">*</span>
                    </label>
                    <select name="estado" id="estado" class="form-select @error('estado') is-invalid @enderror" required>
                        <option value="activa" {{ old('estado', $parcela->estado) == 'activa' ? 'selected' : '' }}>
                            Activa
                        </option>
                        <option value="inactiva" {{ old('estado', $parcela->estado) == 'inactiva' ? 'selected' : '' }}>
                            Inactiva
                        </option>
                        <option value="mantenimiento" {{ old('estado', $parcela->estado) == 'mantenimiento' ? 'selected' : '' }}>
                            En Mantenimiento
                        </option>
                    </select>
                    @error('estado')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- UBICACIÓN --}}
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon"><x-icono name="parcela" :size="20" /></div>
                    <div class="section-title">
                        <h3>Ubicación</h3>
                        <p class="section-description">Localización geográfica</p>
                    </div>
                </div>

                @php
                    $ubicacionObj = $parcela->ubicacion;
                @endphp

                <div class="form-row">
                    <div class="form-group">
                        <label for="region" class="form-label">
                            Región <span class="required-mark">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="region" 
                            id="region" 
                            value="{{ old('region', $ubicacionObj->region ?? 'Puno') }}"
                            class="form-control @error('region') is-invalid @enderror" 
                            required
                        >
                        @error('region')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="provincia" class="form-label">
                            Provincia <span class="required-mark">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="provincia" 
                            id="provincia" 
                            value="{{ old('provincia', $ubicacionObj->provincia ?? 'Puno') }}"
                            class="form-control @error('provincia') is-invalid @enderror" 
                            required
                        >
                        @error('provincia')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="distrito" class="form-label">
                            Distrito <span class="required-mark">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="distrito" 
                            id="distrito" 
                            value="{{ old('distrito', $ubicacionObj->distrito ?? '') }}"
                            class="form-control @error('distrito') is-invalid @enderror" 
                            required
                            placeholder="Ej: Puno, Acora..."
                        >
                        @error('distrito')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="coordenadaLatitud" class="form-label">Latitud (Opcional)</label>
                        <input 
                            type="number" 
                            name="coordenadaLatitud" 
                            id="coordenadaLatitud" 
                            value="{{ old('coordenadaLatitud', $ubicacionObj->coordenadaLatitud ?? '') }}"
                            step="0.00000001"
                            class="form-control" 
                            placeholder="-15.8402"
                        >
                    </div>

                    <div class="form-group">
                        <label for="coordenadaLongitud" class="form-label">Longitud (Opcional)</label>
                        <input 
                            type="number" 
                            name="coordenadaLongitud" 
                            id="coordenadaLongitud" 
                            value="{{ old('coordenadaLongitud', $ubicacionObj->coordenadaLongitud ?? '') }}"
                            step="0.00000001"
                            class="form-control" 
                            placeholder="-70.0219"
                        >
                    </div>
                </div>
            </div>

            {{-- FECHAS DE SIEMBRA --}}
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon"><x-icono name="ciclo" :size="20" /></div>
                    <div class="section-title">
                        <h3>Fecha de Siembra</h3>
                        <p class="section-description">Información para tracking del cultivo</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="fecha_siembra" class="form-label">Fecha de Siembra</label>
                    <input 
                        type="date" 
                        name="fecha_siembra" 
                        id="fecha_siembra" 
                        value="{{ old('fecha_siembra', $parcela->fecha_siembra ? $parcela->fecha_siembra->format('Y-m-d') : '') }}"
                        max="{{ date('Y-m-d') }}"
                        class="form-control @error('fecha_siembra') is-invalid @enderror"
                    >
                    @error('fecha_siembra')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text">Opcional: Permite calcular días desde siembra y etapa de crecimiento</small>
                </div>

                @if($parcela->fecha_siembra && $parcela->dias_desde_siembra)
                <div class="info-box">
                    <span class="info-box-icon"><x-icono name="ciclo" :size="20" /></span>
                    <span>Actualmente: {{ $parcela->dias_desde_siembra }} días desde la siembra (Etapa: {{ ucfirst($parcela->etapa_crecimiento) }})</span>
                </div>
                @endif
            </div>

            {{-- HIDROGEL --}}
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon"><x-icono name="agua" :size="20" /></div>
                    <div class="section-title">
                        <h3>Aplicación de Hidrogel</h3>
                        <p class="section-description">Reduce el consumo de agua hasta 25%</p>
                    </div>
                </div>

                <div class="checkbox-wrapper">
                    <div class="form-check">
                        <input 
                            type="checkbox" 
                            name="tieneHidrogelAplicado" 
                            id="tieneHidrogelAplicado" 
                            value="1"
                            {{ old('tieneHidrogelAplicado', $parcela->tieneHidrogelAplicado) ? 'checked' : '' }}
                            class="form-check-input"
                        >
                        <label for="tieneHidrogelAplicado" class="form-check-label">
                            Esta parcela tiene hidrogel aplicado
                        </label>
                    </div>
                </div>

                <div id="hidrogel-fields" class="{{ old('tieneHidrogelAplicado', $parcela->tieneHidrogelAplicado) ? 'show' : '' }}">
                    <div class="form-row" style="margin-top: 20px;">
                        <div class="form-group">
                            <label for="hidrogel_fecha_aplicacion" class="form-label">
                                Fecha de Aplicación
                            </label>
                            <input 
                                type="date" 
                                name="hidrogel_fecha_aplicacion" 
                                id="hidrogel_fecha_aplicacion" 
                                value="{{ old('hidrogel_fecha_aplicacion', $parcela->hidrogel_fecha_aplicacion ? $parcela->hidrogel_fecha_aplicacion->format('Y-m-d') : '') }}"
                                max="{{ date('Y-m-d') }}"
                                class="form-control @error('hidrogel_fecha_aplicacion') is-invalid @enderror"
                            >
                            @error('hidrogel_fecha_aplicacion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="hidrogel_cantidad_kg" class="form-label">
                                Cantidad (kg)
                            </label>
                            <input 
                                type="number" 
                                name="hidrogel_cantidad_kg" 
                                id="hidrogel_cantidad_kg" 
                                value="{{ old('hidrogel_cantidad_kg', $parcela->hidrogel_cantidad_kg) }}"
                                step="0.01"
                                min="0"
                                class="form-control @error('hidrogel_cantidad_kg') is-invalid @enderror"
                                placeholder="5.00"
                            >
                            @error('hidrogel_cantidad_kg')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="info-box">
                    <span class="info-box-icon"><x-icono name="consejo" :size="20" /></span>
                    <span>El hidrogel aumenta la frecuencia de riego automáticamente (+2 días)</span>
                </div>
            </div>

            {{-- NOTAS --}}
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon"></div>
                    <div class="section-title">
                        <h3>Notas y Observaciones</h3>
                        <p class="section-description">Información adicional (opcional)</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notas_usuario" class="form-label">Notas</label>
                    <textarea 
                        name="notas_usuario" 
                        id="notas_usuario" 
                        rows="4"
                        class="form-control @error('notas_usuario') is-invalid @enderror"
                        placeholder="Ej: Terreno con pendiente leve, exposición solar directa..."
                    >{{ old('notas_usuario', $parcela->notas_usuario) }}</textarea>
                    @error('notas_usuario')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text">Máximo 1000 caracteres</small>
                </div>
            </div>

            {{-- ACCIONES --}}
            <div class="form-actions">
                <a href="{{ route('parcelas.show', $parcela) }}" class="btn btn-cancel">
                    <span>✕</span>
                    <span>Cancelar</span>
                </a>
                <button type="submit" class="btn btn-submit">
                    
                    <span>Actualizar Parcela</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('tieneHidrogelAplicado');
        const hidrogelFields = document.getElementById('hidrogel-fields');
        const fechaInput = document.getElementById('hidrogel_fecha_aplicacion');
        const cantidadInput = document.getElementById('hidrogel_cantidad_kg');

        checkbox.addEventListener('change', function() {
            if (this.checked) {
                hidrogelFields.classList.add('show');
            } else {
                hidrogelFields.classList.remove('show');
                fechaInput.value = '';
                cantidadInput.value = '';
            }
        });

        // Validación en tiempo real
        const inputs = document.querySelectorAll('.form-control, .form-select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });

            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid') && this.value) {
                    this.classList.remove('is-invalid');
                }
            });
        });
    });
</script>
@endpush