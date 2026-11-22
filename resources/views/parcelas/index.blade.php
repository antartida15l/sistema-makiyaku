@extends('layouts.app')

@section('title', 'Mis Parcelas')

@push('styles')
<style>
    /* ========== ESTILOS BASE DEL CATÁLOGO ========== */
    .alert-banner {
        background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        border-left: 5px solid #17a2b8;
        padding: 20px 25px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(23, 162, 184, 0.2);
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .alert-banner.success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border-left-color: #28a745;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
    }

    .alert-banner.danger {
        background: linear-gradient(135deg, #f8d7da, #f5c2c7);
        border-left-color: #dc3545;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
    }

    .alert-icon {
        font-size: 32px;
        flex-shrink: 0;
    }

    .alert-content {
        flex: 1;
    }

    .alert-title {
        font-weight: 800;
        font-size: 16px;
        color: var(--dark);
    }

    .cultivos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 25px;
        margin-top: 25px;
    }

    .cultivo-card {
        background: var(--white);
        border-radius: 16px;
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
        margin-bottom: 20px;
    }

    .cultivo-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .cultivo-badges {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: flex-end;
    }

    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .badge-tipo {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        color: #0d47a1;
    }

    .cultivo-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 5px;
    }

    .cultivo-cientifico {
        color: var(--muted);
        font-size: 13px;
        font-style: italic;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .cultivo-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 15px;
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
        font-size: 15px;
        font-weight: 800;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .cultivo-actions {
        display: flex;
        gap: 10px;
        padding-top: 15px;
        border-top: 1px solid #e9ecef;
    }

    .btn {
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

    .btn-primary {
        background: var(--primary);
        color: var(--white);
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .btn-secondary {
        background: var(--light);
        color: var(--dark);
    }

    .btn-secondary:hover {
        background: #e9ecef;
    }

    .btn-danger {
        background: var(--danger);
        color: var(--white);
    }

    .btn-danger:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-sm);
    }

    .empty-icon {
        font-size: 80px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-title {
        font-size: 24px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 10px;
    }

    .empty-text {
        color: var(--muted);
        margin-bottom: 30px;
    }

    .btn-add {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 15px 30px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: var(--white);
        border-radius: 12px;
        text-decoration: none;
        font-weight: 800;
        font-size: 16px;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        transition: var(--transition);
    }

    .btn-add:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }

    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .header-title {
        font-size: 28px;
        font-weight: 800;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .header-stats {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: var(--white);
        padding: 20px;
        border-radius: 12px;
        box-shadow: var(--shadow-sm);
        flex: 1;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: var(--transition);
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .stat-card-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .stat-card-content {
        flex: 1;
    }

    .stat-card-label {
        font-size: 13px;
        color: var(--muted);
        font-weight: 600;
        text-transform: uppercase;
    }

    .stat-card-value {
        font-size: 24px;
        font-weight: 800;
        color: var(--dark);
    }

    /* ========== ESTILOS ESPECÍFICOS DE CLIMA ========== */
    .clima-widget {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        border-radius: 12px;
        padding: 15px;
        margin-top: 15px;
        border-left: 4px solid #2196f3;
    }

    .clima-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .clima-temp {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .temp-principal {
        font-size: 28px;
        font-weight: 800;
        color: #0d47a1;
    }

    .clima-icon {
        width: 50px;
        height: 50px;
    }

    .clima-descripcion {
        font-size: 13px;
        color: #1565c0;
        font-weight: 600;
        text-transform: capitalize;
    }

    .clima-detalles {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-top: 10px;
    }

    .clima-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #1565c0;
        font-weight: 600;
    }

    .clima-alertas {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    .alerta-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .alerta-helada {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        color: #0d47a1;
    }

    .alerta-calor {
        background: linear-gradient(135deg, #ffebee, #ffcdd2);
        color: #b71c1c;
    }

    .alerta-lluvia {
        background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
        color: #1b5e20;
    }

    .alerta-riego {
        background: linear-gradient(135deg, #fff3e0, #ffe0b2);
        color: #e65100;
    }

    .clima-sin-datos {
        background: #f5f5f5;
        padding: 15px;
        border-radius: 12px;
        text-align: center;
        color: #757575;
        font-size: 13px;
        margin-top: 15px;
    }

    .btn-actualizar-clima {
        background: linear-gradient(135deg, #2196f3, #1976d2);
        color: white;
        padding: 8px 15px;
        border-radius: 8px;
        border: none;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: var(--transition);
    }

    .btn-actualizar-clima:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
    }

    .clima-timestamp {
        font-size: 11px;
        color: #757575;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .stat-card.clima {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    }

    .stat-card.clima .stat-card-icon {
        background: linear-gradient(135deg, #2196f3, #1976d2);
    }

    /* ========== RESPONSIVE ========== */
    @media (max-width: 768px) {
        .cultivos-grid {
            grid-template-columns: 1fr;
        }

        .header-actions {
            flex-direction: column;
            gap: 15px;
            align-items: stretch;
        }

        .header-stats {
            flex-direction: column;
        }

        .alert-banner {
            flex-direction: column;
            text-align: center;
        }

        .clima-detalles {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="header-actions">
    <div class="header-title">
        <span><x-icono name="parcela" :size="20" /></span>
        <span>Mis Parcelas</span>
    </div>
    <a href="{{ route('parcelas.create') }}" class="btn-add">
        
        <span>Nueva Parcela</span>
    </a>
</div>

{{-- ESTADÍSTICAS GENERALES --}}
<div class="header-stats">
    <div class="stat-card">
        <div class="stat-card-icon"><x-icono name="ciclo" :size="20" /></div>
        <div class="stat-card-content">
            <div class="stat-card-label">Total Parcelas</div>
            <div class="stat-card-value">{{ $parcelas->total() }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon"><x-icono name="alerta" :size="20" /></div>
        <div class="stat-card-content">
            <div class="stat-card-label">Riego Crítico</div>
            <div class="stat-card-value">{{ $alertas['criticas'] }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon"><x-icono name="agua" :size="20" /></div>
        <div class="stat-card-content">
            <div class="stat-card-label">Riego Hoy</div>
            <div class="stat-card-value">{{ $alertas['hoy'] }}</div>
        </div>
    </div>

    <div class="stat-card clima">
        <div class="stat-card-icon"><x-icono name="calor" :size="20" /></div>
        <div class="stat-card-content">
            <div class="stat-card-label">Alertas Climáticas</div>
            <div class="stat-card-value">{{ $alertas['climaticas'] }}</div>
        </div>
    </div>
</div>

{{-- ALERTAS --}}
@if(session('success'))
    <div class="alert-banner success">
        <span class="alert-icon"><x-icono name="ok" :size="20" /></span>
        <div class="alert-content">
            <div class="alert-title">{{ session('success') }}</div>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="alert-banner danger">
        
        <div class="alert-content">
            <div class="alert-title">{{ session('error') }}</div>
        </div>
    </div>
@endif

@if($parcelas->count() > 0)
    <div class="cultivos-grid">
        @foreach($parcelas as $parcela)
            <div class="cultivo-card">
                {{-- HEADER --}}
                <div class="cultivo-header">
                    <div class="cultivo-icon">
                        @if($parcela->cultivo)
                            @switch($parcela->cultivo->tipo)
                                @case('Hortalizas') @break
                                @case('Cereales') @break
                                @case('Frutales') @break
                                @case('Legumbres') @break
                                @case('Tubérculos') @break
                                @case('Aromáticas') @break
                                @case('Oleaginosas') @break
                                @default 
                            @endswitch
                        @else
                            
                        @endif
                    </div>
                    <div class="cultivo-badges">
                        @switch($parcela->estado_riego)
                            @case('critico')
                                <span class="badge" style="background: #dc3545; color: white;">CRÍTICO</span>
                                @break
                            @case('hoy')
                                <span class="badge" style="background: #ffc107; color: #000;">RIEGO HOY</span>
                                @break
                            @case('proximo')
                                <span class="badge" style="background: #17a2b8; color: white;">PRÓXIMO</span>
                                @break
                            @default
                                <span class="badge badge-tipo">OK</span>
                        @endswitch
                    </div>
                </div>

                {{-- TÍTULO Y UBICACIÓN --}}
                <div class="cultivo-title">{{ $parcela->nombre }}</div>
                <div class="cultivo-cientifico">
                    <span><x-icono name="parcela" :size="20" /></span>
                    <span>{{ $parcela->ubicacion_completa }}</span>
                </div>

                {{-- ESTADÍSTICAS DE RIEGO --}}
                <div class="cultivo-stats">
                    <div class="stat-item">
                        <div class="stat-label">Área</div>
                        <div class="stat-value">
                            
                            <span>{{ number_format($parcela->area, 0) }} m²</span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-label">Cultivo</div>
                        <div class="stat-value">
                            <span><x-icono name="parcela" :size="20" /></span>
                            <span style="font-size: 13px;">{{ Str::limit($parcela->cultivo->nombreCultivo ?? 'N/A', 12) }}</span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-label">Próximo Riego</div>
                        <div class="stat-value">
                            <span><x-icono name="agua" :size="20" /></span>
                            <span>{{ $parcela->proximo_riego_estimado?->format('d/m') ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-label">Litros/Riego</div>
                        <div class="stat-value">
                            <span><x-icono name="agua" :size="20" /></span>
                            <span>{{ number_format($parcela->litros_por_riego ?? 0, 0) }}L</span>
                        </div>
                    </div>
                </div>

                {{-- WIDGET DE CLIMA --}}
                @if($parcela->climaActual)
                    <div class="clima-widget">
                        <div class="clima-header">
                            <div class="clima-temp">
                                @if($parcela->climaActual->icono_codigo)
                                    <img src="{{ $parcela->climaActual->icono_url }}" 
                                         alt="Clima" 
                                         class="clima-icon">
                                @else
                                    <span style="font-size: 40px;"><x-icono name="calor" :size="20" /></span>
                                @endif
                                <div>
                                    <div class="temp-principal">{{ number_format($parcela->climaActual->temperatura, 1) }}°C</div>
                                    <div class="clima-descripcion">{{ $parcela->climaActual->descripcion }}</div>
                                </div>
                            </div>
                            
                            {{-- Botón actualizar clima --}}
                            <form action="{{ route('parcelas.actualizar-clima', $parcela) }}" 
                                  method="POST" 
                                  style="margin: 0;">
                                @csrf
                                <button type="submit" class="btn-actualizar-clima" title="Actualizar clima ahora">
                                    
                                </button>
                            </form>
                        </div>

                        <div class="clima-detalles">
                            <div class="clima-item">
                                <span><x-icono name="agua" :size="20" /></span>
                                <span>Humedad {{ $parcela->climaActual->humedad }}%</span>
                            </div>
                            <div class="clima-item">
                                
                                <span>Viento {{ number_format($parcela->climaActual->viento_velocidad, 1) }} m/s</span>
                            </div>
                            <div class="clima-item">
                                <span><x-icono name="lluvia" :size="20" /></span>
                                <span>Nubes {{ $parcela->climaActual->nubosidad }}%</span>
                            </div>
                        </div>

                        {{-- Alertas climáticas --}}
                        @if($parcela->climaActual->tiene_alertas)
                            <div class="clima-alertas">
                                @foreach($parcela->climaActual->alertas_activas as $alerta)
                                    <span class="alerta-badge alerta-{{ $alerta['tipo'] }}">
                                        {{ $alerta['mensaje'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="clima-timestamp">
                            <span><x-icono name="reloj" :size="20" /></span>
                            <span>Actualizado: {{ $parcela->climaActual->fecha_registro->diffForHumans() }}</span>
                        </div>
                    </div>
                @else
                    <div class="clima-sin-datos">
                        <div style="margin-bottom: 8px;">Sin datos climáticos</div>
                        <form action="{{ route('parcelas.actualizar-clima', $parcela) }}" 
                              method="POST" 
                              style="display: inline;">
                            @csrf
                            <button type="submit" class="btn-actualizar-clima">
                                
                                <span>Obtener Clima</span>
                            </button>
                        </form>
                    </div>
                @endif

                {{-- ACCIONES --}}
                <div class="cultivo-actions">
                    <a href="{{ route('parcelas.show', $parcela) }}" class="btn btn-primary">
                        
                        <span>Ver</span>
                    </a>
                    <a href="{{ route('parcelas.edit', $parcela) }}" class="btn btn-secondary">
                        
                        <span>Editar</span>
                    </a>
                    <form action="{{ route('parcelas.destroy', $parcela) }}" 
                          method="POST" 
                          style="flex: 1;" 
                          onsubmit="return confirm('¿Estás seguro de eliminar esta parcela? Se perderán todos los registros de riego asociados.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" style="width: 100%;">
                            
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    {{-- PAGINACIÓN --}}
    <div style="margin-top: 30px;">
        {{ $parcelas->links() }}
    </div>
@else
    <div class="empty-state">
        <div class="empty-icon"><x-icono name="parcela" :size="20" /></div>
        <div class="empty-title">No hay parcelas registradas</div>
        <div class="empty-text">
            Comienza agregando tu primera parcela para gestionar el riego y monitorear el clima
        </div>
        <a href="{{ route('parcelas.create') }}" class="btn-add">
            
            <span>Crear Primera Parcela</span>
        </a>
    </div>
@endif
@endsection