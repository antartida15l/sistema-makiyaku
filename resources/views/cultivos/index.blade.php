@extends('layouts.app')

@section('title', 'Catálogo de Cultivos')

@push('styles')
<style>
    .cultivos-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .cultivos-title {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .cultivos-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .cultivos-title-text h1 {
        font-size: 28px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 5px;
    }

    .cultivos-subtitle {
        font-size: 14px;
        color: var(--muted);
        font-weight: 500;
    }

    .btn-add-cultivo {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 26px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: var(--white);
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        font-size: 15px;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        transition: var(--transition);
    }

    .btn-add-cultivo:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }

    .filters-section {
        background: var(--white);
        padding: 20px;
        border-radius: 12px;
        box-shadow: var(--shadow-sm);
        margin-bottom: 25px;
    }

    .filters-label {
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 12px;
        display: block;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filters-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .filter-btn {
        padding: 10px 20px;
        border-radius: 20px;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: var(--transition);
        border: 2px solid;
    }

    .filter-btn.all {
        background: var(--light);
        color: var(--dark);
        border-color: #dee2e6;
    }

    .filter-btn.all:hover {
        background: #e9ecef;
        border-color: var(--primary);
        transform: translateY(-2px);
    }

    .filter-btn.hortalizas {
        background: #d4edda;
        color: #155724;
        border-color: #c3e6cb;
    }

    .filter-btn.hortalizas:hover {
        background: #c3e6cb;
        transform: translateY(-2px);
    }

    .filter-btn.cereales {
        background: #fff3cd;
        color: #856404;
        border-color: #ffeaa7;
    }

    .filter-btn.cereales:hover {
        background: #ffeaa7;
        transform: translateY(-2px);
    }

    .filter-btn.frutales {
        background: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
    }

    .filter-btn.frutales:hover {
        background: #f5c6cb;
        transform: translateY(-2px);
    }

    .filter-btn.legumbres {
        background: #e2d5f3;
        color: #5a2a82;
        border-color: #d4c2e8;
    }

    .filter-btn.legumbres:hover {
        background: #d4c2e8;
        transform: translateY(-2px);
    }

    .filter-btn.tuberculos {
        background: #ffe5d0;
        color: #cc5500;
        border-color: #ffd4b3;
    }

    .filter-btn.tuberculos:hover {
        background: #ffd4b3;
        transform: translateY(-2px);
    }

    .filter-btn.aromaticas {
        background: #d1f2eb;
        color: #0e6655;
        border-color: #a9dfbf;
    }

    .filter-btn.aromaticas:hover {
        background: #a9dfbf;
        transform: translateY(-2px);
    }

    .filter-btn.oleaginosas {
        background: #fadbd8;
        color: #922b21;
        border-color: #f5b7b1;
    }

    .filter-btn.oleaginosas:hover {
        background: #f5b7b1;
        transform: translateY(-2px);
    }

    .filter-btn.otros {
        background: #e8daef;
        color: #512e5f;
        border-color: #d7bde2;
    }

    .filter-btn.otros:hover {
        background: #d7bde2;
        transform: translateY(-2px);
    }

    .cultivos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }

    .cultivo-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 25px;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .cultivo-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    .cultivo-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary);
    }

    .cultivo-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 15px;
    }

    .cultivo-icon-circle {
        width: 55px;
        height: 55px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .cultivo-tipo-badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .tipo-hortalizas { background: #d4edda; color: #155724; }
    .tipo-cereales { background: #fff3cd; color: #856404; }
    .tipo-frutales { background: #f8d7da; color: #721c24; }
    .tipo-legumbres { background: #e2d5f3; color: #5a2a82; }
    .tipo-tubérculos { background: #ffe5d0; color: #cc5500; }
    .tipo-aromáticas { background: #d1f2eb; color: #0e6655; }
    .tipo-oleaginosas { background: #fadbd8; color: #922b21; }
    .tipo-otros { background: #e8daef; color: #512e5f; }

    .cultivo-name {
        font-size: 20px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 5px;
    }

    .cultivo-scientific {
        font-size: 13px;
        color: var(--muted);
        font-style: italic;
        margin-bottom: 15px;
    }

    .cultivo-region-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        background: #e3f2fd;
        color: #0d47a1;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .cultivo-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    .stat-item {
        background: var(--light);
        padding: 12px;
        border-radius: 10px;
        transition: var(--transition);
    }

    .stat-item:hover {
        background: #e9ecef;
        transform: scale(1.02);
    }

    .stat-label {
        font-size: 11px;
        color: var(--muted);
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 16px;
        font-weight: 800;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .cultivo-info-extra {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 15px;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 10px;
    }

    .info-extra-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--dark);
        font-weight: 600;
    }

    .cultivo-actions {
        display: flex;
        gap: 8px;
        padding-top: 15px;
        border-top: 1px solid #e9ecef;
    }

    .btn-action {
        flex: 1;
        padding: 10px;
        border-radius: 8px;
        border: none;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 13px;
        text-decoration: none;
    }

    .btn-view {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: var(--white);
    }

    .btn-view:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    }

    .btn-edit {
        background: linear-gradient(135deg, #ffc107, #ff9800);
        color: var(--white);
    }

    .btn-edit:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
    }

    .btn-delete {
        background: linear-gradient(135deg, var(--danger), #c82333);
        color: var(--white);
    }

    .btn-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-sm);
    }

    .empty-icon {
        font-size: 100px;
        margin-bottom: 25px;
        opacity: 0.5;
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }

    .empty-title {
        font-size: 28px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 10px;
    }

    .empty-text {
        color: var(--muted);
        font-size: 16px;
        margin-bottom: 30px;
    }

    /* PAGINACIÓN */
    .pagination {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
        padding: 30px 20px;
        background: var(--white);
        border-radius: 15px;
        box-shadow: var(--shadow-sm);
        margin-top: 30px;
    }

    .pagination > p {
        font-size: 14px;
        color: var(--muted);
        font-weight: 600;
        margin: 0;
        order: 1;
    }

    .pagination > nav {
        display: flex;
        align-items: center;
        gap: 10px;
        order: 2;
    }

    .pagination > nav > a,
    .pagination > nav > span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 20px;
        min-width: 120px;
        height: 45px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        transition: var(--transition);
        border: 2px solid #dee2e6;
        background: var(--white);
        color: var(--dark);
    }

    .pagination > nav > a:hover {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: var(--white);
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .pagination > nav > span {
        background: #f8f9fa;
        color: #adb5bd;
        cursor: not-allowed;
        opacity: 0.5;
    }

    .pagination > nav > div {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 10px;
    }

    .pagination > nav > div > a,
    .pagination > nav > div > span {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 45px;
        height: 45px;
        padding: 10px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 15px;
        text-decoration: none;
        transition: var(--transition);
        border: 2px solid #dee2e6;
        background: var(--white);
        color: var(--dark);
    }

    .pagination > nav > div > a:hover {
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .pagination > nav > div > span[aria-current="page"] {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: var(--white);
        border-color: var(--primary);
        font-weight: 800;
        box-shadow: 0 6px 15px rgba(40, 167, 69, 0.4);
        transform: scale(1.05);
    }

    .pagination > nav > div > span:not([aria-current]) {
        background: transparent;
        border: none;
        color: var(--muted);
        font-weight: 800;
        cursor: default;
    }

    .pagination svg {
        width: 18px !important;
        height: 18px !important;
        fill: currentColor;
        margin: 0 5px;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .cultivos-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .cultivos-grid {
            grid-template-columns: 1fr;
        }

        .filters-buttons {
            flex-direction: column;
        }

        .filter-btn {
            width: 100%;
            justify-content: center;
        }

        .pagination {
            padding: 20px 15px;
        }

        .pagination > nav {
            flex-direction: column;
            width: 100%;
            gap: 15px;
        }

        .pagination > nav > a,
        .pagination > nav > span {
            width: 100%;
            min-width: auto;
        }

        .pagination > nav > div {
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
        }

        .pagination > nav > div > a,
        .pagination > nav > div > span {
            min-width: 40px;
            height: 40px;
            font-size: 14px;
        }

        .cultivo-info-extra {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<!-- Header -->
<div class="cultivos-header">
    <div class="cultivos-title">
        <div class="cultivos-icon"><x-icono name="parcela" :size="20" /></div>
        <div class="cultivos-title-text">
            <h1>Catálogo de Cultivos</h1>
            <p class="cultivos-subtitle">Gestiona los tipos de cultivos para tus parcelas</p>
        </div>
    </div>
    <a href="{{ route('cultivos.create') }}" class="btn-add-cultivo">
        
        <span>Agregar Cultivo</span>
    </a>
</div>

<!-- Filtros -->
<div class="filters-section">
    <span class="filters-label">Filtrar por tipo:</span>
    <div class="filters-buttons">
        <a href="{{ route('cultivos.index') }}" class="filter-btn all">
            Todos
        </a>
        <a href="{{ route('cultivos.index', ['tipo' => 'Hortalizas']) }}" class="filter-btn hortalizas">
            Hortalizas
        </a>
        <a href="{{ route('cultivos.index', ['tipo' => 'Cereales']) }}" class="filter-btn cereales">
            Cereales
        </a>
        <a href="{{ route('cultivos.index', ['tipo' => 'Frutales']) }}" class="filter-btn frutales">
            Frutales
        </a>
        <a href="{{ route('cultivos.index', ['tipo' => 'Legumbres']) }}" class="filter-btn legumbres">
            Legumbres
        </a>
        <a href="{{ route('cultivos.index', ['tipo' => 'Tubérculos']) }}" class="filter-btn tuberculos">
            Tubérculos
        </a>
        <a href="{{ route('cultivos.index', ['tipo' => 'Aromáticas']) }}" class="filter-btn aromaticas">
            Aromáticas
        </a>
        <a href="{{ route('cultivos.index', ['tipo' => 'Oleaginosas']) }}" class="filter-btn oleaginosas">
            Oleaginosas
        </a>
        <a href="{{ route('cultivos.index', ['tipo' => 'Otros']) }}" class="filter-btn otros">
            Otros
        </a>
    </div>
</div>

@if($cultivos->isEmpty())
    <!-- Estado Vacío -->
    <div class="empty-state">
        <div class="empty-icon"><x-icono name="parcela" :size="20" /></div>
        <div class="empty-title">No hay cultivos registrados</div>
        <div class="empty-text">
            Comienza agregando tu primer cultivo al catálogo
        </div>
        <a href="{{ route('cultivos.create') }}" class="btn-add-cultivo">
            
            <span>Agregar Primer Cultivo</span>
        </a>
    </div>
@else
    <!-- Grid de Cultivos -->
    <div class="cultivos-grid">
        @foreach($cultivos as $cultivo)
            <div class="cultivo-card">
                <div class="cultivo-header">
                    <div class="cultivo-icon-circle"><x-icono name="parcela" :size="20" /></div>
                    <span class="cultivo-tipo-badge tipo-{{ strtolower($cultivo->tipo) }}">
                        {{ $cultivo->tipo }}
                    </span>
                </div>

                <div class="cultivo-name">{{ $cultivo->nombreCultivo }}</div>
                <div class="cultivo-scientific">{{ $cultivo->nombreCientifico ?? 'Sin nombre científico' }}</div>

                @if($cultivo->region)
                    <div class="cultivo-region-badge">
                        <span><x-icono name="parcela" :size="20" /></span>
                        <span>{{ $cultivo->region }}</span>
                    </div>
                @endif

                <div class="cultivo-stats">
                    <div class="stat-item">
                        <div class="stat-label">Riego</div>
                        <div class="stat-value">
                            <span><x-icono name="agua" :size="20" /></span>
                            <span>{{ $cultivo->frecuenciaRiegoMin }}-{{ $cultivo->frecuenciaRiegoMax }}d</span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-label">Consumo L/m²/día</div>
                        <div class="stat-value">
                            
                            <span>{{ number_format($cultivo->litrosPorM2Dia, 2) }}</span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-label">Humedad Óptima</div>
                        <div class="stat-value">
                            <span><x-icono name="ciclo" :size="20" /></span>
                            <span>{{ $cultivo->humedadOptimaMin }}-{{ $cultivo->humedadOptimaMax }}%</span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-label">Ciclo</div>
                        <div class="stat-value">
                            <span><x-icono name="ciclo" :size="20" /></span>
                            <span>{{ $cultivo->cicloProductivo ?? 'N/A' }} días</span>
                        </div>
                    </div>
                </div>

                <div class="cultivo-info-extra">
                    <div class="info-extra-item">
                        <span><x-icono name="calor" :size="20" /></span>
                        <span>{{ $cultivo->tempCriticaMinima }}°C - {{ $cultivo->tempCriticaMaxima }}°C</span>
                    </div>
                    <div class="info-extra-item">
                        <span><x-icono name="alerta" :size="20" /></span>
                        <span>{{ $cultivo->diasCriticosSinAgua }}d sin agua</span>
                    </div>
                    <div class="info-extra-item" style="grid-column: span 2;">
                        
                        <span style="font-size: 11px;">{{ Str::limit($cultivo->etapaMayorConsumo, 35) }}</span>
                    </div>
                </div>

                <div class="cultivo-actions">
                    <a href="{{ route('cultivos.show', $cultivo) }}" class="btn-action btn-view">
                        
                        <span>Ver</span>
                    </a>
                    <a href="{{ route('cultivos.edit', $cultivo) }}" class="btn-action btn-edit">
                        
                        <span>Editar</span>
                    </a>
                    <form action="{{ route('cultivos.destroy', $cultivo) }}" method="POST" style="flex: 1;"
                          onsubmit="return confirm('¿Estás seguro de eliminar este cultivo?\n\nEsta acción no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-action btn-delete" style="width: 100%;">
                            
                            <span>Eliminar</span>
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Paginación -->
    <div class="pagination">
        {{ $cultivos->appends(request()->query())->links() }}
    </div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animación de entrada para las tarjetas
        const cards = document.querySelectorAll('.cultivo-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 80);
        });
    });
</script>
@endpush