@extends('layouts.app')

@section('title', 'Nueva Parcela')

@push('styles')
<style>
    .form-container {
        max-width: 1100px;
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
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
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
        border-color: var(--primary);
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
        background: linear-gradient(180deg, var(--primary), var(--secondary));
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
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.25);
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
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
        background: #f8fff9;
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

    .form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-row-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
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
        background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        border-left: 4px solid #17a2b8;
        padding: 15px 20px;
        border-radius: 10px;
        margin-top: 15px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13px;
        color: #0c5460;
        font-weight: 600;
    }

    .info-box-icon {
        font-size: 20px;
        flex-shrink: 0;
    }

    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        border: 2px solid #e9ecef;
        cursor: pointer;
        transition: var(--transition);
    }

    .checkbox-wrapper:hover {
        background: #e9ecef;
        border-color: var(--primary);
    }

    .checkbox-wrapper input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
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
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: var(--white);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        min-width: 180px;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
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

    #hidrogelFields {
        margin-top: 20px;
        padding: 20px;
        background: #f8fff9;
        border-radius: 10px;
        border: 2px solid #d4edda;
    }

    /* Estilos para el indicador de geocodificación */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    input[type="number"].form-control {
        transition: all 0.3s ease;
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

        .form-row,
        .form-row-2,
        .form-row-3 {
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
            <div class="page-icon">🌾</div>
            <div class="page-title-group">
                <h1>Nueva Parcela</h1>
                <p class="page-subtitle">Registra una nueva parcela de cultivo</p>
            </div>
        </div>
        <a href="{{ route('parcelas.index') }}" class="btn-back">
            <span>←</span>
            <span>Volver</span>
        </a>
    </div>

    {{-- ALERTAS --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <span style="font-size: 24px;">⚠️</span>
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
    <form action="{{ route('parcelas.store') }}" method="POST">
        @csrf

        <div class="form-card">
            {{-- INFORMACIÓN BÁSICA --}}
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">📋</div>
                    <div class="section-title">
                        <h3>Información Básica</h3>
                        <p class="section-description">Datos generales de la parcela</p>
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
                            value="{{ old('nombreParcela') }}"
                            class="form-control @error('nombreParcela') is-invalid @enderror" 
                            required
                            placeholder="Ej: Parcela Norte, Lote 5, Terraza Alta..."
                            maxlength="255"
                        >
                        <span class="input-icon">📍</span>
                    </div>
                    @error('nombreParcela')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text">Nombre descriptivo para identificar tu parcela</small>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="area" class="form-label">
                            Área (m²) <span class="required-mark">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="number" 
                                name="area" 
                                id="area" 
                                value="{{ old('area') }}"
                                class="form-control @error('area') is-invalid @enderror" 
                                required
                                step="0.01"
                                min="0.01"
                                placeholder="100.00"
                            >
                            <span class="input-icon">📐</span>
                        </div>
                        @error('area')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Área total en metros cuadrados</small>
                    </div>

                    <div class="form-group">
                        <label for="idCultivo" class="form-label">
                            Tipo de Cultivo <span class="required-mark">*</span>
                        </label>
                        <select 
                            name="idCultivo" 
                            id="idCultivo" 
                            class="form-select @error('idCultivo') is-invalid @enderror"
                            required
                        >
                            <option value="">-- Seleccione un cultivo --</option>
                            @foreach($cultivos as $cultivo)
                                <option value="{{ $cultivo->idCultivo }}" {{ old('idCultivo') == $cultivo->idCultivo ? 'selected' : '' }}>
                                    {{ $cultivo->nombreCultivo }} 
                                    @if($cultivo->nombreCientifico)
                                        ({{ $cultivo->nombreCientifico }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('idCultivo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- UBICACIÓN --}}
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">🗺️</div>
                    <div class="section-title">
                        <h3>Ubicación</h3>
                        <p class="section-description">Localización geográfica de la parcela</p>
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label for="region" class="form-label">
                            Región <span class="required-mark">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="region" 
                            id="region" 
                            value="{{ old('region') }}"
                            class="form-control @error('region') is-invalid @enderror" 
                            required
                            placeholder="Ej: Puno"
                            maxlength="100"
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
                            value="{{ old('provincia') }}"
                            class="form-control @error('provincia') is-invalid @enderror" 
                            required
                            placeholder="Ej: Puno"
                            maxlength="100"
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
                            value="{{ old('distrito') }}"
                            class="form-control @error('distrito') is-invalid @enderror" 
                            required
                            placeholder="Ej: Puno"
                            maxlength="100"
                        >
                        @error('distrito')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="coordenadaLatitud" class="form-label">
                            Latitud (Opcional)
                        </label>
                        <input 
                            type="number" 
                            name="coordenadaLatitud" 
                            id="coordenadaLatitud" 
                            value="{{ old('coordenadaLatitud') }}"
                            class="form-control @error('coordenadaLatitud') is-invalid @enderror" 
                            step="any"
                            min="-90"
                            max="90"
                            placeholder="-15.840231"
                        >
                        @error('coordenadaLatitud')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Entre -90 y 90</small>
                    </div>

                    <div class="form-group">
                        <label for="coordenadaLongitud" class="form-label">
                            Longitud (Opcional)
                        </label>
                        <input 
                            type="number" 
                            name="coordenadaLongitud" 
                            id="coordenadaLongitud" 
                            value="{{ old('coordenadaLongitud') }}"
                            class="form-control @error('coordenadaLongitud') is-invalid @enderror" 
                            step="0.000001"
                            min="-180"
                            max="180"
                            placeholder="-70.021980"
                        >
                        @error('coordenadaLongitud')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Entre -180 y 180</small>
                    </div>
                </div>

                <div class="info-box">
                    <span class="info-box-icon">💡</span>
                    <span>Las coordenadas GPS se buscarán automáticamente al escribir la ubicación. También puedes ingresarlas manualmente.</span>
                </div>
            </div>

            {{-- DATOS DE SIEMBRA Y RIEGO --}}
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">💧</div>
                    <div class="section-title">
                        <h3>Datos de Cultivo y Riego</h3>
                        <p class="section-description">Información sobre siembra y riego</p>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="fecha_siembra" class="form-label">
                            Fecha de Siembra (Opcional)
                        </label>
                        <input 
                            type="date" 
                            name="fecha_siembra" 
                            id="fecha_siembra" 
                            value="{{ old('fecha_siembra') }}"
                            class="form-control @error('fecha_siembra') is-invalid @enderror" 
                            max="{{ date('Y-m-d') }}"
                        >
                        @error('fecha_siembra')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Cuándo se sembró el cultivo actual</small>
                    </div>

                    <div class="form-group">
                        <label for="ultimo_riego" class="form-label">
                            Último Riego (Opcional)
                        </label>
                        <input 
                            type="date" 
                            name="ultimo_riego" 
                            id="ultimo_riego" 
                            value="{{ old('ultimo_riego') }}"
                            class="form-control @error('ultimo_riego') is-invalid @enderror" 
                            max="{{ date('Y-m-d') }}"
                        >
                        @error('ultimo_riego')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Cuándo regaste por última vez</small>
                    </div>
                </div>
            </div>

            {{-- HIDROGEL --}}
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">💎</div>
                    <div class="section-title">
                        <h3>Aplicación de Hidrogel</h3>
                        <p class="section-description">Tecnología de retención de agua</p>
                    </div>
                </div>

                {{-- RECUADRO INFORMATIVO SOBRE HIDROGEL --}}
                <div style="background: linear-gradient(135deg, #e3f2fd, #bbdefb); border: 2px solid #2196f3; border-left: 5px solid #2196f3; border-radius: 12px; padding: 20px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(33, 150, 243, 0.15);">
                    <div style="display: flex; align-items: flex-start; gap: 15px;">
                        <div style="font-size: 32px; flex-shrink: 0;">💧</div>
                        <div>
                            <h4 style="margin: 0 0 12px 0; color: #0d47a1; font-size: 18px; font-weight: 800;">
                                ¿Qué es el Hidrogel y para qué sirve?
                            </h4>
                            <p style="margin: 0 0 12px 0; color: #1565c0; font-size: 14px; line-height: 1.7; font-weight: 600;">
                                El <strong>hidrogel agrícola</strong> es un polímero superabsorbente que retiene hasta 400 veces su peso en agua. Funciona como una "esponja inteligente" en el suelo que:
                            </p>
                            <ul style="margin: 0 0 15px 0; padding-left: 25px; color: #1565c0; font-size: 14px; line-height: 1.8; font-weight: 600;">
                                <li><strong>💧 Reduce el consumo de agua:</strong> Ahorra hasta un 25-30% en riego</li>
                                <li><strong>🌱 Mejora el crecimiento:</strong> Las plantas absorben agua gradualmente según necesitan</li>
                                <li><strong>⏰ Espacía los riegos:</strong> Permite regar con menos frecuencia</li>
                                <li><strong>🛡️ Protege en sequías:</strong> Actúa como reserva de agua en períodos críticos</li>
                                <li><strong>♻️ Es reutilizable:</strong> Dura de 3 a 5 años en el suelo</li>
                            </ul>
                            <div style="background: rgba(255, 255, 255, 0.5); padding: 12px 15px; border-radius: 8px; border-left: 3px solid #1976d2;">
                                <p style="margin: 0; color: #0d47a1; font-size: 13px; font-weight: 700;">
                                    <span style="font-size: 16px;">📊</span> <strong>Cómo usar:</strong> Se aplica mezclado con el suelo al momento de sembrar o trasplantar, típicamente 2-5 kg por cada 100 m² dependiendo del cultivo.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-wrapper">
                        <input 
                            type="checkbox" 
                            name="tieneHidrogelAplicado" 
                            id="tieneHidrogelAplicado"
                            value="1"
                            {{ old('tieneHidrogelAplicado') ? 'checked' : '' }}
                        >
                        <span class="form-label" style="margin: 0;">
                            Esta parcela tiene hidrogel aplicado
                        </span>
                    </label>
                </div>

                <div id="hidrogelFields" style="display: none;">
                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="hidrogel_fecha_aplicacion" class="form-label">
                                Fecha de Aplicación <span class="required-mark">*</span>
                            </label>
                            <input 
                                type="date" 
                                name="hidrogel_fecha_aplicacion" 
                                id="hidrogel_fecha_aplicacion" 
                                value="{{ old('hidrogel_fecha_aplicacion') }}"
                                class="form-control @error('hidrogel_fecha_aplicacion') is-invalid @enderror" 
                                max="{{ date('Y-m-d') }}"
                            >
                            @error('hidrogel_fecha_aplicacion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="hidrogel_cantidad_kg" class="form-label">
                                Cantidad (kg) <span class="required-mark">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="hidrogel_cantidad_kg" 
                                id="hidrogel_cantidad_kg" 
                                value="{{ old('hidrogel_cantidad_kg') }}"
                                class="form-control @error('hidrogel_cantidad_kg') is-invalid @enderror" 
                                step="0.01"
                                min="0"
                                placeholder="2.50"
                            >
                            @error('hidrogel_cantidad_kg')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="info-box">
                        <span class="info-box-icon">📊</span>
                        <span>El hidrogel reducirá automáticamente las necesidades de riego en un 25%.</span>
                    </div>
                </div>
            </div>
            {{-- NOTAS --}}
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">📝</div>
                    <div class="section-title">
                        <h3>Notas y Observaciones</h3>
                        <p class="section-description">Información adicional</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notas_usuario" class="form-label">
                        Notas sobre la Parcela
                    </label>
                    <textarea 
                        name="notas_usuario" 
                        id="notas_usuario" 
                        rows="4"
                        class="form-control @error('notas_usuario') is-invalid @enderror"
                        placeholder="Ej: Pendiente pronunciada, exposición solar directa, suelo arcilloso, problemas de drenaje..."
                        maxlength="1000"
                    >{{ old('notas_usuario') }}</textarea>
                    @error('notas_usuario')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text">Condiciones especiales, características del terreno, etc.</small>
                </div>
            </div>

            {{-- ACCIONES --}}
            <div class="form-actions">
                <a href="{{ route('parcelas.index') }}" class="btn btn-cancel">
                    <span>✕</span>
                    <span>Cancelar</span>
                </a>
                <button type="submit" class="btn btn-submit">
                    <span>💾</span>
                    <span>Crear Parcela</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ============================================
        // 🌍 GEOCODIFICACIÓN AUTOMÁTICA
        // ============================================
        
        const regionInput = document.getElementById('region');
        const provinciaInput = document.getElementById('provincia');
        const distritoInput = document.getElementById('distrito');
        const latitudInput = document.getElementById('coordenadaLatitud');
        const longitudInput = document.getElementById('coordenadaLongitud');
        
        // Crear indicador de búsqueda
        const searchIndicator = document.createElement('div');
        searchIndicator.id = 'geocoding-indicator';
        searchIndicator.style.cssText = `
            display: none;
            margin-top: 15px;
            padding: 15px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            animation: fadeIn 0.3s;
        `;
        
        // Insertar indicador después de los campos de coordenadas
        longitudInput.closest('.form-group').insertAdjacentElement('afterend', searchIndicator);

        let searchTimeout = null;
        
        /**
         * Buscar coordenadas cuando el usuario termina de escribir
         */
        function buscarCoordenadas() {
            const region = regionInput.value.trim();
            const provincia = provinciaInput.value.trim();
            const distrito = distritoInput.value.trim();
            
            // Validar que al menos distrito esté completo
            if (!distrito || distrito.length < 3) {
                return;
            }
            
            // Si ya hay coordenadas manuales, no buscar automáticamente
            if (latitudInput.value && longitudInput.value) {
                return;
            }
            
            // Cancelar búsqueda anterior si existe
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Esperar 1 segundo después de que el usuario dejó de escribir
            searchTimeout = setTimeout(() => {
                realizarBusqueda(distrito, provincia, region);
            }, 1000);
        }
        
        /**
         * Realizar la búsqueda AJAX (VERSIÓN MEJORADA CON REDONDEO)
         */
        async function realizarBusqueda(distrito, provincia, region) {
            mostrarIndicador('buscando', '🔍 Buscando coordenadas GPS...');
            
            try {
                const response = await fetch('{{ route("api.geocodificar") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        distrito: distrito,
                        provincia: provincia || distrito,
                        region: region || provincia || distrito
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // ✅ Redondear a 6 decimales para evitar problemas de validación
                    const latRedondeada = parseFloat(data.data.latitud).toFixed(6);
                    const lonRedondeada = parseFloat(data.data.longitud).toFixed(6);
                    
                    // Rellenar campos automáticamente
                    latitudInput.value = latRedondeada;
                    longitudInput.value = lonRedondeada;
                    
                    // Resaltar campos con animación
                    latitudInput.style.background = '#d4edda';
                    longitudInput.style.background = '#d4edda';
                    latitudInput.style.transition = 'background 0.3s ease';
                    longitudInput.style.transition = 'background 0.3s ease';
                    
                    setTimeout(() => {
                        latitudInput.style.background = '';
                        longitudInput.style.background = '';
                    }, 2000);
                    
                    mostrarIndicador('success', `
                        ${data.mensaje}<br>
                        <small style="opacity: 0.8;">
                            📍 ${latRedondeada}, ${lonRedondeada}<br>
                            ${data.data.display_name}
                        </small>
                    `);
                    
                    // Ocultar mensaje después de 5 segundos
                    setTimeout(() => {
                        ocultarIndicador();
                    }, 5000);
                } else {
                    mostrarIndicador('warning', data.mensaje + '<br><small>Puedes ingresarlas manualmente si las conoces</small>');
                    
                    setTimeout(() => {
                        ocultarIndicador();
                    }, 4000);
                }
                
            } catch (error) {
                console.error('Error en geocodificación:', error);
                mostrarIndicador('error', '❌ Error al buscar coordenadas. Intenta manualmente.');
                
                setTimeout(() => {
                    ocultarIndicador();
                }, 4000);
            }
        }
        
        /**
         * Mostrar indicador de estado
         */
        function mostrarIndicador(tipo, mensaje) {
            const styles = {
                buscando: {
                    background: 'linear-gradient(135deg, #cfe2ff, #b6d4fe)',
                    border: '2px solid #0d6efd',
                    color: '#084298'
                },
                success: {
                    background: 'linear-gradient(135deg, #d1e7dd, #badbcc)',
                    border: '2px solid #198754',
                    color: '#0f5132'
                },
                warning: {
                    background: 'linear-gradient(135deg, #fff3cd, #ffecb5)',
                    border: '2px solid #ffc107',
                    color: '#997404'
                },
                error: {
                    background: 'linear-gradient(135deg, #f8d7da, #f5c2c7)',
                    border: '2px solid #dc3545',
                    color: '#842029'
                }
            };
            
            const style = styles[tipo] || styles.buscando;
            
            searchIndicator.style.background = style.background;
            searchIndicator.style.border = style.border;
            searchIndicator.style.color = style.color;
            searchIndicator.innerHTML = mensaje;
            searchIndicator.style.display = 'block';
        }
        
        /**
         * Ocultar indicador
         */
        function ocultarIndicador() {
            searchIndicator.style.display = 'none';
        }
        
        // 🎯 Escuchar cambios en los campos de ubicación
        regionInput.addEventListener('input', buscarCoordenadas);
        provinciaInput.addEventListener('input', buscarCoordenadas);
        distritoInput.addEventListener('input', buscarCoordenadas);
        
        // También buscar cuando pierden el foco
        distritoInput.addEventListener('blur', function() {
            if (this.value.trim().length >= 3) {
                buscarCoordenadas();
            }
        });
        
        // 🔒 Si el usuario edita manualmente las coordenadas, detener búsqueda automática
        latitudInput.addEventListener('input', function() {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
        });
        
        longitudInput.addEventListener('input', function() {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
        });

        // ============================================
        // 💎 TOGGLE CAMPOS DE HIDROGEL
        // ============================================
        
        const hidrogelCheckbox = document.getElementById('tieneHidrogelAplicado');
        const hidrogelFields = document.getElementById('hidrogelFields');
        const hidrogelFecha = document.getElementById('hidrogel_fecha_aplicacion');
        const hidrogelCantidad = document.getElementById('hidrogel_cantidad_kg');

        function toggleHidrogelFields() {
            if (hidrogelCheckbox.checked) {
                hidrogelFields.style.display = 'block';
                hidrogelFecha.required = true;
                hidrogelCantidad.required = true;
            } else {
                hidrogelFields.style.display = 'none';
                hidrogelFecha.required = false;
                hidrogelCantidad.required = false;
            }
        }

        hidrogelCheckbox.addEventListener('change', toggleHidrogelFields);
        toggleHidrogelFields();

        // ============================================
        // ✅ VALIDACIÓN EN TIEMPO REAL
        // ============================================
        
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

        // ============================================
        // 🚫 PREVENIR ENVÍO CON ERRORES
        // ============================================
        
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const invalidInputs = document.querySelectorAll('.is-invalid');
            if (invalidInputs.length > 0) {
                e.preventDefault();
                invalidInputs[0].focus();
                alert('Por favor, corrige los errores antes de continuar.');
            }
        });
    });
</script>
@endpush