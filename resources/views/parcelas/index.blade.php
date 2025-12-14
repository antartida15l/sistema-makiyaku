@extends('layouts.app')

@section('title', 'Mis Parcelas')

@push('styles')
<style>
    .parcelas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
        margin-top: 25px;
    }

    .parcela-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 25px;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .parcela-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    .parcela-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary);
    }

    .parcela-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 20px;
    }

    .parcela-icon {
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

    .parcela-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-activo {
        background: #d4edda;
        color: #155724;
    }

    .status-inactivo {
        background: #f8d7da;
        color: #721c24;
    }

    .parcela-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 8px;
    }

    .parcela-cultivo {
        color: var(--muted);
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 20px;
    }

    .parcela-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
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
        font-size: 18px;
        font-weight: 800;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .parcela-actions {
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
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 30px;
        flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
        padding: 10px 16px;
        background: var(--white);
        color: var(--dark);
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: var(--transition);
        border: 2px solid #e9ecef;
    }

    .pagination a:hover {
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
        transform: translateY(-2px);
    }

    .pagination .active span {
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
    }

    .pagination .disabled span {
        opacity: 0.5;
        cursor: not-allowed;
    }

    @media (max-width: 768px) {
        .parcelas-grid {
            grid-template-columns: 1fr;
        }

        .header-actions {
            flex-direction: column;
            gap: 15px;
        }
    }
</style>
@endpush

@section('content')
<div class="header-actions">
    <div class="header-title">🌾 Mis Parcelas</div>
    <a href="{{ route('parcelas.create') }}" class="btn-add">
        <span>➕</span>
        <span>Nueva Parcela</span>
    </a>
</div>

@if($parcelas->count() > 0)
    <div class="parcelas-grid">
        @foreach($parcelas as $parcela)
            <div class="parcela-card">
                <div class="parcela-header">
                    <div class="parcela-icon">🌱</div>
                    <span class="parcela-status status-{{ $parcela->activo ? 'activo' : 'inactivo' }}">
                        {{ $parcela->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>

                <div class="parcela-title">{{ $parcela->nombre }}</div>
                <div class="parcela-cultivo">
                    <span>🌾</span>
                    <span>{{ $parcela->tipo_cultivo ?? 'Sin cultivo' }}</span>
                </div>

                <div class="parcela-stats">
                    <div class="stat-item">
                        <div class="stat-label">Área</div>
                        <div class="stat-value">
                            <span>📏</span>
                            <span>{{ $parcela->area ?? 0 }} m²</span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-label">Humedad</div>
                        <div class="stat-value">
                            <span>💧</span>
                            <span>
                                @if($parcela->lecturas && $parcela->lecturas->count() > 0)
                                    {{ number_format($parcela->lecturas->first()->humedad_suelo, 1) }}%
                                @else
                                    --
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-label">Temperatura</div>
                        <div class="stat-value">
                            <span>🌡️</span>
                            <span>
                                @if($parcela->lecturas && $parcela->lecturas->count() > 0)
                                    {{ number_format($parcela->lecturas->first()->temperatura, 1) }}°C
                                @else
                                    --
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-label">Riego</div>
                        <div class="stat-value">
                            <span>🚿</span>
                            <span>{{ $parcela->estado_riego ? 'ON' : 'OFF' }}</span>
                        </div>
                    </div>
                </div>

                <div class="parcela-actions">
                    <a href="{{ route('parcelas.show', $parcela) }}" class="btn btn-primary">
                        <span>👁️</span>
                        <span>Ver</span>
                    </a>
                    <a href="{{ route('parcelas.edit', $parcela) }}" class="btn btn-secondary">
                        <span>✏️</span>
                        <span>Editar</span>
                    </a>
                    <form action="{{ route('parcelas.destroy', $parcela) }}" method="POST" style="flex: 1;" 
                          onsubmit="return confirm('¿Estás seguro de eliminar esta parcela?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" style="width: 100%;">
                            <span>🗑️</span>
                            <span>Eliminar</span>
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="pagination">
        {{ $parcelas->links() }}
    </div>
@else
    <div class="empty-state">
        <div class="empty-icon">🌾</div>
        <div class="empty-title">No tienes parcelas aún</div>
        <div class="empty-text">
            Comienza agregando tu primera parcela para monitorear tus cultivos
        </div>
        <a href="{{ route('parcelas.create') }}" class="btn-add">
            <span>➕</span>
            <span>Crear Primera Parcela</span>
        </a>
    </div>
@endif
@endsection