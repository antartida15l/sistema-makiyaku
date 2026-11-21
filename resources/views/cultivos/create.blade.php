@extends('layouts.app')

@section('title', 'Agregar Nuevo Cultivo')

@push('styles')
<style>
    .form-container {
        max-width: 1000px;
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
        border-radius: var(--border-radius);
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
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .required-mark {
        color: var(--danger);
        font-size: 16px;
        margin-left: 4px;
    }

    .form-control,
    .form-select,
    .form-textarea {
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

    .form-textarea {
        resize: vertical;
        min-height: 120px;
        font-family: inherit;
    }

    .form-control:focus,
    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
        background: #f8fff9;
    }

    .form-control.is-invalid,
    .form-select.is-invalid,
    .form-textarea.is-invalid {
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
        justify-content: center;
    }

    .btn-cancel {
        background: var(--light);
        color: var(--dark);
        border: 2px solid #e9ecef;
    }

    .btn-cancel:hover {
        background: #e9ecef;
        border-color: var(--muted);
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
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

    .alert-errors {
        background: linear-gradient(135deg, #f8d7da, #f5c2c7);
        border: 2px solid var(--danger);
        border-left: 5px solid var(--danger);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
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

    .alert-errors strong {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        color: #721c24;
        margin-bottom: 12px;
    }

    .alert-errors ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .alert-errors li {
        padding: 8px 0;
        color: #721c24;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .alert-errors li::before {
        content: '•';
        color: var(--danger);
        font-size: 20px;
    }

    .alert-close-btn {
        background: none;
        border: none;
        font-size: 24px;
        color: #721c24;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: var(--transition);
        margin-left: auto;
    }

    .alert-close-btn:hover {
        background: rgba(220, 53, 69, 0.1);
        transform: rotate(90deg);
    }

    @media (max-width: 768px) {
        .form-container {
            max-width: 100%;
        }

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
            padding: 20px;
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
    <!-- Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-icon">🌱</div>
            <div class="page-title-group">
                <h1>Agregar Nuevo Cultivo</h1>
                <p class="page-subtitle">Registra un nuevo tipo de cultivo con todos sus requerimientos</p>
            </div>
        </div>
        <a href="{{ route('cultivos.index') }}" class="btn-back">
            <span>←</span>
            <span>Volver</span>
        </a>
    </div>

    <!-- Errores -->
    @if ($errors->any())
        <div class="alert-errors">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <strong>
                    <span style="font-size: 24px;">⚠️</span>
                    ¡Atención! Se encontraron {{ count($errors) }} error(es)
                </strong>
                <button type="button" class="alert-close-btn" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Formulario -->
    <form action="{{ route('cultivos.store') }}" method="POST">
        @csrf

        <div class="form-card">
            <!-- 1. INFORMACIÓN BÁSICA -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">📋</div>
                    <div class="section-title">
                        <h3>Información Básica</h3>
                        <p class="section-description">Datos generales e identificación del cultivo</p>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="nombreCultivo" class="form-label">
                            Nombre del Cultivo <span class="required-mark">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="text" 
                                name="nombreCultivo" 
                                id="nombreCultivo" 
                                value="{{ old('nombreCultivo') }}"
                                class="form-control @error('nombreCultivo') is-invalid @enderror" 
                                required
                                placeholder="Ej: Tomate, Maíz, Fresa..."
                            >
                            <span class="input-icon">🏷️</span>
                        </div>
                        @error('nombreCultivo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="nombreCientifico" class="form-label">
                            Nombre Científico
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="text" 
                                name="nombreCientifico" 
                                id="nombreCientifico" 
                                value="{{ old('nombreCientifico') }}"
                                class="form-control @error('nombreCientifico') is-invalid @enderror" 
                                placeholder="Ej: Solanum lycopersicum"
                            >
                            <span class="input-icon">🔬</span>
                        </div>
                        @error('nombreCientifico')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="tipo" class="form-label">
                            Tipo de Cultivo <span class="required-mark">*</span>
                        </label>
                        <select 
                            name="tipo" 
                            id="tipo" 
                            class="form-select @error('tipo') is-invalid @enderror"
                            required
                        >
                            <option value="">-- Seleccione un tipo --</option>
                            <option value="Hortalizas" {{ old('tipo') == 'Hortalizas' ? 'selected' : '' }}>🥬 Hortalizas</option>
                            <option value="Cereales" {{ old('tipo') == 'Cereales' ? 'selected' : '' }}>🌾 Cereales</option>
                            <option value="Frutales" {{ old('tipo') == 'Frutales' ? 'selected' : '' }}>🍎 Frutales</option>
                            <option value="Legumbres" {{ old('tipo') == 'Legumbres' ? 'selected' : '' }}>🫘 Legumbres</option>
                            <option value="Tubérculos" {{ old('tipo') == 'Tubérculos' ? 'selected' : '' }}>🥔 Tubérculos</option>
                            <option value="Aromáticas" {{ old('tipo') == 'Aromáticas' ? 'selected' : '' }}>🌿 Aromáticas</option>
                            <option value="Oleaginosas" {{ old('tipo') == 'Oleaginosas' ? 'selected' : '' }}>🌻 Oleaginosas</option>
                            <option value="Otros" {{ old('tipo') == 'Otros' ? 'selected' : '' }}>🌾 Otros</option>
                        </select>
                        @error('tipo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="region" class="form-label">
                            Región de Cultivo Recomendada
                        </label>
                        <select 
                            name="region" 
                            id="region" 
                            class="form-select @error('region') is-invalid @enderror"
                        >
                            <option value="">-- Seleccione región --</option>
                            <option value="Costa Norte" {{ old('region') == 'Costa Norte' ? 'selected' : '' }}>🏖️ Costa Norte</option>
                            <option value="Costa Central y Sur" {{ old('region') == 'Costa Central y Sur' ? 'selected' : '' }}>🌊 Costa Central y Sur</option>
                            <option value="Sierra" {{ old('region') == 'Sierra' ? 'selected' : '' }}>⛰️ Sierra</option>
                            <option value="Selva" {{ old('region') == 'Selva' ? 'selected' : '' }}>🌴 Selva</option>
                        </select>
                        @error('region')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- 2. REQUERIMIENTOS DE RIEGO -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">💧</div>
                    <div class="section-title">
                        <h3>Requerimientos de Riego</h3>
                        <p class="section-description">Frecuencia, consumo de agua y niveles de humedad</p>
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label for="frecuenciaRiegoMin" class="form-label">
                            Frecuencia Mínima (días) <span class="required-mark">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="number" 
                                name="frecuenciaRiegoMin" 
                                id="frecuenciaRiegoMin" 
                                value="{{ old('frecuenciaRiegoMin', 2) }}"
                                min="1"
                                max="365"
                                class="form-control @error('frecuenciaRiegoMin') is-invalid @enderror" 
                                required
                            >
                            <span class="input-icon">⏱️</span>
                        </div>
                        @error('frecuenciaRiegoMin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Días mínimos entre riegos</small>
                    </div>

                    <div class="form-group">
                        <label for="frecuenciaRiegoMax" class="form-label">
                            Frecuencia Máxima (días) <span class="required-mark">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="number" 
                                name="frecuenciaRiegoMax" 
                                id="frecuenciaRiegoMax" 
                                value="{{ old('frecuenciaRiegoMax', 5) }}"
                                min="1"
                                max="365"
                                class="form-control @error('frecuenciaRiegoMax') is-invalid @enderror" 
                                required
                            >
                            <span class="input-icon">⏰</span>
                        </div>
                        @error('frecuenciaRiegoMax')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Días máximos entre riegos</small>
                    </div>

                    <div class="form-group">
                        <label for="litrosPorM2Dia" class="form-label">
                            Consumo L/m²/día <span class="required-mark">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="number" 
                                name="litrosPorM2Dia" 
                                id="litrosPorM2Dia" 
                                value="{{ old('litrosPorM2Dia', 5.0) }}"
                                step="0.01"
                                min="0.01"
                                max="50"
                                class="form-control @error('litrosPorM2Dia') is-invalid @enderror" 
                                required
                            >
                            <span class="input-icon">💦</span>
                        </div>
                        @error('litrosPorM2Dia')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Litros de agua por m²/día</small>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="humedadOptimaMin" class="form-label">
                            Humedad Óptima Mínima (%) <span class="required-mark">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="number" 
                                name="humedadOptimaMin" 
                                id="humedadOptimaMin" 
                                value="{{ old('humedadOptimaMin', 60) }}"
                                step="0.01"
                                min="0"
                                max="100"
                                class="form-control @error('humedadOptimaMin') is-invalid @enderror" 
                                required
                            >
                            <span class="input-icon">📊</span>
                        </div>
                        @error('humedadOptimaMin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="humedadOptimaMax" class="form-label">
                            Humedad Óptima Máxima (%) <span class="required-mark">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="number" 
                                name="humedadOptimaMax" 
                                id="humedadOptimaMax" 
                                value="{{ old('humedadOptimaMax', 80) }}"
                                step="0.01"
                                min="0"
                                max="100"
                                class="form-control @error('humedadOptimaMax') is-invalid @enderror" 
                                required
                            >
                            <span class="input-icon">📈</span>
                        </div>
                        @error('humedadOptimaMax')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="sensibilidadSequia" class="form-label">
                            Sensibilidad a Sequía <span class="required-mark">*</span>
                        </label>
                        <select 
                            name="sensibilidadSequia" 
                            id="sensibilidadSequia" 
                            class="form-select @error('sensibilidadSequia') is-invalid @enderror"
                            required
                        >
                            <option value="">-- Seleccione --</option>
                            <option value="Muy Baja" {{ old('sensibilidadSequia') == 'Muy Baja' ? 'selected' : '' }}>Muy Baja</option>
                            <option value="Baja" {{ old('sensibilidadSequia') == 'Baja' ? 'selected' : '' }}>Baja</option>
                            <option value="Media" {{ old('sensibilidadSequia') == 'Media' ? 'selected' : '' }}>Media</option>
                            <option value="Media-Alta" {{ old('sensibilidadSequia') == 'Media-Alta' ? 'selected' : '' }}>Media-Alta</option>
                            <option value="Alta" {{ old('sensibilidadSequia') == 'Alta' ? 'selected' : '' }}>Alta</option>
                            <option value="Muy Alta" {{ old('sensibilidadSequia') == 'Muy Alta' ? 'selected' : '' }}>Muy Alta</option>
                        </select>
                        @error('sensibilidadSequia')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="diasCriticosSinAgua" class="form-label">
                            Días Críticos sin Agua <span class="required-mark">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="number" 
                                name="diasCriticosSinAgua" 
                                id="diasCriticosSinAgua" 
                                value="{{ old('diasCriticosSinAgua', 7) }}"
                                min="1"
                                max="90"
                                class="form-control @error('diasCriticosSinAgua') is-invalid @enderror" 
                                required
                            >
                            <span class="input-icon">⚠️</span>
                        </div>
                        @error('diasCriticosSinAgua')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Días antes de daño permanente</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="etapaMayorConsumo" class="form-label">
                        Etapa de Mayor Consumo de Agua <span class="required-mark">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <input 
                            type="text" 
                            name="etapaMayorConsumo" 
                            id="etapaMayorConsumo" 
                            value="{{ old('etapaMayorConsumo') }}"
                            class="form-control @error('etapaMayorConsumo') is-invalid @enderror" 
                            required
                            placeholder="Ej: Floración y fructificación"
                        >
                        <span class="input-icon">🌸</span>
                    </div>
                    @error('etapaMayorConsumo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label for="diasEtapaConsumoMin" class="form-label">
                            Inicio Etapa (días)
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="number" 
                                name="diasEtapaConsumoMin" 
                                id="diasEtapaConsumoMin" 
                                value="{{ old('diasEtapaConsumoMin') }}"
                                min="0"
                                class="form-control @error('diasEtapaConsumoMin') is-invalid @enderror"
                                placeholder="Ej: 30"
                            >
                            <span class="input-icon">📅</span>
                        </div>
                        @error('diasEtapaConsumoMin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="diasEtapaConsumoMax" class="form-label">
                            Fin Etapa (días)
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="number" 
                                name="diasEtapaConsumoMax" 
                                id="diasEtapaConsumoMax" 
                                value="{{ old('diasEtapaConsumoMax') }}"
                                min="0"
                                class="form-control @error('diasEtapaConsumoMax') is-invalid @enderror"
                                placeholder="Ej: 60"
                            >
                            <span class="input-icon">📅</span>
                        </div>
                        @error('diasEtapaConsumoMax')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="reduccionConHidrogel" class="form-label">
                            Reducción con Hidrogel (%)
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="number" 
                                name="reduccionConHidrogel" 
                                id="reduccionConHidrogel" 
                                value="{{ old('reduccionConHidrogel') }}"
                                step="0.01"
                                min="0"
                                max="100"
                                class="form-control @error('reduccionConHidrogel') is-invalid @enderror"
                                placeholder="Ej: 30"
                            >
                            <span class="input-icon">🧪</span>
                        </div>
                        @error('reduccionConHidrogel')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">% de ahorro de agua con hidrogel</small>
                    </div>
                </div>
            </div>

            <!-- 3. CONDICIONES CLIMÁTICAS CRÍTICAS -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">🌡️</div>
                    <div class="section-title">
                        <h3>Condiciones Climáticas Críticas</h3>
                        <p class="section-description">Temperaturas límite y sensibilidad a heladas</p>
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label for="tempCriticaMinima" class="form-label">
                            Temperatura Mínima (°C) <span class="required-mark">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="number" 
                                name="tempCriticaMinima" 
                                id="tempCriticaMinima" 
                                value="{{ old('tempCriticaMinima', 5) }}"
                                step="0.01"
                                min="-20"
                                max="50"
                                class="form-control @error('tempCriticaMinima') is-invalid @enderror" 
                                required
                            >
                            <span class="input-icon">🥶</span>
                        </div>
                        @error('tempCriticaMinima')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Temperatura crítica mínima</small>
                    </div>

                    <div class="form-group">
                        <label for="tempCriticaMaxima" class="form-label">
                            Temperatura Máxima (°C) <span class="required-mark">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="number" 
                                name="tempCriticaMaxima" 
                                id="tempCriticaMaxima" 
                                value="{{ old('tempCriticaMaxima', 35) }}"
                                step="0.01"
                                min="-20"
                                max="50"
                                class="form-control @error('tempCriticaMaxima') is-invalid @enderror" 
                                required
                            >
                            <span class="input-icon">🥵</span>
                        </div>
                        @error('tempCriticaMaxima')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Temperatura crítica máxima</small>
                    </div>

                    <div class="form-group">
                        <label for="sensibilidadHeladas" class="form-label">
                            Sensibilidad a Heladas <span class="required-mark">*</span>
                        </label>
                        <select 
                            name="sensibilidadHeladas" 
                            id="sensibilidadHeladas" 
                            class="form-select @error('sensibilidadHeladas') is-invalid @enderror"
                            required
                        >
                            <option value="">-- Seleccione --</option>
                            <option value="Muy Baja" {{ old('sensibilidadHeladas') == 'Muy Baja' ? 'selected' : '' }}>Muy Baja</option>
                            <option value="Baja" {{ old('sensibilidadHeladas') == 'Baja' ? 'selected' : '' }}>Baja</option>
                            <option value="Media" {{ old('sensibilidadHeladas') == 'Media' ? 'selected' : '' }}>Media</option>
                            <option value="Media-Alta" {{ old('sensibilidadHeladas') == 'Media-Alta' ? 'selected' : '' }}>Media-Alta</option>
                            <option value="Alta" {{ old('sensibilidadHeladas') == 'Alta' ? 'selected' : '' }}>Alta</option>
                            <option value="Muy Alta" {{ old('sensibilidadHeladas') == 'Muy Alta' ? 'selected' : '' }}>Muy Alta</option>
                        </select>
                        @error('sensibilidadHeladas')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="efectoTempMinima" class="form-label">
                            Efecto de Temperatura Mínima
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="text" 
                                name="efectoTempMinima" 
                                id="efectoTempMinima" 
                                value="{{ old('efectoTempMinima') }}"
                                class="form-control @error('efectoTempMinima') is-invalid @enderror"
                                placeholder="Ej: Daño foliar, detención del crecimiento..."
                            >
                            <span class="input-icon">❄️</span>
                        </div>
                        @error('efectoTempMinima')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Descripción del daño por frío</small>
                    </div>

                    <div class="form-group">
                        <label for="efectoTempMaxima" class="form-label">
                            Efecto de Temperatura Máxima
                        </label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="text" 
                                name="efectoTempMaxima" 
                                id="efectoTempMaxima" 
                                value="{{ old('efectoTempMaxima') }}"
                                class="form-control @error('efectoTempMaxima') is-invalid @enderror"
                                placeholder="Ej: Quemadura de hojas, caída de flores..."
                            >
                            <span class="input-icon">🔥</span>
                        </div>
                        @error('efectoTempMaxima')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Descripción del daño por calor</small>
                    </div>
                </div>
            </div>

            <!-- 4. INFORMACIÓN ADICIONAL -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">📝</div>
                    <div class="section-title">
                        <h3>Información Adicional</h3>
                        <p class="section-description">Ciclo productivo y observaciones generales</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="cicloProductivo" class="form-label">
                        Ciclo Productivo (días)
                    </label>
                    <div class="input-icon-wrapper">
                        <input 
                            type="number" 
                            name="cicloProductivo" 
                            id="cicloProductivo" 
                            value="{{ old('cicloProductivo', 90) }}"
                            min="1"
                            max="730"
                            class="form-control @error('cicloProductivo') is-invalid @enderror"
                            placeholder="90"
                        >
                        <span class="input-icon">⏱️</span>
                    </div>
                    @error('cicloProductivo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text">Tiempo desde siembra hasta cosecha</small>
                </div>

                <div class="form-group">
                    <label for="descripcion" class="form-label">
                        Descripción del Cultivo
                    </label>
                    <textarea 
                        name="descripcion" 
                        id="descripcion" 
                        class="form-textarea @error('descripcion') is-invalid @enderror"
                        placeholder="Características generales, variedades, usos, beneficios nutricionales..."
                    >{{ old('descripcion') }}</textarea>
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text">Información general sobre el cultivo (opcional)</small>
                </div>

                <div class="form-group">
                    <label for="observaciones" class="form-label">
                        Observaciones y Notas Especiales
                    </label>
                    <textarea 
                        name="observaciones" 
                        id="observaciones" 
                        class="form-textarea @error('observaciones') is-invalid @enderror"
                        placeholder="Requerimientos especiales, plagas comunes, consejos de manejo, compatibilidades..."
                    >{{ old('observaciones') }}</textarea>
                    @error('observaciones')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text">Notas adicionales para el agricultor (opcional)</small>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="form-actions">
                <a href="{{ route('cultivos.index') }}" class="btn btn-cancel">
                    <span>✕</span>
                    <span>Cancelar</span>
                </a>
                <button type="submit" class="btn btn-submit">
                    <span>💾</span>
                    <span>Guardar Cultivo</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animación de entrada
        const formGroups = document.querySelectorAll('.form-group');
        formGroups.forEach((group, index) => {
            group.style.opacity = '0';
            group.style.transform = 'translateY(20px)';
            setTimeout(() => {
                group.style.transition = 'all 0.4s ease';
                group.style.opacity = '1';
                group.style.transform = 'translateY(0)';
            }, index * 30);
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

        // Validación de frecuencia de riego
        const frecMin = document.getElementById('frecuenciaRiegoMin');
        const frecMax = document.getElementById('frecuenciaRiegoMax');

        function validarFrecuencia() {
            const min = parseFloat(frecMin.value);
            const max = parseFloat(frecMax.value);

            if (min && max && max < min) {
                frecMax.setCustomValidity('La frecuencia máxima debe ser mayor o igual a la mínima');
            } else {
                frecMax.setCustomValidity('');
            }
        }

        frecMin?.addEventListener('input', validarFrecuencia);
        frecMax?.addEventListener('input', validarFrecuencia);

        // Validación de humedad
        const humedadMin = document.getElementById('humedadOptimaMin');
        const humedadMax = document.getElementById('humedadOptimaMax');

        function validarHumedad() {
            const min = parseFloat(humedadMin.value);
            const max = parseFloat(humedadMax.value);

            if (min && max && max < min) {
                humedadMax.setCustomValidity('La humedad máxima debe ser mayor o igual a la mínima');
            } else {
                humedadMax.setCustomValidity('');
            }
        }

        humedadMin?.addEventListener('input', validarHumedad);
        humedadMax?.addEventListener('input', validarHumedad);

        // Validación de temperatura
        const tempMin = document.getElementById('tempCriticaMinima');
        const tempMax = document.getElementById('tempCriticaMaxima');

        function validarTemperatura() {
            const min = parseFloat(tempMin.value);
            const max = parseFloat(tempMax.value);

            if (min && max && max < min) {
                tempMax.setCustomValidity('La temperatura máxima debe ser mayor o igual a la mínima');
            } else {
                tempMax.setCustomValidity('');
            }
        }

        tempMin?.addEventListener('input', validarTemperatura);
        tempMax?.addEventListener('input', validarTemperatura);

        // Validación de etapas de consumo
        const diasEtapaMin = document.getElementById('diasEtapaConsumoMin');
        const diasEtapaMax = document.getElementById('diasEtapaConsumoMax');

        function validarEtapas() {
            const min = parseFloat(diasEtapaMin.value);
            const max = parseFloat(diasEtapaMax.value);

            if (min && max && max < min) {
                diasEtapaMax.setCustomValidity('El día final debe ser mayor o igual al inicial');
            } else {
                diasEtapaMax.setCustomValidity('');
            }
        }

        diasEtapaMin?.addEventListener('input', validarEtapas);
        diasEtapaMax?.addEventListener('input', validarEtapas);

        // Efecto al submit
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            // Validar antes de enviar
            if (!form.checkValidity()) {
                e.preventDefault();
                
                // Resaltar campos inválidos
                const invalidInputs = form.querySelectorAll(':invalid');
                invalidInputs.forEach(input => {
                    input.classList.add('is-invalid');
                });
                
                // Scroll al primer campo inválido
                if (invalidInputs.length > 0) {
                    invalidInputs[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                
                return false;
            }

            const submitBtn = this.querySelector('.btn-submit');
            submitBtn.innerHTML = '<span>⏳</span><span>Guardando...</span>';
            submitBtn.style.pointerEvents = 'none';
            submitBtn.style.opacity = '0.7';
        });

        // Auto-scroll suave en errores
        const alertErrors = document.querySelector('.alert-errors');
        if (alertErrors) {
            alertErrors.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
</script>
@endpush