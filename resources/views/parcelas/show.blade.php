@extends('layouts.app')

@section('title', 'Detalles de Parcela')

@push('styles')
<style>
    .form-container {
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

    .header-actions {
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
        font-size: 15px;
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
        box-shadow: var(--shadow-sm);
    }

    .btn-edit {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: var(--white);
        box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
    }

    .btn-edit:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
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

    /* Estado Badge */
    .estado-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .estado-activa {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
        border: 2px solid #28a745;
    }

    .estado-inactiva {
        background: linear-gradient(135deg, #f8d7da, #f5c2c7);
        color: #721c24;
        border: 2px solid #dc3545;
    }

    .estado-mantenimiento {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        color: #856404;
        border: 2px solid #ffc107;
    }

    /* Alertas de Riego */
    .alerta-riego {
        padding: 20px 25px;
        border-radius: 14px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 20px;
        font-weight: 700;
        animation: pulse 2s infinite;
    }

    .alerta-critica {
        background: linear-gradient(135deg, #f8d7da, #f5c2c7);
        border: 3px solid #dc3545;
        color: #721c24;
    }

    .alerta-hoy {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        border: 3px solid #ffc107;
        color: #856404;
    }

    .alerta-proximo {
        background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        border: 3px solid #17a2b8;
        color: #0c5460;
    }

    .alerta-ok {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border: 3px solid #28a745;
        color: #155724;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }

    .alerta-icon {
        font-size: 40px;
        flex-shrink: 0;
    }

    .alerta-content {
        flex: 1;
    }

    .alerta-title {
        font-size: 18px;
        margin-bottom: 5px;
    }

    .alerta-description {
        font-size: 14px;
        font-weight: 600;
        opacity: 0.9;
    }

    /* Cards Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--white);
        border-radius: 16px;
        padding: 25px;
        box-shadow: var(--shadow-sm);
        border: 2px solid #f0f0f0;
        transition: var(--transition);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary);
    }

    .stat-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.25);
    }

    .stat-label {
        font-size: 13px;
        color: var(--muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 800;
        color: var(--dark);
    }

    .stat-unit {
        font-size: 16px;
        color: var(--muted);
        font-weight: 600;
    }

    /* Form Card */
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

    .info-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .info-item {
        background: #f8f9fa;
        padding: 18px;
        border-radius: 10px;
        border: 2px solid #e9ecef;
    }

    .info-label {
        font-size: 12px;
        color: var(--muted);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .info-value {
        font-size: 16px;
        font-weight: 700;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #28a745;
    }

    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #17a2b8;
    }

    .progress-bar-container {
        background: #e9ecef;
        border-radius: 10px;
        height: 12px;
        overflow: hidden;
        margin-top: 10px;
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 10px;
        transition: width 0.6s ease;
    }

    /* Tabla de Eventos */
    .eventos-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .eventos-table thead {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    }

    .eventos-table th {
        padding: 15px;
        text-align: left;
        font-weight: 700;
        color: var(--dark);
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 3px solid var(--primary);
    }

    .eventos-table td {
        padding: 15px;
        border-bottom: 1px solid #e9ecef;
        font-weight: 600;
        color: #495057;
    }

    .eventos-table tbody tr:hover {
        background: #f8fff9;
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: var(--muted);
    }

    .empty-state-icon {
        font-size: 60px;
        margin-bottom: 15px;
    }

    /* Formulario de Riego */
    .riego-form {
        background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        padding: 25px;
        border-radius: 14px;
        border: 2px solid #17a2b8;
        margin-top: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 8px;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 15px;
        transition: var(--transition);
        background: var(--white);
        color: var(--dark);
        font-weight: 500;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
        background: #f8fff9;
    }

    .btn-riego {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: var(--white);
        box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
        width: 100%;
        margin-top: 10px;
        padding: 14px;
        justify-content: center;
    }

    .btn-riego:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
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

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }

        .header-actions {
            width: 100%;
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .info-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="form-container">
    {{-- HEADER --}}
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-icon"><x-icono name="parcela" :size="20" /></div>
            <div class="page-title-group">
                <h1>{{ $parcela->nombre }}</h1>
                @if($parcela->alertas()->activas()->exists())
                    <div style="display: inline-flex; align-items: center; gap: 10px; margin-left: 20px;">
                        <a href="{{ route('alertas.index') }}" style="
                            background: linear-gradient(135deg, #dc3545, #c82333);
                            color: white;
                            padding: 8px 16px;
                            border-radius: 20px;
                            text-decoration: none;
                            font-weight: 600;
                            font-size: 14px;
                            display: flex;
                            align-items: center;
                            gap: 8px;
                            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
                            transition: all 0.3s;
                        " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                            {{ $parcela->alertas()->activas()->count() }} alerta(s) activa(s)
                        </a>
                    </div>
                @endif
                <p class="page-subtitle">{{ $parcela->ubicacion_completa }}</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ route('parcelas.index') }}" class="btn btn-back">
                <span>←</span>
                <span>Volver</span>
            </a>
            <a href="{{ route('parcelas.edit', $parcela) }}" class="btn btn-edit">
                
                <span>Editar</span>
            </a>
            <form action="{{ route('parcelas.destroy', $parcela) }}" method="POST" style="display: inline;"
                  onsubmit="return confirm('¿Estás seguro de eliminar esta parcela? Esta acción no se puede deshacer.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-delete">
                    
                    <span>Eliminar</span>
                </button>
            </form>
            {{-- En el card de cada parcela o en la vista show --}}
            <a href="{{ route('recomendaciones.index', $parcela) }}" class="btn btn-primary">
                <span><x-icono name="consejo" :size="20" /></span>
                <span>Ver Recomendaciones</span>
            </a>

        </div>
    </div>

    {{-- ALERTAS --}}
    @if(session('success'))
        <div class="alert alert-success">
            <span style="font-size: 24px;"><x-icono name="ok" :size="20" /></span>
            <strong>{{ session('success') }}</strong>
        </div>
    @endif

    {{-- ALERTA DE RIEGO --}}
    @php
        $estadoRiego = $parcela->estado_riego;
        $diasParaRiego = $parcela->dias_para_riego;
    @endphp

    @if($estadoRiego === 'critico')
        <div class="alerta-riego alerta-critica">
            <div class="alerta-icon"><x-icono name="alerta" :size="20" /></div>
            <div class="alerta-content">
                <div class="alerta-title">¡RIEGO URGENTE!</div>
                <div class="alerta-description">
                    Esta parcela necesitaba riego hace {{ abs($diasParaRiego) }} día(s). ¡Regar inmediatamente!
                </div>
            </div>
        </div>
    @elseif($estadoRiego === 'hoy')
        <div class="alerta-riego alerta-hoy">
            <div class="alerta-icon"><x-icono name="agua" :size="20" /></div>
            <div class="alerta-content">
                <div class="alerta-title">Riego Programado para HOY</div>
                <div class="alerta-description">
                    Es momento de regar esta parcela. Cantidad recomendada: {{ number_format($parcela->calcularLitrosPorRiego(), 2) }} litros
                </div>
            </div>
        </div>
    @elseif($estadoRiego === 'proximo')
        <div class="alerta-riego alerta-proximo">
            <div class="alerta-icon"><x-icono name="reloj" :size="20" /></div>
            <div class="alerta-content">
                <div class="alerta-title">Próximo Riego Cercano</div>
                <div class="alerta-description">
                    Riego programado en {{ $diasParaRiego }} día(s) - {{ $parcela->proximo_riego_estimado->format('d/m/Y') }}
                </div>
            </div>
        </div>
    @else
        <div class="alerta-riego alerta-ok">
            <div class="alerta-icon"><x-icono name="ok" :size="20" /></div>
            <div class="alerta-content">
                <div class="alerta-title">Riego al Día</div>
                <div class="alerta-description">
                    Próximo riego: {{ $parcela->proximo_riego_estimado->format('d/m/Y') }} (en {{ $diasParaRiego }} días)
                </div>
            </div>
        </div>
    @endif

    {{-- ESTADÍSTICAS RÁPIDAS --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon"></div>
                <div class="stat-label">Área Total</div>
            </div>
            <div class="stat-value">
                {{ number_format($parcela->area, 2) }}
                <span class="stat-unit">m²</span>
            </div>
        </div>

       <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon"><x-icono name="agua" :size="20" /></div>
                <div class="stat-label">Agua por Riego</div>
            </div>
            <div class="stat-value">
                @php
                    $litrosCalculados = $parcela->calcularLitrosPorRiego();
                @endphp
                {{ number_format($litrosCalculados, 2) }}
                <span class="stat-unit">L</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon"><x-icono name="ciclo" :size="20" /></div>
                <div class="stat-label">Frecuencia de Riego</div>
            </div>
            <div class="stat-value">
                {{ $parcela->frecuencia_riego_dias }}
                <span class="stat-unit">días</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon"><x-icono name="parcela" :size="20" /></div>
                <div class="stat-label">Días desde Siembra</div>
            </div>
            <div class="stat-value">
                {{ $parcela->dias_desde_siembra ?? 'N/A' }}
                @if($parcela->dias_desde_siembra)
                    <span class="stat-unit">días</span>
                @endif
            </div>
        </div>
    </div>

    {{-- INFORMACIÓN DETALLADA --}}
    <div class="form-card">
        {{-- INFORMACIÓN BÁSICA --}}
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon"></div>
                <div class="section-title">
                    <h3>Información Básica</h3>
                    <p class="section-description">Datos principales de la parcela</p>
                </div>
            </div>

            <div class="info-row">
                <div class="info-item">
                    <div class="info-label">Nombre de la Parcela</div>
                    <div class="info-value">{{ $parcela->nombre }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Estado</div>
                    <div class="info-value">
                        @if($parcela->estado === 'activa')
                            <span class="estado-badge estado-activa">Activa</span>
                        @elseif($parcela->estado === 'inactiva')
                            <span class="estado-badge estado-inactiva">Inactiva</span>
                        @else
                            <span class="estado-badge estado-mantenimiento">Mantenimiento</span>
                        @endif
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Tipo de Cultivo</div>
                    <div class="info-value">
                        {{ $parcela->cultivo->nombreCultivo }}
                        <span class="badge badge-info">{{ $parcela->cultivo->tipo }}</span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Área</div>
                    <div class="info-value">{{ number_format($parcela->area, 2) }} m²</div>
                </div>
            </div>

            @if($parcela->progreso_siembra !== null)
                <div class="info-item">
                    <div class="info-label">Progreso del Cultivo</div>
                    <div class="info-value">
                        {{ $parcela->progreso_siembra }}% - Etapa: 
                        <span class="badge badge-success">{{ ucfirst($parcela->etapa_crecimiento) }}</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: {{ $parcela->progreso_siembra }}%"></div>
                    </div>
                </div>
            @endif
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

            <div class="info-row">
                <div class="info-item">
                    <div class="info-label">Región</div>
                    <div class="info-value">{{ $parcela->ubicacion->region }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Provincia</div>
                    <div class="info-value">{{ $parcela->ubicacion->provincia }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Distrito</div>
                    <div class="info-value">{{ $parcela->ubicacion->distrito }}</div>
                </div>
            </div>

            @if($parcela->ubicacion->coordenadaLatitud && $parcela->ubicacion->coordenadaLongitud)
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Coordenadas GPS</div>
                        <div class="info-value">
                            {{ $parcela->ubicacion->coordenadaLatitud }}, {{ $parcela->ubicacion->coordenadaLongitud }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- INFORMACIÓN DE RIEGO --}}
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon"><x-icono name="agua" :size="20" /></div>
                <div class="section-title">
                    <h3>Gestión de Riego</h3>
                    <p class="section-description">Control y seguimiento del riego</p>
                </div>
            </div>

            <div class="info-row">
                <div class="info-item">
                    <div class="info-label">Último Riego</div>
                    <div class="info-value">
                        {{ $parcela->ultimo_riego ? $parcela->ultimo_riego->format('d/m/Y H:i') : 'Sin registros' }}
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Próximo Riego Estimado</div>
                    <div class="info-value">
                        {{ $parcela->proximo_riego_estimado->format('d/m/Y') }}
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Frecuencia de Riego</div>
                    <div class="info-value">
                        Cada {{ $parcela->frecuencia_riego_dias }} días
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Cantidad por Riego</div>
                    <div class="info-value">
                        {{ number_format($parcela->litros_por_riego, 2) }} litros
                    </div>
                </div>
            </div>

            @if($parcela->total_agua_utilizada > 0)
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Total Agua Utilizada</div>
                        <div class="info-value">
                            {{ number_format($parcela->total_agua_utilizada, 2) }} litros
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Promedio por Riego</div>
                        <div class="info-value">
                            {{ number_format($parcela->promedio_agua_por_riego, 2) }} litros
                        </div>
                    </div>
                </div>
            @endif

            {{-- FORMULARIO DE REGISTRAR RIEGO --}}
            <div class="riego-form">
                <h4 style="margin-bottom: 15px; font-weight: 800;">Registrar Nuevo Riego</h4>
                <form action="{{ route('parcelas.registrar-riego', $parcela) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="notas" class="form-label">
                            Notas (Opcional)
                        </label>
                        <textarea 
                            name="notas" 
                            id="notas" 
                            class="form-control"
                            rows="2"
                            placeholder="Observaciones sobre este riego..."
                        ></textarea>
                    </div>

                    <button type="submit" class="btn btn-riego">
                        <span><x-icono name="agua" :size="20" /></span>
                        <span>Registrar Riego Ahora</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- HIDROGEL --}}
        @if($parcela->tieneHidrogelAplicado)
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon"><x-icono name="agua" :size="20" /></div>
                    <div class="section-title">
                        <h3>Hidrogel Aplicado</h3>
                        <p class="section-description">Reduce consumo de agua hasta 25%</p>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Estado</div>
                        <div class="info-value">
                            <span class="badge badge-success">Activo</span>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Fecha de Aplicación</div>
                        <div class="info-value">
                            {{ $parcela->hidrogel_fecha_aplicacion->format('d/m/Y') }}
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Cantidad Aplicada</div>
                        <div class="info-value">
                            {{ number_format($parcela->hidrogel_cantidad_kg, 2) }} kg
                        </div>
                    </div>
                </div>

                <div style="background: linear-gradient(135deg, #d1ecf1, #bee5eb); padding: 15px; border-radius: 10px; margin-top: 15px; border: 2px solid #17a2b8;">
                    <span style="font-size: 20px;"><x-icono name="consejo" :size="20" /></span>
                    <span style="color: #0c5460; font-weight: 700; margin-left: 10px;">
                        El hidrogel extiende la frecuencia de riego en +2 días
                    </span>
                </div>
            </div>
        @endif

        {{-- FECHAS IMPORTANTES --}}
        @if($parcela->fecha_siembra)
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon"><x-icono name="ciclo" :size="20" /></div>
                    <div class="section-title">
                        <h3>Fechas del Cultivo</h3>
                        <p class="section-description">Seguimiento del ciclo de vida</p>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Fecha de Siembra</div>
                        <div class="info-value">
                            {{ $parcela->fecha_siembra->format('d/m/Y') }}
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Días Transcurridos</div>
                        <div class="info-value">
                            {{ $parcela->dias_desde_siembra }} días
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Etapa Actual</div>
                        <div class="info-value">
                            <span class="badge badge-success">
                                {{ ucfirst($parcela->etapa_crecimiento) }}
                            </span>
                        </div>
                    </div>

                    @if($parcela->cultivo->cicloProductivo)
                        <div class="info-item">
                            <div class="info-label">Ciclo Total Estimado</div>
                            <div class="info-value">
                                {{ $parcela->cultivo->cicloProductivo }} días
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- NOTAS --}}
        @if($parcela->notas_usuario)
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon"></div>
                    <div class="section-title">
                        <h3>Notas y Observaciones</h3>
                        <p class="section-description">Información adicional</p>
                    </div>
                </div>

                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; border: 2px solid #e9ecef;">
                    <p style="margin: 0; color: var(--dark); font-weight: 600; line-height: 1.6;">
                        {{ $parcela->notas_usuario }}
                    </p>
                </div>
            </div>
        @endif

        {{-- HISTORIAL DE RIEGOS --}}
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon"><x-icono name="ciclo" :size="20" /></div>
                <div class="section-title">
                    <h3>Historial de Riegos</h3>
                    <p class="section-description">Últimos 20 riegos registrados</p>
                </div>
            </div>

            @if($parcela->eventosRiego->count() > 0)
                <div style="overflow-x: auto;">
                    <table class="eventos-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Cantidad (L)</th>
                                <th>Tipo</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($parcela->eventosRiego as $evento)
                                <tr>
                                    <td>{{ $evento->fecha_riego->format('d/m/Y') }}</td>
                                    <td>{{ $evento->fecha_riego->format('H:i') }}</td>
                                    <td>
                                        <strong>{{ number_format($evento->cantidad_agua, 2) }}</strong> L
                                    </td>
                                    <td>
                                        @if($evento->tipo_riego === 'manual')
                                            <span class="badge badge-info">Manual</span>
                                        @else
                                            <span class="badge badge-success">Automático</span>
                                        @endif
                                    </td>
                                    <td>{{ $evento->notas ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon"><x-icono name="agua" :size="20" /></div>
                    <p style="font-weight: 700; color: var(--muted);">
                        Aún no hay riegos registrados para esta parcela
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection-group">
                        <label for="cantidad_agua" class="form-label">
                            Cantidad de Agua (litros)
                        </label>
                        @php
                            $litrosRecomendados = $parcela->calcularLitrosPorRiego();
                        @endphp
                        <input 
                            type="number" 
                            name="cantidad_agua" 
                            id="cantidad_agua" 
                            class="form-control"
                            value="{{ $litrosRecomendados }}"
                            step="0.01"
                            min="0"
                            placeholder="Litros utilizados"
                        >
                        <small style="color: #0c5460; font-weight: 600; margin-top: 5px; display: block;">
                            Recomendado: {{ number_format($litrosRecomendados, 2) }} litros
                        </small>
                    </div>

                    <div class="form