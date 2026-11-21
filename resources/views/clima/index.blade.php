@extends('layouts.app')

@section('title', 'Clima - ' . ($datos['name'] ?? 'Consulta'))

@push('styles')
<style>
    :root {
        --color-primary: #28a745;
        --color-secondary: #20c997;
        --color-info: #17a2b8;
        --color-warning: #ffc107;
        --color-danger: #dc3545;
        --color-success: #28a745;
    }

    .clima-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Header */
    .clima-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .page-title {
        font-size: 32px;
        font-weight: 800;
        color: #212529;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-refresh {
        padding: 12px 24px;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .btn-refresh:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }

    /* Búsqueda */
    .search-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 25px;
    }

    .search-wrapper {
        position: relative;
    }

    .search-input {
        width: 100%;
        padding: 14px 20px 14px 50px;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .search-input:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
    }

    .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 20px;
        color: #6c757d;
    }

    /* Autocompletado */
    .autocomplete-results {
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        max-height: 400px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    }

    .autocomplete-results.active { display: block; }

    .autocomplete-item {
        padding: 15px 20px;
        cursor: pointer;
        transition: all 0.2s;
        border-bottom: 1px solid #f8f9fa;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .autocomplete-item:hover {
        background: linear-gradient(90deg, #e3f2fd, #f8f9fa);
        padding-left: 25px;
    }

    /* Alertas */
    .alertas-section {
        display: grid;
        gap: 15px;
        margin-bottom: 25px;
    }

    .alerta-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-left: 5px solid;
    }

    .alerta-card.info { border-left-color: var(--color-info); background: linear-gradient(90deg, #e3f2fd, white); }
    .alerta-card.warning { border-left-color: var(--color-warning); background: linear-gradient(90deg, #fff3cd, white); }
    .alerta-card.success { border-left-color: var(--color-success); background: linear-gradient(90deg, #d4edda, white); }
    .alerta-card.danger { border-left-color: var(--color-danger); background: linear-gradient(90deg, #f8d7da, white); }

    .alerta-icon {
        font-size: 32px;
        flex-shrink: 0;
    }

    .alerta-content h4 {
        font-size: 16px;
        font-weight: 800;
        margin-bottom: 5px;
        color: #212529;
    }

    .alerta-content p {
        font-size: 14px;
        color: #6c757d;
        margin: 0;
    }

    /* Clima actual */
    .clima-actual {
        background: white;
        border-radius: 20px;
        padding: 35px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        border-top: 5px solid var(--color-primary);
    }

    .clima-main-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .clima-main-header h2 {
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 5px;
    }

    .clima-main-header p {
        font-size: 18px;
        color: #6c757d;
        font-weight: 600;
    }

    .weather-icon-large {
        width: 120px;
        height: 120px;
        filter: drop-shadow(0 5px 15px rgba(0,0,0,0.2));
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .stat-card {
        padding: 25px;
        border-radius: 16px;
        color: white;
        transition: all 0.3s;
        background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .stat-card.humedad { background: linear-gradient(135deg, #95e1d3, #38ada9); }
    .stat-card.viento { background: linear-gradient(135deg, #a8b8c3, #6c757d); }
    .stat-card.lluvia { background: linear-gradient(135deg, #4ecdc4, #44a3b3); }

    .stat-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        opacity: 0.95;
        margin-bottom: 10px;
    }

    .stat-value {
        font-size: 42px;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .stat-extra {
        font-size: 14px;
        opacity: 0.9;
    }

    /* Pronóstico horario */
    .forecast-section {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        margin-bottom: 25px;
    }

    .forecast-title {
        font-size: 20px;
        font-weight: 800;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #212529;
    }

    .hourly-forecast {
        display: flex;
        gap: 15px;
        overflow-x: auto;
        padding-bottom: 10px;
    }

    .hourly-item {
        min-width: 100px;
        text-align: center;
        padding: 20px 15px;
        background: linear-gradient(135deg, #f8f9fa, white);
        border-radius: 12px;
        border: 2px solid transparent;
        transition: all 0.3s;
    }

    .hourly-item:hover {
        transform: translateY(-5px);
        border-color: var(--color-primary);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    .hourly-time {
        font-size: 13px;
        font-weight: 700;
        color: #6c757d;
        margin-bottom: 10px;
    }

    .hourly-icon {
        width: 50px;
        height: 50px;
        margin: 10px auto;
    }

    .hourly-temp {
        font-size: 24px;
        font-weight: 800;
        color: #212529;
        margin: 10px 0;
    }

    .hourly-pop {
        font-size: 12px;
        color: var(--color-info);
        font-weight: 700;
    }

    /* Pronóstico 5 días */
    .daily-forecast {
        display: grid;
        gap: 15px;
    }

    .daily-item {
        display: grid;
        grid-template-columns: 80px 60px 1fr 100px 80px;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: linear-gradient(90deg, #f8f9fa, white);
        border-radius: 12px;
        border: 2px solid transparent;
        transition: all 0.3s;
    }

    .daily-item:hover {
        border-color: var(--color-primary);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transform: translateX(5px);
    }

    .daily-date {
        font-weight: 800;
        font-size: 15px;
        color: #212529;
    }

    .daily-date small {
        display: block;
        font-size: 12px;
        color: #6c757d;
        font-weight: 600;
    }

    .daily-icon {
        width: 50px;
        height: 50px;
    }

    .daily-desc {
        font-size: 14px;
        font-weight: 600;
        color: #6c757d;
    }

    .daily-temps {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
    }

    .temp-max {
        font-size: 20px;
        color: #dc3545;
    }

    .temp-min {
        font-size: 18px;
        color: #17a2b8;
    }

    .daily-pop {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 14px;
        font-weight: 700;
        color: var(--color-info);
    }

    /* Gráficos */
    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 25px;
        margin-bottom: 25px;
    }

    .chart-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }

    .chart-title {
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chart-container {
        position: relative;
        height: 300px;
    }

    /* Ciudades rápidas */
    .quick-cities {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }

    .quick-cities h3 {
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 15px;
    }

    .cities-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .city-btn {
        padding: 10px 20px;
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        color: #1565c0;
        border: 2px solid transparent;
        border-radius: 25px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s;
    }

    .city-btn:hover {
        background: linear-gradient(135deg, #bbdefb, #90caf9);
        border-color: #1565c0;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(21, 101, 192, 0.3);
    }

    /* Loading */
    .loading-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .loading-overlay.active { display: flex; }

    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid var(--color-primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .charts-grid, .stats-grid { grid-template-columns: 1fr; }
        .daily-item { grid-template-columns: 1fr; text-align: center; }
        .hourly-forecast { justify-content: flex-start; }
    }
</style>
@endpush

@section('content')
<div class="clima-container">
    <!-- Header -->
    <div class="clima-header">
        <h1 class="page-title">
            <span>🌤️</span>
            <span>Clima en Tiempo Real</span>
        </h1>
        
        @if(isset($datos))
        <button id="btnRefresh" class="btn-refresh">
            <span>🔄</span>
            <span>Actualizar</span>
        </button>
        @endif
    </div>

    <!-- Búsqueda -->
    <div class="search-card">
        <div class="search-wrapper">
            <span class="search-icon">🔍</span>
            <input 
                type="text" 
                id="searchInput"
                class="search-input"
                placeholder="Busca una ciudad (ej: Puno, Lima, Cusco...)"
                value="{{ $ciudad ?? '' }}"
                autocomplete="off"
            >
            <div id="autocompleteResults" class="autocomplete-results"></div>
        </div>
    </div>

    @if(isset($error))
        <div class="alert alert-danger">
            <strong>⚠️ Error:</strong> {{ $error }}
        </div>
    @else
        <!-- Alertas inteligentes -->
        @if(!empty($alertas))
        <div class="alertas-section">
            @foreach($alertas as $alerta)
            <div class="alerta-card {{ $alerta['clase'] }}">
                <span class="alerta-icon">{{ $alerta['icono'] }}</span>
                <div class="alerta-content">
                    <h4>{{ $alerta['titulo'] }}</h4>
                    <p>{{ $alerta['mensaje'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Clima actual -->
        <div class="clima-actual">
            <div class="clima-main-header">
                <div>
                    <h2>📍 {{ $datos['name'] }}, {{ $datos['country'] }}</h2>
                    <p>{{ $datos['weather_description'] }}</p>
                </div>
                <img src="{{ $datos['icon_url'] }}" alt="Clima" class="weather-icon-large">
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">🌡️ TEMPERATURA</div>
                    <div class="stat-value">{{ $datos['temp'] }}°C</div>
                    <div class="stat-extra">Sensación: {{ $datos['feels_like'] }}°C</div>
                </div>

                <div class="stat-card humedad">
                    <div class="stat-label">💧 HUMEDAD</div>
                    <div class="stat-value">{{ $datos['humidity'] }}%</div>
                    <div class="stat-extra">Presión: {{ $datos['pressure'] }} hPa</div>
                </div>

                <div class="stat-card viento">
                    <div class="stat-label">🌬️ VIENTO</div>
                    <div class="stat-value">{{ $datos['wind_speed'] }} m/s</div>
                    <div class="stat-extra">Dirección: {{ $datos['wind_direction'] }}</div>
                </div>

                <div class="stat-card lluvia">
                    <div class="stat-label">🌧️ PRECIPITACIÓN</div>
                    <div class="stat-value">{{ $datos['rain_3h'] }} mm</div>
                    <div class="stat-extra">Últimas 3 horas</div>
                </div>
            </div>
        </div>

        <!-- Pronóstico horario (24h) -->
        @if(!empty($pronostico_horario))
        <div class="forecast-section">
            <h3 class="forecast-title">
                <span>⏰</span>
                <span>Pronóstico por hora (próximas 24h)</span>
            </h3>
            <div class="hourly-forecast">
                @foreach($pronostico_horario as $hora)
                <div class="hourly-item">
                    <div class="hourly-time">{{ $hora['hora_corta'] }}</div>
                    <img src="{{ $hora['icon_url'] }}" alt="Clima" class="hourly-icon">
                    <div class="hourly-temp">{{ $hora['temp'] }}°</div>
                    @if($hora['pop'] > 0)
                    <div class="hourly-pop">💧 {{ $hora['pop'] }}%</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Pronóstico 5 días -->
        @if(!empty($pronostico_diario))
        <div class="forecast-section">
            <h3 class="forecast-title">
                <span>📅</span>
                <span>Pronóstico 5 días</span>
            </h3>
            <div class="daily-forecast">
                @foreach($pronostico_diario as $dia)
                <div class="daily-item">
                    <div class="daily-date">
                        {{ $dia['dia_semana'] }}
                        <small>{{ $dia['dia_mes'] }}</small>
                    </div>
                    <img src="{{ $dia['icon_url'] }}" alt="Clima" class="daily-icon">
                    <div class="daily-desc">{{ $dia['descripcion'] }}</div>
                    <div class="daily-temps">
                        <span class="temp-max">{{ $dia['temp_max'] }}°</span>
                        <span class="temp-min">{{ $dia['temp_min'] }}°</span>
                    </div>
                    <div class="daily-pop">
                        <span>💧</span>
                        <span>{{ $dia['pop'] }}%</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Gráficos -->
        @if(!empty($graficos))
        <div class="charts-grid">
            <div class="chart-card">
                <h3 class="chart-title">
                    <span>🌡️</span>
                    <span>Temperatura (48h)</span>
                </h3>
                <div class="chart-container">
                    <canvas id="chartTemperatura"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">
                    <span>🌧️</span>
                    <span>Probabilidad de Lluvia</span>
                </h3>
                <div class="chart-container">
                    <canvas id="chartLluvia"></canvas>
                </div>
            </div>
        </div>
        @endif
    @endif

    <!-- Ciudades principales -->
    <div class="quick-cities">
        <h3>🏙️ Ciudades Principales de Perú</h3>
        <div class="cities-grid">
            @foreach($ciudades_principales as $city)
                <a href="{{ route('clima.index', ['lat' => $city['lat'], 'lon' => $city['lon']]) }}" 
                   class="city-btn">
                    {{ $city['name'] }}
                </a>
            @endforeach
        </div>
    </div>
</div>

<!-- Loading -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="spinner"></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const graficosData = @json($graficos ?? null);

// Autocompletado
let searchTimeout;
const searchInput = document.getElementById('searchInput');
const autocompleteResults = document.getElementById('autocompleteResults');

searchInput?.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    if (query.length < 2) {
        autocompleteResults.classList.remove('active');
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch(`{{ route('clima.buscar') }}?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (!data.length) {
                    autocompleteResults.classList.remove('active');
                    return;
                }
                
                autocompleteResults.innerHTML = data.map(city => `
                    <div class="autocomplete-item" data-lat="${city.lat}" data-lon="${city.lon}">
                        <span>📍</span>
                        <div>
                            <div style="font-weight:700;color:#212529">${city.display_name}</div>
                        </div>
                    </div>
                `).join('');
                
                autocompleteResults.classList.add('active');
                
                document.querySelectorAll('.autocomplete-item').forEach(item => {
                    item.addEventListener('click', function() {
                        window.location.href = `{{ route('clima.index') }}?lat=${this.dataset.lat}&lon=${this.dataset.lon}`;
                    });
                });
            });
    }, 300);
});

document.addEventListener('click', e => {
    if (!searchInput?.contains(e.target) && !autocompleteResults?.contains(e.target)) {
        autocompleteResults?.classList.remove('active');
    }
});

// Refrescar
document.getElementById('btnRefresh')?.addEventListener('click', function() {
    this.disabled = true;
    document.getElementById('loadingOverlay').classList.add('active');
    location.reload();
});

// Gráficos
if (graficosData) {
    const commonOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true, position: 'top' } },
        scales: { y: { beginAtZero: true } }
    };

    // Temperatura
    if (document.getElementById('chartTemperatura')) {
        new Chart(document.getElementById('chartTemperatura'), {
            type: 'line',
            data: {
                labels: graficosData.temperatura.labels,
                datasets: [{
                    label: 'Temperatura (°C)',
                    data: graficosData.temperatura.data,
                    borderColor: '#ff6b6b',
                    backgroundColor: 'rgba(255,107,107,0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: commonOpts
        });
    }

    // Lluvia
    if (document.getElementById('chartLluvia')) {
        new Chart(document.getElementById('chartLluvia'), {
            type: 'bar',
            data: {
                labels: graficosData.lluvia.labels,
                datasets: [{
                    label: 'Probabilidad (%)',
                    data: graficosData.lluvia.probabilidad,
                    backgroundColor: 'rgba(78,205,196,0.6)',
                    borderColor: '#4ecdc4',
                    borderWidth: 2
                }]
            },
            options: commonOpts
        });
    }
}
</script>
@endpush