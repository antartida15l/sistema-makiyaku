@extends('layouts.app')

@section('title', "Recomendaciones - {$parcela->nombre}")

@push('styles')
<style>
    :root {
        --color-critica: #dc3545;
        --color-alta: #fd7e14;
        --color-media: #ffc107;
        --color-baja: #17a2b8;
        --color-info: #6c757d;
    }

    /* HEADER */
    .recomendaciones-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 16px;
        margin-bottom: 30px;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
    }

    .header-info h1 {
        font-size: 28px;
        font-weight: 800;
        margin: 0 0 10px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .header-subtitle {
        opacity: 0.9;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-regenerar {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.4);
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
        cursor: pointer;
        backdrop-filter: blur(10px);
    }

    .btn-regenerar:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.6);
        transform: translateY(-2px);
    }

    /* ESTADÍSTICAS */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: transform 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }

    .stat-icon.critica { background: linear-gradient(135deg, #ffebee, #ffcdd2); }
    .stat-icon.alta { background: linear-gradient(135deg, #fff3e0, #ffe0b2); }
    .stat-icon.total { background: linear-gradient(135deg, #e3f2fd, #bbdefb); }

    .stat-content {
        flex: 1;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 800;
        color: #212529;
        line-height: 1;
    }

    .stat-label {
        font-size: 13px;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        margin-top: 4px;
    }

    /* RECOMENDACIONES */
    .recomendaciones-container {
        display: grid;
        gap: 20px;
    }

    .recomendacion-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        border-left: 5px solid;
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
    }

    .recomendacion-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 150px;
        height: 150px;
        opacity: 0.05;
        font-size: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .recomendacion-card.critica {
        border-left-color: var(--color-critica);
        background: linear-gradient(to right, #fff, #fff5f5);
    }

    .recomendacion-card.alta {
        border-left-color: var(--color-alta);
        background: linear-gradient(to right, #fff, #fffbf5);
    }

    .recomendacion-card.media {
        border-left-color: var(--color-media);
        background: linear-gradient(to right, #fff, #fffef5);
    }

    .recomendacion-card.baja {
        border-left-color: var(--color-baja);
        background: linear-gradient(to right, #fff, #f5fcff);
    }

    .recomendacion-card.info {
        border-left-color: var(--color-info);
        background: linear-gradient(to right, #fff, #f8f9fa);
    }

    .recomendacion-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }

    .recomendacion-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
        gap: 15px;
    }

    .recomendacion-icon-wrapper {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .recomendacion-icon {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        flex-shrink: 0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .recomendacion-icon.critica { background: linear-gradient(135deg, #ff6b6b, #ee5a6f); }
    .recomendacion-icon.alta { background: linear-gradient(135deg, #ffa726, #fb8c00); }
    .recomendacion-icon.media { background: linear-gradient(135deg, #ffca28, #ffc107); }
    .recomendacion-icon.baja { background: linear-gradient(135deg, #26c6da, #00acc1); }
    .recomendacion-icon.info { background: linear-gradient(135deg, #9e9e9e, #757575); }

    .recomendacion-title-group {
        flex: 1;
    }

    .recomendacion-title {
        font-size: 20px;
        font-weight: 800;
        color: #212529;
        margin: 0 0 8px 0;
        line-height: 1.3;
    }

    .recomendacion-meta {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-prioridad {
        color: white;
    }

    .badge-prioridad.critica { background: var(--color-critica); }
    .badge-prioridad.alta { background: var(--color-alta); }
    .badge-prioridad.media { background: var(--color-media); color: #000; }
    .badge-prioridad.baja { background: var(--color-baja); }
    .badge-prioridad.info { background: var(--color-info); }

    .badge-categoria {
        background: #e9ecef;
        color: #495057;
    }

    .recomendacion-descripcion {
        color: #495057;
        line-height: 1.6;
        margin-bottom: 20px;
        font-size: 15px;
    }

    .recomendacion-datos {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 15px;
    }

    .datos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
    }

    .dato-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .dato-label {
        font-size: 11px;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
    }

    .dato-value {
        font-size: 15px;
        font-weight: 700;
        color: #212529;
    }

    .recomendacion-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-success:hover {
        background: #218838;
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    /* TABS */
    .tabs-container {
        margin-bottom: 25px;
    }

    .tabs {
        display: flex;
        gap: 10px;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 10px;
        flex-wrap: wrap;
    }

    .tab {
        padding: 10px 20px;
        border-radius: 8px 8px 0 0;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        background: transparent;
        color: #6c757d;
        border: none;
        font-size: 14px;
    }

    .tab.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }

    .tab:hover:not(.active) {
        background: #f8f9fa;
    }

    /* EMPTY STATE */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .empty-icon {
        font-size: 80px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-title {
        font-size: 24px;
        font-weight: 800;
        color: #212529;
        margin-bottom: 10px;
    }

    .empty-text {
        color: #6c757d;
        margin-bottom: 30px;
    }

    /* HISTÓRICO */
    .historico-card {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 10px;
        border-left: 3px solid #28a745;
    }

    .historico-card.descartada {
        border-left-color: #6c757d;
        opacity: 0.7;
    }

    .historico-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .historico-titulo {
        font-weight: 700;
        color: #212529;
        font-size: 14px;
    }

    .historico-fecha {
        font-size: 12px;
        color: #6c757d;
    }

    .historico-notas {
        font-size: 13px;
        color: #495057;
        font-style: italic;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            align-items: stretch;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .recomendacion-header {
            flex-direction: column;
        }

        .tabs {
            flex-direction: column;
        }
    }
</style>
@endpush

@section('content')
{{-- HEADER --}}
<div class="recomendaciones-header">
    <div class="header-content">
        <div class="header-info">
            <h1>
                <span>💡</span>
                <span>Recomendaciones para {{ $parcela->nombre }}</span>
            </h1>
            <div class="header-subtitle">
                <span>🌱</span>
                <span>{{ $parcela->cultivo->nombreCultivo ?? 'Sin cultivo' }}</span>
                <span>•</span>
                <span>📍 {{ $parcela->ubicacion_completa }}</span>
            </div>
        </div>
        <form action="{{ route('recomendaciones.regenerar', $parcela) }}" method="POST">
            @csrf
            <button type="submit" class="btn-regenerar">
                <span>🔄</span>
                <span>Actualizar Recomendaciones</span>
            </button>
        </form>
    </div>
</div>

{{-- ESTADÍSTICAS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon total">📊</div>
        <div class="stat-content">
            <div class="stat-value">{{ $estadisticas['total'] }}</div>
            <div class="stat-label">Total</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon critica">🚨</div>
        <div class="stat-content">
            <div class="stat-value">{{ $estadisticas['criticas'] }}</div>
            <div class="stat-label">Críticas</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon alta">⚠️</div>
        <div class="stat-content">
            <div class="stat-value">{{ $estadisticas['altas'] }}</div>
            <div class="stat-label">Prioridad Alta</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #d4edda, #c3e6cb);">
            ✅
        </div>
        <div class="stat-content">
            <div class="stat-value">{{ $historico->where('aplicada', true)->count() }}</div>
            <div class="stat-label">Aplicadas</div>
        </div>
    </div>
</div>

{{-- ALERTAS --}}
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

{{-- TABS --}}
<div class="tabs-container">
    <div class="tabs">
        <button class="tab active" data-tab="activas">
            📋 Activas ({{ $recomendaciones->count() }})
        </button>
        <button class="tab" data-tab="historico">
            📜 Histórico ({{ $historico->count() }})
        </button>
    </div>
</div>

{{-- CONTENIDO TABS --}}
<div class="tab-content" data-content="activas">
    @if($recomendaciones->count() > 0)
        <div class="recomendaciones-container">
            @foreach($recomendaciones as $rec)
                <div class="recomendacion-card {{ $rec->prioridad }}">
                    <div class="recomendacion-header">
                        <div class="recomendacion-icon-wrapper">
                            <div class="recomendacion-icon {{ $rec->prioridad }}">
                                {{ $rec->icono }}
                            </div>
                            <div class="recomendacion-title-group">
                                <h3 class="recomendacion-title">{{ $rec->titulo }}</h3>
                                <div class="recomendacion-meta">
                                    <span class="badge badge-prioridad {{ $rec->prioridad }}">
                                        {{ $rec->etiqueta_prioridad }}
                                    </span>
                                    <span class="badge badge-categoria">
                                        {{ $rec->etiqueta_categoria }}
                                    </span>
                                    <span class="badge badge-categoria">
                                        📊 {{ $rec->puntaje_relevancia }}/100
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="recomendacion-descripcion">
                        {{ $rec->descripcion }}
                    </p>

                    @if($rec->datos_analisis && count($rec->datos_analisis) > 0)
                        <div class="recomendacion-datos">
                            <div class="datos-grid">
                                @foreach($rec->datos_analisis as $key => $value)
                                    <div class="dato-item">
                                        <div class="dato-label">{{ Str::headline($key) }}</div>
                                        <div class="dato-value">
                                            @if(is_numeric($value))
                                                {{ number_format($value, 2) }}
                                            @elseif(is_bool($value))
                                                {{ $value ? 'Sí' : 'No' }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="recomendacion-actions">
                        @if($rec->accion_url)
                            <a href="{{ $rec->accion_url }}" class="btn btn-primary">
                                <span>🎯</span>
                                <span>{{ $rec->accion_texto ?? 'Ver Más' }}</span>
                            </a>
                        @endif

                        <form action="{{ route('recomendaciones.aplicar', $rec) }}" 
                              method="POST" 
                              style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <span>✅</span>
                                <span>Marcar como Aplicada</span>
                            </button>
                        </form>

                        <form action="{{ route('recomendaciones.descartar', $rec) }}" 
                              method="POST" 
                              style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-secondary">
                                <span>❌</span>
                                <span>Descartar</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">🎉</div>
            <div class="empty-title">¡Todo en orden!</div>
            <div class="empty-text">
                No hay recomendaciones activas en este momento. Tu parcela está siendo manejada correctamente.
            </div>
            <form action="{{ route('recomendaciones.regenerar', $parcela) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <span>🔄</span>
                    <span>Generar Nuevas Recomendaciones</span>
                </button>
            </form>
        </div>
    @endif
</div>

<div class="tab-content" data-content="historico" style="display: none;">
    @if($historico->count() > 0)
        @foreach($historico as $rec)
            <div class="historico-card {{ $rec->descartada ? 'descartada' : '' }}">
                <div class="historico-header">
                    <div class="historico-titulo">
                        {{ $rec->icono }} {{ $rec->titulo }}
                    </div>
                    <div class="historico-fecha">
                        {{ $rec->aplicada ? '✅ Aplicada' : '❌ Descartada' }} 
                        {{ $rec->aplicada ? $rec->aplicada_en->diffForHumans() : $rec->descartada_en->diffForHumans() }}
                    </div>
                </div>
                @if($rec->notas_usuario)
                    <div class="historico-notas">
                        💭 "{{ $rec->notas_usuario }}"
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <div class="empty-title">Sin histórico aún</div>
            <div class="empty-text">
                Las recomendaciones aplicadas o descartadas aparecerán aquí.
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // TABS
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Actualizar tabs activos
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Mostrar contenido correspondiente
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            document.querySelector(`[data-content="${targetTab}"]`).style.display = 'block';
        });
    });
</script>
@endpush
@endsection