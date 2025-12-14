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

    /* Charts Grid - Mejorado */
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

    .chart-placeholder {
        height: 320px;
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

    /* Activity List - Mejorado */
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

    .view-all-link {
        color: var(--primary-color);
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .view-all-link:hover {
        color: #1e7e34;
        gap: 8px;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .activity-item {
        display: flex;
        gap: 18px;
        padding: 18px 0;
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
    }

    .quick-action-label {
        font-weight: 600;
        font-size: 14px;
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
            height: 260px;
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
    <a href="#" class="quick-action-btn">
        <div class="quick-action-icon">💧</div>
        <div class="quick-action-label">Monitorear</div>
    </a>
    <a href="#" class="quick-action-btn">
        <div class="quick-action-icon">📊</div>
        <div class="quick-action-label">Reportes</div>
    </a>
</div>

<!-- Dashboard Stats -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-header">
            <div class="stat-content">
                <div class="stat-value">{{ $totalParcelas ?? 0 }}</div>
                <div class="stat-label">Parcelas Activas</div>
            </div>
            <div class="stat-icon primary">🌾</div>
        </div>
        <div class="stat-footer">
            <div class="stat-change positive">
                <span class="stat-change-icon">↑</span>
                <span>{{ $parcelasNuevas ?? 0 }}</span>
            </div>
            <div class="stat-period">este mes</div>
        </div>
    </div>

    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-content">
                <div class="stat-value">{{ $humedadPromedio ?? 0 }}%</div>
                <div class="stat-label">Humedad Promedio</div>
            </div>
            <div class="stat-icon success">💧</div>
        </div>
        <div class="stat-footer">
            <div class="stat-change {{ ($cambioHumedad ?? 0) >= 0 ? 'positive' : 'negative' }}">
                <span class="stat-change-icon">{{ ($cambioHumedad ?? 0) >= 0 ? '↑' : '↓' }}</span>
                <span>{{ abs($cambioHumedad ?? 0) }}%</span>
            </div>
            <div class="stat-period">últimas 24h</div>
        </div>
    </div>

    <div class="stat-card warning">
        <div class="stat-header">
            <div class="stat-content">
                <div class="stat-value">{{ number_format($aguaHoy ?? 0, 0) }}</div>
                <div class="stat-label">Litros de Agua (hoy)</div>
            </div>
            <div class="stat-icon warning">🚿</div>
        </div>
        <div class="stat-footer">
            <div class="stat-change {{ ($cambioAgua ?? 0) >= 0 ? 'negative' : 'positive' }}">
                <span class="stat-change-icon">{{ ($cambioAgua ?? 0) >= 0 ? '↑' : '↓' }}</span>
                <span>{{ abs($cambioAgua ?? 0) }}%</span>
            </div>
            <div class="stat-period">vs ayer</div>
        </div>
    </div>

    <div class="stat-card danger">
        <div class="stat-header">
            <div class="stat-content">
                <div class="stat-value">{{ $alertasActivas ?? 0 }}</div>
                <div class="stat-label">Alertas Activas</div>
            </div>
            <div class="stat-icon danger">🔔</div>
        </div>
        <div class="stat-footer">
            <div class="stat-change {{ ($nuevasAlertas ?? 0) > 0 ? 'negative' : 'positive' }}">
                <span class="stat-change-icon">{{ ($nuevasAlertas ?? 0) > 0 ? '+' : '✓' }}</span>
                <span>{{ $nuevasAlertas ?? 0 }}</span>
            </div>
            <div class="stat-period">{{ ($nuevasAlertas ?? 0) == 1 ? 'nueva alerta' : 'nuevas hoy' }}</div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="charts-grid">
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <div class="chart-icon">📈</div>
                <span>Historial de Humedad (7 días)</span>
            </div>
            <select class="filter-select" id="parcelaFilter">
                <option value="all">Todas las parcelas</option>
                @if(isset($parcelas) && count($parcelas) > 0)
                    @foreach($parcelas as $parcela)
                        <option value="{{ $parcela->id }}">{{ $parcela->nombre }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="chart-placeholder">
            <div class="chart-placeholder-content">
                <div class="chart-placeholder-icon">📊</div>
                <div class="chart-placeholder-title">Gráfico de Humedad</div>
                <div class="chart-placeholder-subtitle">Visualización de datos en tiempo real</div>
            </div>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <div class="chart-icon">🥧</div>
                <span>Distribución por Cultivo</span>
            </div>
        </div>
        <div class="chart-placeholder">
            <div class="chart-placeholder-content">
                <div class="chart-placeholder-icon">🌱</div>
                <div class="chart-placeholder-title">Cultivos Activos</div>
                <div class="chart-placeholder-subtitle">Análisis de distribución</div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="activity-card">
    <div class="activity-header">
        <div class="activity-title">
            <div class="activity-title-icon">⚡</div>
            <span>Actividad Reciente</span>
        </div>
        <a href="#" class="view-all-link">
            Ver todo
            <span>→</span>
        </a>
    </div>

    <div class="activity-list">
        @if(isset($actividadesRecientes) && count($actividadesRecientes) > 0)
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
                <div class="activity-icon" style="background: rgba(0, 123, 255, 0.1); color: #007bff;">ℹ️</div>
                <div class="activity-content">
                    <div class="activity-item-title">Sistema iniciado correctamente</div>
                    <div class="activity-time">Hace unos momentos</div>
                </div>
            </div>

            <div class="activity-item">
                <div class="activity-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">✓</div>
                <div class="activity-content">
                    <div class="activity-item-title">Todos los sensores funcionando correctamente</div>
                    <div class="activity-time">Hace 5 minutos</div>
                </div>
            </div>

            <div class="activity-item">
                <div class="activity-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">📊</div>
                <div class="activity-content">
                    <div class="activity-item-title">Datos sincronizados con éxito</div>
                    <div class="activity-time">Hace 15 minutos</div>
                </div>
            </div>

            <div class="activity-item">
                <div class="activity-icon" style="background: rgba(0, 123, 255, 0.1); color: #007bff;">🌱</div>
                <div class="activity-content">
                    <div class="activity-item-title">Bienvenido al sistema MAKIYAKU</div>
                    <div class="activity-time">Hoy</div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Filtro de parcelas para el gráfico
    document.getElementById('parcelaFilter')?.addEventListener('change', function(e) {
        console.log('Parcela seleccionada:', e.target.value);
        // Aquí puedes agregar la lógica AJAX para cargar datos reales
    });

    // Animación de números al cargar
    document.addEventListener('DOMContentLoaded', function() {
        const statValues = document.querySelectorAll('.stat-value');
        
        statValues.forEach(element => {
            const finalValue = parseInt(element.textContent) || 0;
            let currentValue = 0;
            const increment = finalValue / 30;
            const duration = 1000;
            const stepTime = duration / 30;

            const counter = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    element.textContent = element.textContent.includes('%') ? finalValue + '%' : finalValue;
                    clearInterval(counter);
                } else {
                    element.textContent = Math.floor(currentValue);
                }
            }, stepTime);
        });
    });

    // Simulación de actualización en tiempo real
    let updateInterval;
    
    function startRealTimeUpdates() {
        updateInterval = setInterval(function() {
            console.log('🔄 Actualizando datos en tiempo real...');
            // Aquí puedes hacer peticiones AJAX para actualizar los datos
        }, 30000); // Cada 30 segundos
    }

    // Iniciar actualizaciones
    startRealTimeUpdates();

    // Limpiar el intervalo cuando se sale de la página
    window.addEventListener('beforeunload', function() {
        if (updateInterval) {
            clearInterval(updateInterval);
        }
    });
</script>
@endpush