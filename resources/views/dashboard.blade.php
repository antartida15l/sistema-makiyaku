@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    /* Variables CSS para consistencia */
    :root {
        --primary-color: #28a745;
        --secondary-color: #20c997;
        --danger-color: #dc3545;
        --warning-color: #ffc107;
        --info-color: #007bff;
        --success-color: #28a745;
        --light-bg: #f8f9fa;
        --white: #ffffff;
        --text-dark: #212529;
        --text-muted: #6c757d;
        --border-radius: 15px;
        --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        --box-shadow-hover: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    /* Welcome Banner */
    .welcome-banner {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border-radius: var(--border-radius);
        padding: 30px;
        margin-bottom: 30px;
        color: white;
        box-shadow: var(--box-shadow-hover);
        position: relative;
        overflow: hidden;
    }

    .welcome-banner::before {
        content: '🌱';
        position: absolute;
        right: 30px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 80px;
        opacity: 0.2;
    }

    .welcome-title {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 8px;
    }

    .welcome-subtitle {
        font-size: 16px;
        opacity: 0.9;
    }

    .welcome-date {
        font-size: 14px;
        opacity: 0.8;
        margin-top: 5px;
    }

    /* Stats Grid - Mejorado */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
        margin-bottom: 35px;
    }

    .stat-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 28px;
        box-shadow: var(--box-shadow);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, transparent, currentColor, transparent);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--box-shadow-hover);
        cursor: pointer;
    }

    .stat-card:hover::before {
        opacity: 1;
    }

    .stat-card.primary::before { color: var(--info-color); }
    .stat-card.success::before { color: var(--success-color); }
    .stat-card.warning::before { color: var(--warning-color); }
    .stat-card.danger::before { color: var(--danger-color); }

    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .stat-content {
        flex: 1;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        flex-shrink: 0;
    }

    .stat-icon.primary {
        background: linear-gradient(135deg, #007bff, #0056b3);
    }

    .stat-icon.success {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .stat-icon.warning {
        background: linear-gradient(135deg, #ffc107, #ff9800);
    }

    .stat-icon.danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }

    .stat-value {
        font-size: 36px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 8px;
        line-height: 1;
    }

    .stat-label {
        color: var(--text-muted);
        font-size: 14px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #f0f0f0;
    }

    .stat-change {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
    }

    .stat-change.positive {
        color: var(--success-color);
    }

    .stat-change.negative {
        color: var(--danger-color);
    }

    .stat-change-icon {
        font-size: 16px;
    }

    .stat-period {
        font-size: 12px;
        color: var(--text-muted);
    }

    /* Charts Grid - Ajustado para mejor distribución */
    .charts-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 25px;
        margin-bottom: 35px;
    }

    .chart-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 28px;
        box-shadow: var(--box-shadow);
        transition: all 0.3s;
    }

    .chart-card:hover {
        box-shadow: var(--box-shadow-hover);
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .chart-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chart-icon {
        width: 35px;
        height: 35px;
        background: linear-gradient(135deg, #28a745, #20c997);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    /* Ajuste de altura del gráfico - MÁS COMPACTO */
    .chart-placeholder {
        height: 280px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        font-size: 14px;
        border: 2px dashed #dee2e6;
        transition: all 0.3s;
    }

    #humedadChart, #comparativaChart, #pronosticoChart {
        height: 280px !important;
    }

    .chart-placeholder:hover {
        border-color: var(--primary-color);
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    }

    .chart-placeholder-content {
        text-align: center;
    }

    .chart-placeholder-icon {
        font-size: 56px;
        margin-bottom: 15px;
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    .chart-placeholder-title {
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 5px;
    }

    .chart-placeholder-subtitle {
        font-size: 12px;
        opacity: 0.7;
    }

    /* Filter Select - Mejorado */
    .filter-select {
        padding: 10px 16px;
        border-radius: 10px;
        border: 2px solid #e9ecef;
        background: var(--white);
        color: var(--text-dark);
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        outline: none;
    }

    .filter-select:hover {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
    }

    .filter-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.15);
    }

    /* Activity List - Mejorado y más compacto */
    .activity-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 28px;
        box-shadow: var(--box-shadow);
    }

    .activity-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .activity-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .activity-title-icon {
        width: 35px;
        height: 35px;
        background: linear-gradient(135deg, #007bff, #0056b3);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .activity-item {
        display: flex;
        gap: 18px;
        padding: 15px 0;
        border-bottom: 1px solid #f5f5f5;
        transition: all 0.3s;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-item:hover {
        background: #f8f9fa;
        padding-left: 10px;
        padding-right: 10px;
        margin: 0 -10px;
        border-radius: 8px;
    }

    .activity-icon {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .activity-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .activity-item-title {
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 4px;
        font-size: 14px;
    }

    .activity-time {
        font-size: 12px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .activity-time::before {
        content: '🕐';
        font-size: 12px;
    }

    /* Distribución de cultivos mejorada */
    .cultivo-item {
        margin-bottom: 15px;
    }

    .cultivo-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        align-items: center;
    }

    .cultivo-nombre {
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .cultivo-stats {
        color: #6c757d;
        font-size: 13px;
    }

    .cultivo-bar-container {
        background: #e9ecef;
        height: 10px;
        border-radius: 5px;
        overflow: hidden;
        margin-bottom: 5px;
    }

    .cultivo-bar {
        background: linear-gradient(90deg, #28a745, #20c997);
        height: 100%;
        transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .cultivo-area {
        font-size: 12px;
        color: #6c757d;
    }

    /* Quick Actions Section */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 35px;
    }

    .quick-action-btn {
        background: var(--white);
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        color: var(--text-dark);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        position: relative;
    }

    .quick-action-btn:hover {
        border-color: var(--primary-color);
        transform: translateY(-5px);
        box-shadow: var(--box-shadow-hover);
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.05), rgba(32, 201, 151, 0.05));
    }

    .quick-action-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #28a745, #20c997);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        position: relative;
    }

    .quick-action-label {
        font-weight: 600;
        font-size: 14px;
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ffc107;
        color: #000;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .welcome-banner {
            padding: 25px 20px;
        }

        .welcome-banner::before {
            font-size: 60px;
            right: 15px;
        }

        .welcome-title {
            font-size: 22px;
        }

        .stats-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .stat-card {
            padding: 22px;
        }

        .stat-value {
            font-size: 32px;
        }

        .chart-placeholder {
            height: 220px;
        }

        #humedadChart, #comparativaChart, #pronosticoChart {
            height: 220px !important;
        }

        .quick-actions {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 480px) {
        .quick-actions {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<!-- Welcome Banner -->
<div class="welcome-banner">
    <div class="welcome-title">👋 ¡Bienvenido de nuevo, {{ Auth::user()->name }}!</div>
    <div class="welcome-subtitle">Aquí está el resumen de tu sistema de riego inteligente</div>
    <div class="welcome-date">📅 {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="{{ route('parcelas.create') }}" class="quick-action-btn">
        <div class="quick-action-icon">➕</div>
        <div class="quick-action-label">Nueva Parcela</div>
    </a>
    <a href="{{ route('parcelas.index') }}" class="quick-action-btn">
        <div class="quick-action-icon">🌾</div>
        <div class="quick-action-label">Ver Parcelas</div>
    </a>
    <a href="{{ route('clima.index') }}" class="quick-action-btn">
        <div class="quick-action-icon">🌤️</div>
        <div class="quick-action-label">Monitorear Clima</div>
    </a>
    <a href="#" class="quick-action-btn">
        <div class="quick-action-icon">📊</div>
        <div class="quick-action-label">Reportes</div>
    </a>
</div>

<!-- Dashboard Stats -->
<div class="stats-grid">
      {{-- ✅ ALERTAS ACTIVAS - CLICKEABLE --}}
    <a href="{{ route('alertas.index') }}" class="stat-card danger">
        <div class="stat-header">
            <div class="stat-content">
                <div class="stat-value">{{ $alertasActivas }}</div>
                <div class="stat-label">Alertas Activas</div>
            </div>
            <div class="stat-icon danger">🔔</div>
        </div>
        <div class="stat-footer">
            <div class="stat-change {{ $nuevasAlertas > 0 ? 'negative' : 'positive' }}">
                <span class="stat-change-icon">{{ $nuevasAlertas > 0 ? '+' : '✓' }}</span>
                <span>{{ $nuevasAlertas }}</span>
            </div>
            <div class="stat-period">{{ $nuevasAlertas == 1 ? 'nueva alerta' : 'nuevas hoy' }}</div>
        </div>
    </a>

    {{-- ✅ PARCELAS ACTIVAS - DATOS REALES --}}
    <div class="stat-card primary">
        <div class="stat-header">
            <div class="stat-content">
                <div class="stat-value">{{ $totalParcelas }}</div>
                <div class="stat-label">Parcelas Activas</div>
            </div>
            <div class="stat-icon primary">🌾</div>
        </div>
        <div class="stat-footer">
            <div class="stat-change {{ $parcelasNuevas > 0 ? 'positive' : '' }}">
                <span class="stat-change-icon">{{ $parcelasNuevas > 0 ? '↑' : '•' }}</span>
                <span>{{ $parcelasNuevas }}</span>
            </div>
            <div class="stat-period">este mes</div>
        </div>
    </div>

    {{-- ✅ CLIMA PROMEDIO EN TIEMPO REAL - DESDE API --}}
    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-content">
                @if($climaPromedio['hay_datos'])
                    <div class="stat-value">{{ $climaPromedio['temperatura'] }}°C</div>
                    <div class="stat-label">Temperatura Promedio</div>
                @else
                    <div class="stat-value">--</div>
                    <div class="stat-label">Sin Datos de Clima</div>
                @endif
            </div>
            <div class="stat-icon success">🌡️</div>
        </div>
        <div class="stat-footer">
            @if($climaPromedio['hay_datos'])
                <div class="stat-change">
                    <span>💧 {{ $climaPromedio['humedad'] }}%</span>
                </div>
                <div class="stat-period">humedad actual</div>
            @else
                <div class="stat-period">
                    <a href="{{ route('clima.index') }}" style="color: #28a745; font-weight: 600;">
                        Configurar clima →
                    </a>
                </div>
            @endif
        </div>
    </div>

</div>

<!-- Charts Section -->
<div class="charts-grid">
    {{-- ✅ COMPARATIVA DE CLIMA ENTRE PARCELAS - TIEMPO REAL --}}
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <div class="chart-icon">🌡️</div>
                <span>Comparativa de Clima (Tiempo Real)</span>
            </div>
            <button class="filter-select" id="btnRefrescarClima" style="cursor: pointer;">
                🔄 Actualizar
            </button>
        </div>
        <div class="chart-placeholder" id="chartComparativa">
            <div class="chart-placeholder-content">
                <div class="chart-placeholder-icon">🌤️</div>
                <div class="chart-placeholder-title">Cargando clima en tiempo real...</div>
                <div class="chart-placeholder-subtitle">Obteniendo datos desde OpenWeather API</div>
            </div>
        </div>
        <canvas id="comparativaChart" style="display: none;"></canvas>
    </div>

    {{-- ✅ DISTRIBUCIÓN POR CULTIVO - MEJORADA --}}
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <div class="chart-icon">🥧</div>
                <span>Distribución por Cultivo</span>
            </div>
        </div>
        @if(count($distribucionCultivos) > 0)
            <div style="padding: 15px 0;">
                @foreach($distribucionCultivos as $cultivo)
                    <div class="cultivo-item">
                        <div class="cultivo-header">
                            <span class="cultivo-nombre">
                                {{ $cultivo['emoji'] ?? '🌾' }} {{ $cultivo['nombre'] }}
                            </span>
                            <span class="cultivo-stats">
                                {{ $cultivo['cantidad'] }} parcela(s) • {{ $cultivo['porcentaje'] }}%
                            </span>
                        </div>
                        <div class="cultivo-bar-container">
                            <div class="cultivo-bar" style="width: {{ $cultivo['porcentaje'] }}%;"></div>
                        </div>
                        <div class="cultivo-area">
                            Área total: {{ $cultivo['area'] }} m²
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="chart-placeholder">
                <div class="chart-placeholder-content">
                    <div class="chart-placeholder-icon">🌱</div>
                    <div class="chart-placeholder-title">Sin Cultivos</div>
                    <div class="chart-placeholder-subtitle">Crea tu primera parcela para ver datos</div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Pronóstico 5 días + Actividades -->
<div class="charts-grid">
    {{-- ✅ PRONÓSTICO 5 DÍAS - DESDE API --}}
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <div class="chart-icon">📅</div>
                <span>Pronóstico (5 días)</span>
            </div>
            <select class="filter-select" id="parcelaFilterPronostico">
                <option value="all">Todas las parcelas</option>
                @foreach($parcelas as $parcela)
                    <option value="{{ $parcela->id }}">{{ $parcela->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="chart-placeholder" id="chartPronostico">
            <div class="chart-placeholder-content">
                <div class="chart-placeholder-icon">⛅</div>
                <div class="chart-placeholder-title">Cargando pronóstico...</div>
                <div class="chart-placeholder-subtitle">Obteniendo datos climáticos</div>
            </div>
        </div>
        <canvas id="pronosticoChart" style="display: none;"></canvas>
    </div>

    {{-- ✅ ACTIVIDAD RECIENTE --}}
    <div class="activity-card">
        <div class="activity-header">
            <div class="activity-title">
                <div class="activity-title-icon">⚡</div>
                <span>Actividad Reciente</span>
            </div>
        </div>

        <div class="activity-list">
            @if(count($actividadesRecientes) > 0)
                @foreach($actividadesRecientes as $actividad)
                    <div class="activity-item">
                        <div class="activity-icon" style="background: {{ $actividad['color'] }}; color: {{ $actividad['iconColor'] }};">
                            {{ $actividad['icon'] }}
                        </div>
                        <div class="activity-content">
                            <div class="activity-item-title">{{ $actividad['titulo'] }}</div>
                            <div class="activity-time">{{ $actividad['tiempo'] }}</div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="activity-item">
                    <div class="activity-icon" style="background: rgba(0, 123, 255, 0.1); color: #007bff;">🌱</div>
                    <div class="activity-content">
                        <div class="activity-item-title">¡Sistema iniciado! Crea tu primera parcela para comenzar</div>
                        <div class="activity-time">Ahora</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ═══════════════════════════════════════════════════════════
// DASHBOARD - SCRIPT COMPLETO
// ═══════════════════════════════════════════════════════════

let comparativaChart = null;
let pronosticoChart = null;

// ═══════════════════════════════════════════════════════════
// CARGAR DATOS AL INICIAR
// ═══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    cargarComparativaClima();
    cargarPronostico('all');
    
    // Animar las barras de cultivos
    animarBarrasCultivos();
});

// ═══════════════════════════════════════════════════════════
// EVENTOS DE FILTROS
// ═══════════════════════════════════════════════════════════
document.getElementById('btnRefrescarClima')?.addEventListener('click', function() {
    this.innerHTML = '⏳ Cargando...';
    this.disabled = true;
    cargarComparativaClima();
});

document.getElementById('parcelaFilterPronostico')?.addEventListener('change', function(e) {
    cargarPronostico(e.target.value);
});

// ═══════════════════════════════════════════════════════════
// FUNCIÓN: Comparativa de Clima entre Parcelas
// ═══════════════════════════════════════════════════════════
function cargarComparativaClima() {
    const btn = document.getElementById('btnRefrescarClima');
    
    fetch('{{ route("dashboard.comparativa") }}')
        .then(response => response.json())
        .then(result => {
            if (!result.success || result.data.length === 0) {
                mostrarMensajeSinDatos('chartComparativa', '❌ No hay datos de clima disponibles', 'Verifica que las parcelas tengan ubicación configurada');
                return;
            }

            const datos = result.data;
            
            // Ocultar placeholder y mostrar canvas
            document.getElementById('chartComparativa').style.display = 'none';
            document.getElementById('comparativaChart').style.display = 'block';

            // Destruir gráfico anterior si existe
            if (comparativaChart) {
                comparativaChart.destroy();
            }

            const ctx = document.getElementById('comparativaChart').getContext('2d');
            comparativaChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: datos.map(d => d.parcela),
                    datasets: [
                        {
                            label: 'Temperatura (°C)',
                            data: datos.map(d => d.temperatura),
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 2,
                            borderRadius: 8,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Humedad (%)',
                            data: datos.map(d => d.humedad),
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 2,
                            borderRadius: 8,
                            yAxisID: 'y1'
                        },
                        {
                            label: 'Viento (km/h)',
                            data: datos.map(d => d.viento),
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 2,
                            borderRadius: 8,
                            yAxisID: 'y'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            borderColor: 'rgba(255, 255, 255, 0.2)',
                            borderWidth: 1,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                title: function(context) {
                                    const idx = context[0].dataIndex;
                                    return `${datos[idx].emoji} ${datos[idx].parcela}`;
                                },
                                afterTitle: function(context) {
                                    const idx = context[0].dataIndex;
                                    return datos[idx].descripcion;
                                },
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.y;
                                    if (context.dataset.label === 'Temperatura (°C)') {
                                        label += '°C';
                                    } else if (context.dataset.label === 'Humedad (%)') {
                                        label += '%';
                                    } else if (context.dataset.label === 'Viento (km/h)') {
                                        label += ' km/h';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Temperatura (°C) / Viento (km/h)',
                                font: {
                                    size: 11,
                                    weight: '600'
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Humedad (%)',
                                font: {
                                    size: 11,
                                    weight: '600'
                                }
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            max: 100
                        }
                    }
                }
            });

            if (btn) {
                btn.innerHTML = '🔄 Actualizar';
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error al cargar clima:', error);
            mostrarMensajeSinDatos('chartComparativa', '⚠️ Error al cargar datos', 'Por favor, intenta nuevamente');
            if (btn) {
                btn.innerHTML = '🔄 Actualizar';
                btn.disabled = false;
            }
        });
}

// ═══════════════════════════════════════════════════════════
// FUNCIÓN: Pronóstico 5 días
// ═══════════════════════════════════════════════════════════
function cargarPronostico(parcelaId) {
    const chartPlaceholder = document.getElementById('chartPronostico');
    const canvas = document.getElementById('pronosticoChart');

    // Mostrar loading
    chartPlaceholder.innerHTML = `
        <div class="chart-placeholder-content">
            <div class="chart-placeholder-icon">⏳</div>
            <div class="chart-placeholder-title">Cargando pronóstico...</div>
            <div class="chart-placeholder-subtitle">Obteniendo datos climáticos</div>
        </div>
    `;
    chartPlaceholder.style.display = 'flex';
    canvas.style.display = 'none';

    fetch(`{{ route("dashboard.pronostico") }}?parcela_id=${parcelaId}`)
        .then(response => response.json())
        .then(result => {
            if (!result.success || result.data.length === 0) {
                mostrarMensajeSinDatos('chartPronostico', '❌ No hay pronóstico disponible', 'Verifica que la parcela tenga ubicación configurada');
                return;
            }

            const datos = result.data;
            
            chartPlaceholder.style.display = 'none';
            canvas.style.display = 'block';

            if (pronosticoChart) {
                pronosticoChart.destroy();
            }

            const ctx = canvas.getContext('2d');
            pronosticoChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: datos.map(d => `${d.dia_semana} ${d.fecha}`),
                    datasets: [
                        {
                            label: 'Temp. Máxima (°C)',
                            data: datos.map(d => d.temp_max),
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        },
                        {
                            label: 'Temp. Mínima (°C)',
                            data: datos.map(d => d.temp_min),
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        },
                        {
                            label: 'Prob. Lluvia (%)',
                            data: datos.map(d => d.lluvia_prob),
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            borderWidth: 3,
                            borderDash: [5, 5],
                            tension: 0.4,
                            fill: false,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            borderColor: 'rgba(255, 255, 255, 0.2)',
                            borderWidth: 1,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                title: function(context) {
                                    const idx = context[0].dataIndex;
                                    return `${datos[idx].emoji} ${datos[idx].dia_semana} ${datos[idx].fecha}`;
                                },
                                afterTitle: function(context) {
                                    const idx = context[0].dataIndex;
                                    return datos[idx].descripcion;
                                },
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.y;
                                    if (label.includes('Temp')) {
                                        label += '°C';
                                    } else if (label.includes('Prob')) {
                                        label += '%';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                    weight: '600'
                                }
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Temperatura (°C)',
                                font: {
                                    size: 11,
                                    weight: '600'
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Probabilidad Lluvia (%)',
                                font: {
                                    size: 11,
                                    weight: '600'
                                }
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            max: 100
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error al cargar pronóstico:', error);
            mostrarMensajeSinDatos('chartPronostico', '⚠️ Error al cargar pronóstico', 'Por favor, intenta nuevamente');
        });
}

// ═══════════════════════════════════════════════════════════
// HELPER: Mostrar mensaje cuando no hay datos
// ═══════════════════════════════════════════════════════════
function mostrarMensajeSinDatos(containerId, titulo, subtitulo) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = `
        <div class="chart-placeholder-content">
            <div class="chart-placeholder-icon" style="font-size: 48px;">📊</div>
            <div class="chart-placeholder-title">${titulo}</div>
            <div class="chart-placeholder-subtitle">${subtitulo}</div>
        </div>
    `;
    container.style.display = 'flex';

    // Ocultar canvas correspondiente
    const canvasId = containerId.replace('chart', '') + 'Chart';
    const canvas = document.getElementById(canvasId);
    if (canvas) {
        canvas.style.display = 'none';
    }
}

// ═══════════════════════════════════════════════════════════
// ANIMACIÓN: Barras de cultivos
// ═══════════════════════════════════════════════════════════
function animarBarrasCultivos() {
    const barras = document.querySelectorAll('.cultivo-bar');
    
    barras.forEach((barra, index) => {
        const anchoFinal = barra.style.width;
        barra.style.width = '0%';
        
        setTimeout(() => {
            barra.style.transition = 'width 1s cubic-bezier(0.4, 0, 0.2, 1)';
            barra.style.width = anchoFinal;
        }, index * 150);
    });
}
</script>



<script>
// ════════════════════════════════════════════════════════════
// TOAST DE ALERTAS NUEVAS AL ENTRAR
// ════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    // Solo mostrar si es la primera carga (no recarga)
    if (!sessionStorage.getItem('toastMostrado')) {
        setTimeout(() => {
            mostrarToastAlertas();
            sessionStorage.setItem('toastMostrado', 'true');
        }, 1500); // Esperar 1.5s después de cargar
    }
});

function mostrarToastAlertas() {
    fetch('{{ route("alertas.recientes") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.alertas.length > 0) {
                const alertas = data.alertas;
                
                // Crear contenedor de toast
                const toastContainer = document.createElement('div');
                toastContainer.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    max-width: 400px;
                `;
                document.body.appendChild(toastContainer);

                // Crear cada alerta
                alertas.forEach((alerta, index) => {
                    setTimeout(() => {
                        const toast = crearToast(alerta);
                        toastContainer.appendChild(toast);
                        
                        // Animar entrada
                        setTimeout(() => toast.style.transform = 'translateX(0)', 10);
                        
                        // Auto-cerrar después de 8 segundos
                        setTimeout(() => {
                            toast.style.transform = 'translateX(450px)';
                            setTimeout(() => toast.remove(), 300);
                        }, 8000);
                    }, index * 400); // Stagger de 400ms entre alertas
                });

                // Reproducir sonido según nivel
                reproducirSonidoAlerta(alertas[0].nivel);
            }
        })
        .catch(error => console.error('Error cargando toast:', error));
}

function crearToast(alerta) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        background: white;
        border-left: 5px solid ${alerta.color_hex};
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        transform: translateX(450px);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    `;

    toast.innerHTML = `
        <div style="display: flex; gap: 15px; align-items: start;">
            <div style="font-size: 32px; flex-shrink: 0;">
                ${alerta.icono}
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 700; font-size: 16px; color: #212529; margin-bottom: 5px;">
                    ${alerta.titulo}
                </div>
                <div style="font-size: 13px; color: #6c757d; margin-bottom: 5px;">
                    🌾 ${alerta.parcela}
                </div>
                <div style="font-size: 14px; color: #495057; line-height: 1.4; margin-bottom: 10px;">
                    ${alerta.mensaje.substring(0, 120)}${alerta.mensaje.length > 120 ? '...' : ''}
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; color: #6c757d;">
                        ${alerta.tiempo}
                    </span>
                    <span style="font-size: 12px; color: #28a745; font-weight: 600;">
                        Ver detalles →
                    </span>
                </div>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" style="
                background: none;
                border: none;
                font-size: 20px;
                color: #6c757d;
                cursor: pointer;
                padding: 0;
                margin-left: 10px;
            ">×</button>
        </div>
    `;

    // Click para ir a la parcela
    toast.addEventListener('click', function(e) {
        if (e.target.tagName !== 'BUTTON') {
            window.location.href = alerta.url;
        }
    });

    return toast;
}

function reproducirSonidoAlerta(nivel) {
    // Usar API de audio del navegador para sonido simple
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    // Frecuencia según nivel
    const frecuencias = {
        'critico': [800, 600, 800],
        'alto': [600, 500],
        'medio': [500],
        'info': [400]
    };

    const freqs = frecuencias[nivel] || [500];
    
    let time = audioContext.currentTime;
    freqs.forEach((freq, i) => {
        oscillator.frequency.setValueAtTime(freq, time);
        gainNode.gain.setValueAtTime(0.3, time);
        gainNode.gain.exponentialRampToValueAtTime(0.01, time + 0.3);
        time += 0.35;
    });

    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + (freqs.length * 0.35));
}
</script>
@endpush