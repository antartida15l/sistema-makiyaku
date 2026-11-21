@extends('layouts.app')

@section('title', $cultivo->nombreCultivo)

@push('styles')
<style>
    .detail-container {
        max-width: 1200px;
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
        flex: 1;
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

    .scientific-name {
        font-style: italic;
        color: var(--muted);
        font-size: 14px;
        margin-top: 3px;
    }

    .action-buttons {
        display: flex;
        gap: 12px;
    }

    .btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
    }

    .btn-back {
        background: var(--light);
        color: var(--dark);
        border: 2px solid #e9ecef;
    }

    .btn-back:hover {
        background: #e9ecef;
        border-color: var(--primary);
        transform: translateX(-5px);
    }

    .btn-edit {
        background: linear-gradient(135deg, #ffc107, #ff9800);
        color: var(--white);
        box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
    }

    .btn-edit:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
    }

    .btn-delete {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: var(--white);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }

    .btn-delete:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
    }

    .detail-card {
        background: var(--white);
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .detail-section {
        padding: 35px;
        border-bottom: 2px solid #f8f9fa;
        position: relative;
    }

    .detail-section:last-child {
        border-bottom: none;
    }

    .detail-section::before {
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

    .detail-section:hover::before {
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

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }

    .detail-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .detail-grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
    }

    .detail-item {
        background: #f8f9fa;
        padding: 18px;
        border-radius: 12px;
        border: 2px solid #e9ecef;
        transition: var(--transition);
    }

    .detail-item:hover {
        background: #ffffff;
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .detail-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .detail-value {
        font-size: 16px;
        font-weight: 700;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .detail-value-large {
        font-size: 24px;
        font-weight: 800;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-primary {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: var(--white);
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
    }

    .badge-info {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: var(--white);
        box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
    }

    .badge-warning {
        background: linear-gradient(135deg, #ffc107, #ff9800);
        color: var(--dark);
        box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
    }

    .badge-danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: var(--white);
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
    }

    .badge-success {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: var(--white);
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
    }

    .badge-secondary {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: var(--white);
        box-shadow: 0 2px 8px rgba(108, 117, 125, 0.3);
    }

    .info-box {
        background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        border-left: 4px solid #17a2b8;
        padding: 18px 22px;
        border-radius: 10px;
        margin-top: 20px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: 14px;
        color: #0c5460;
        font-weight: 600;
        line-height: 1.6;
    }

    .info-box-icon {
        font-size: 22px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .warning-box {
        background: linear-gradient(135deg, #fff3cd, #ffe69c);
        border-left: 4px solid #ffc107;
        padding: 18px 22px;
        border-radius: 10px;
        margin-top: 20px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: 14px;
        color: #856404;
        font-weight: 600;
        line-height: 1.6;
    }

    .danger-box {
        background: linear-gradient(135deg, #f8d7da, #f5c2c7);
        border-left: 4px solid #dc3545;
        padding: 18px 22px;
        border-radius: 10px;
        margin-top: 20px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: 14px;
        color: #721c24;
        font-weight: 600;
        line-height: 1.6;
    }

    .description-box {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        border: 2px solid #e9ecef;
        font-size: 15px;
        color: var(--dark);
        line-height: 1.7;
        font-weight: 500;
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: var(--muted);
        font-style: italic;
    }

    .range-display {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        font-weight: 700;
    }

    .range-separator {
        color: var(--muted);
        font-size: 14px;
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

    .alert-success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border: 2px solid #28a745;
        color: #155724;
    }

    .alert-danger {
        background: linear-gradient(135deg, #f8d7da, #f5c2c7);
        border: 2px solid #dc3545;
        color: #721c24;
    }

    .parcelas-section {
        margin-top: 30px;
    }

    .parcelas-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }

    .parcela-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        border: 2px solid #e9ecef;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .parcela-item:hover {
        background: #ffffff;
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .parcela-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .parcela-info {
        flex: 1;
    }

    .parcela-name {
        font-weight: 700;
        color: var(--dark);
        font-size: 14px;
    }

    .parcela-area {
        font-size: 12px;
        color: var(--muted);
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }

        .action-buttons {
            width: 100%;
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }

        .detail-section {
            padding: 25px 20px;
        }

        .detail-grid,
        .detail-grid-2,
        .detail-grid-3 {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="detail-container">
    {{-- HEADER --}}
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-icon">🌾</div>
            <div class="page-title-group">
                <h1>{{ $cultivo->nombreCultivo }}</h1>
                @if($cultivo->nombreCientifico)
                    <p class="scientific-name">{{ $cultivo->nombreCientifico }}</p>
                @endif
                <p class="page-subtitle">Detalles completos del cultivo</p>
            </div>
        </div>
        <div class="action-buttons">
            <a href="{{ route('cultivos.index') }}" class="btn btn-back">
                <span>←</span>
                <span>Volver</span>
            </a>
            <a href="{{ route('cultivos.edit', $cultivo) }}" class="btn btn-edit">
                <span>✏️</span>
                <span>Editar</span>
            </a>
            <form action="{{ route('cultivos.destroy', $cultivo) }}" method="POST" style="display: inline;" 
                  onsubmit="return confirm('¿Estás seguro de eliminar este cultivo?\n\n⚠️ ADVERTENCIA: Esto eliminará permanentemente toda la información del cultivo.\n\nEsta acción no se puede deshacer.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-delete">
                    <span>🗑️</span>
                    <span>Eliminar</span>
                </button>
            </form>
        </div>
    </div>

    {{-- ALERTAS --}}
    @if(session('success'))
        <div class="alert alert-success">
            <span style="font-size: 24px;">✅</span>
            <strong>{{ session('success') }}</strong>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            <span style="font-size: 24px;">❌</span>
            <strong>{{ session('error') }}</strong>
        </div>
    @endif

    <div class="detail-card">
        {{-- INFORMACIÓN BÁSICA --}}
        <div class="detail-section">
            <div class="section-header">
                <div class="section-icon">📋</div>
                <div class="section-title">
                    <h3>Información Básica</h3>
                    <p class="section-description">Clasificación y características generales</p>
                </div>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">
                        <span>🏷️</span> Tipo de Cultivo
                    </div>
                    <div class="detail-value">
                        <span class="badge badge-primary">{{ $cultivo->tipo }}</span>
                    </div>
                </div>

                @if($cultivo->region)
                <div class="detail-item">
                    <div class="detail-label">
                        <span>🗺️</span> Región de Adaptación
                    </div>
                    <div class="detail-value">
                        <span class="badge badge-info">{{ $cultivo->region }}</span>
                    </div>
                </div>
                @endif

                @if($cultivo->cicloProductivo)
                <div class="detail-item">
                    <div class="detail-label">
                        <span>📅</span> Ciclo Productivo
                    </div>
                    <div class="detail-value">
                        <span class="detail-value-large">{{ $cultivo->cicloProductivo }}</span> días
                    </div>
                </div>
                @endif
            </div>

            @if($cultivo->descripcion)
            <div style="margin-top: 20px;">
                <div class="detail-label" style="margin-bottom: 12px;">
                    <span>📝</span> Descripción
                </div>
                <div class="description-box">
                    {{ $cultivo->descripcion }}
                </div>
            </div>
            @endif
        </div>

        {{-- REQUERIMIENTOS DE RIEGO --}}
        <div class="detail-section">
            <div class="section-header">
                <div class="section-icon">💧</div>
                <div class="section-title">
                    <h3>Requerimientos de Riego</h3>
                    <p class="section-description">Necesidades hídricas del cultivo</p>
                </div>
            </div>

            <div class="detail-grid-3">
                <div class="detail-item">
                    <div class="detail-label">
                        <span>🔄</span> Frecuencia de Riego
                    </div>
                    <div class="detail-value range-display">
                        <span class="detail-value-large">{{ $cultivo->frecuenciaRiegoMin }}</span>
                        <span class="range-separator">a</span>
                        <span class="detail-value-large">{{ $cultivo->frecuenciaRiegoMax }}</span>
                        <span>días</span>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">
                        <span>💦</span> Consumo de Agua
                    </div>
                    <div class="detail-value">
                        <span class="detail-value-large">{{ number_format($cultivo->litrosPorM2Dia, 2) }}</span>
                        <span>L/m²/día</span>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">
                        <span>⏱️</span> Días Críticos sin Agua
                    </div>
                    <div class="detail-value">
                        <span class="detail-value-large">{{ $cultivo->diasCriticosSinAgua }}</span>
                        <span>días</span>
                    </div>
                </div>
            </div>

            <div class="detail-grid-2" style="margin-top: 20px;">
                <div class="detail-item">
                    <div class="detail-label">
                        <span>💧</span> Humedad Óptima
                    </div>
                    <div class="detail-value range-display">
                        <span class="detail-value-large">{{ number_format($cultivo->humedadOptimaMin, 0) }}%</span>
                        <span class="range-separator">a</span>
                        <span class="detail-value-large">{{ number_format($cultivo->humedadOptimaMax, 0) }}%</span>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">
                        <span>🏜️</span> Sensibilidad a Sequía
                    </div>
                    <div class="detail-value">
                        @php
                            $badgeClass = match($cultivo->sensibilidadSequia) {
                                'Muy Baja', 'Baja' => 'badge-success',
                                'Media', 'Media-Alta' => 'badge-warning',
                                'Alta', 'Muy Alta' => 'badge-danger',
                                default => 'badge-secondary'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $cultivo->sensibilidadSequia }}</span>
                    </div>
                </div>
            </div>

            <div class="detail-item" style="margin-top: 20px;">
                <div class="detail-label">
                    <span>🌱</span> Etapa de Mayor Consumo de Agua
                </div>
                <div class="detail-value" style="font-size: 15px;">
                    {{ $cultivo->etapaMayorConsumo }}
                </div>
            </div>

            @if($cultivo->diasEtapaConsumoMin && $cultivo->diasEtapaConsumoMax)
            <div class="detail-grid-2" style="margin-top: 20px;">
                <div class="detail-item">
                    <div class="detail-label">
                        <span>📆</span> Inicio Etapa Mayor Consumo
                    </div>
                    <div class="detail-value">
                        Día <span class="detail-value-large">{{ $cultivo->diasEtapaConsumoMin }}</span>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">
                        <span>📆</span> Fin Etapa Mayor Consumo
                    </div>
                    <div class="detail-value">
                        Día <span class="detail-value-large">{{ $cultivo->diasEtapaConsumoMax }}</span>
                    </div>
                </div>
            </div>
            @endif

            @if($cultivo->reduccionConHidrogel)
            <div class="info-box">
                <span class="info-box-icon">💡</span>
                <span>Con el uso de hidrogel, este cultivo puede reducir su consumo de agua en un <strong>{{ number_format($cultivo->reduccionConHidrogel, 0) }}%</strong>, optimizando significativamente la eficiencia del riego.</span>
            </div>
            @endif
        </div>

        {{-- TEMPERATURAS CRÍTICAS --}}
        <div class="detail-section">
            <div class="section-header">
                <div class="section-icon">🌡️</div>
                <div class="section-title">
                    <h3>Condiciones Climáticas Críticas</h3>
                    <p class="section-description">Límites térmicos de tolerancia y sensibilidad</p>
                </div>
            </div>

            <div class="detail-grid-3">
                <div class="detail-item">
                    <div class="detail-label">
                        <span>❄️</span> Temperatura Crítica Mínima
                    </div>
                    <div class="detail-value">
                        <span class="detail-value-large">{{ number_format($cultivo->tempCriticaMinima, 1) }}°C</span>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">
                        <span>🔥</span> Temperatura Crítica Máxima
                    </div>
                    <div class="detail-value">
                        <span class="detail-value-large">{{ number_format($cultivo->tempCriticaMaxima, 1) }}°C</span>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">
                        <span>🧊</span> Sensibilidad a Heladas
                    </div>
                    <div class="detail-value">
                        @php
                            $badgeClass = match($cultivo->sensibilidadHeladas) {
                                'Muy Baja', 'Baja' => 'badge-success',
                                'Media', 'Media-Alta' => 'badge-warning',
                                'Alta', 'Muy Alta' => 'badge-danger',
                                default => 'badge-secondary'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $cultivo->sensibilidadHeladas }}</span>
                    </div>
                </div>
            </div>

            @if($cultivo->efectoTempMinima)
            <div class="warning-box" style="margin-top: 20px;">
                <span class="info-box-icon">❄️</span>
                <div>
                    <strong>Efecto de Temperatura Mínima Crítica:</strong><br>
                    {{ $cultivo->efectoTempMinima }}
                </div>
            </div>
            @endif

            @if($cultivo->efectoTempMaxima)
            <div class="danger-box">
                <span class="info-box-icon">🔥</span>
                <div>
                    <strong>Efecto de Temperatura Máxima Crítica:</strong><br>
                    {{ $cultivo->efectoTempMaxima }}
                </div>
            </div>
            @endif

            <div class="info-box">
                <span class="info-box-icon">🌡️</span>
                <span>El sistema monitoreará automáticamente estas temperaturas y generará alertas cuando se detecten condiciones críticas que puedan afectar el cultivo.</span>
            </div>
        </div>

        {{-- OBSERVACIONES --}}
        @if($cultivo->observaciones)
        <div class="detail-section">
            <div class="section-header">
                <div class="section-icon">📝</div>
                <div class="section-title">
                    <h3>Observaciones Técnicas</h3>
                    <p class="section-description">Notas y recomendaciones adicionales</p>
                </div>
            </div>

            <div class="description-box">
                {{ $cultivo->observaciones }}
            </div>
        </div>
        @endif

        {{-- PARCELAS ASOCIADAS --}}
        @if($cultivo->parcelas->count() > 0)
        <div class="detail-section">
            <div class="section-header">
                <div class="section-icon">🌾</div>
                <div class="section-title">
                    <h3>Parcelas con este Cultivo</h3>
                    <p class="section-description">{{ $cultivo->parcelas->count() }} parcela(s) registrada(s)</p>
                </div>
            </div>

            <div class="parcelas-list">
                @foreach($cultivo->parcelas as $parcela)
                <a href="{{ route('parcelas.show', $parcela) }}" class="parcela-item" style="text-decoration: none; color: inherit;">
                    <div class="parcela-icon">🌱</div>
                    <div class="parcela-info">
                        <div class="parcela-name">{{ $parcela->nombreParcela }}</div>
                        <div class="parcela-area">{{ number_format($parcela->areaM2, 2) }} m²</div>
                    </div>
                </a>
                @endforeach
            </div>

            <div class="info-box" style="margin-top: 20px;">
                <span class="info-box-icon">⚠️</span>
                <span>Este cultivo está siendo utilizado en {{ $cultivo->parcelas->count() }} parcela(s). No podrás eliminarlo mientras esté asociado a parcelas activas.</span>
            </div>
        @else
        <div class="detail-section">
            <div class="section-header">
                <div class="section-icon">🌾</div>
                <div class="section-title">
                    <h3>Parcelas con este Cultivo</h3>
                    <p class="section-description">Sin parcelas asociadas</p>
                </div>
            </div>

            <div class="empty-state">
                <span style="font-size: 48px; display: block; margin-bottom: 15px;">🌾</span>
                <p>Este cultivo aún no está siendo utilizado en ninguna parcela.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animación de entrada para las secciones
        const sections = document.querySelectorAll('.detail-section');
        sections.forEach((section, index) => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            setTimeout(() => {
                section.style.transition = 'all 0.5s ease';
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    });
</script>
@endpush
@endsection