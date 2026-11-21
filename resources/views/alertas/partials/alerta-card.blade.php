{{-- resources/views/alertas/partials/alerta-card.blade.php --}}

@php
    // Detectar si es alerta de RIEGO
    $esAlertaRiego = in_array($alerta->tipo, ['riego_urgente', 'riego_hoy', 'riego_proximo']);
    
    // Configuración visual según tipo de riego
    $configRiego = match($alerta->tipo) {
        'riego_urgente' => [
            'badge' => 'RIEGO URGENTE',
            'badge_color' => '#dc3545',
            'badge_bg' => 'linear-gradient(135deg, #dc3545, #c82333)',
            'border_color' => '#dc3545',
            'icon_size' => '64px',
            'pulso' => true,
        ],
        'riego_hoy' => [
            'badge' => 'RIEGO HOY',
            'badge_color' => '#ffc107',
            'badge_bg' => 'linear-gradient(135deg, #ffc107, #ff9800)',
            'border_color' => '#ffc107',
            'icon_size' => '56px',
            'pulso' => false,
        ],
        'riego_proximo' => [
            'badge' => 'RIEGO PRÓXIMO',
            'badge_color' => '#17a2b8',
            'badge_bg' => 'linear-gradient(135deg, #17a2b8, #138496)',
            'border_color' => '#17a2b8',
            'icon_size' => '48px',
            'pulso' => false,
        ],
        default => null,
    };
@endphp

<div class="alerta-card {{ $alerta->nivel }} {{ $esAlertaRiego ? 'alerta-riego' : '' }}" 
     id="alerta-{{ $alerta->id }}"
     @if($esAlertaRiego && $configRiego['pulso']) data-pulso="true" @endif>
    
    {{-- Badge especial para alertas de RIEGO --}}
    @if($esAlertaRiego)
        <div class="badge-riego" style="background: {{ $configRiego['badge_bg'] }};">
            💧 {{ $configRiego['badge'] }}
        </div>
    @endif

    <div class="alerta-header">
        <div class="alerta-icon" style="font-size: {{ $esAlertaRiego ? $configRiego['icon_size'] : '48px' }};">
            {{ $alerta->icono }}
        </div>
        
        <div class="alerta-content">
            <div class="alerta-parcela">
                🌾 {{ $alerta->parcela->nombre }}
                @if($alerta->parcela->cultivo)
                    • {{ $alerta->parcela->cultivo->nombreCultivo }}
                @endif
            </div>
            
            <div class="alerta-titulo">
                {{ $alerta->titulo }}
            </div>
            
            <div class="alerta-mensaje">
                {{ $alerta->mensaje }}
            </div>
        </div>
    </div>

    {{-- Detalles adicionales --}}
    @if($alerta->datos_adicionales)
        <div class="alerta-detalles {{ $esAlertaRiego ? 'alerta-detalles-riego' : '' }}">
            <strong>📊 Información detallada:</strong>
            <div class="detalles-grid">
                @if(isset($alerta->datos_adicionales['litros_recomendados']))
                    <div class="detalle-item">
                        <span class="detalle-icon">💧</span>
                        <span class="detalle-label">Litros necesarios:</span>
                        <span class="detalle-valor">{{ number_format($alerta->datos_adicionales['litros_recomendados'], 0) }}L</span>
                    </div>
                @endif
                
                @if(isset($alerta->datos_adicionales['dias_sin_regar']))
                    <div class="detalle-item">
                        <span class="detalle-icon">📅</span>
                        <span class="detalle-label">Días sin regar:</span>
                        <span class="detalle-valor">{{ $alerta->datos_adicionales['dias_sin_regar'] }} días</span>
                    </div>
                @endif
                
                @if(isset($alerta->datos_adicionales['temperatura']))
                    <div class="detalle-item">
                        <span class="detalle-icon">🌡️</span>
                        <span class="detalle-label">Temperatura:</span>
                        <span class="detalle-valor">{{ $alerta->datos_adicionales['temperatura'] }}°C</span>
                    </div>
                @endif
                
                @if(isset($alerta->datos_adicionales['humedad']))
                    <div class="detalle-item">
                        <span class="detalle-icon">💨</span>
                        <span class="detalle-label">Humedad:</span>
                        <span class="detalle-valor">{{ $alerta->datos_adicionales['humedad'] }}%</span>
                    </div>
                @endif
                
                @if(isset($alerta->datos_adicionales['probabilidad_lluvia']))
                    <div class="detalle-item">
                        <span class="detalle-icon">🌧️</span>
                        <span class="detalle-label">Prob. lluvia:</span>
                        <span class="detalle-valor">{{ $alerta->datos_adicionales['probabilidad_lluvia'] }}%</span>
                    </div>
                @endif
                
                @if(isset($alerta->datos_adicionales['puntaje_desglose']))
                    <div class="detalle-item detalle-urgencia">
                        <span class="detalle-icon">⚡</span>
                        <span class="detalle-label">Urgencia:</span>
                        <span class="detalle-valor">{{ $alerta->puntaje_urgencia }}/100</span>
                        <div class="barra-urgencia">
                            <div class="barra-progreso" style="width: {{ $alerta->puntaje_urgencia }}%;"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="alerta-footer">
        <div class="alerta-tiempo">
            🕐 {{ $alerta->tiempo_transcurrido }}
        </div>

        <div class="alerta-acciones">
            @if($alerta->accion_url)
                <a href="{{ $alerta->accion_url }}" class="btn btn-primary">
                    {{ $esAlertaRiego ? '💧 Registrar Riego' : ($alerta->accion_texto ?? 'Ver Detalles') }}
                </a>
            @endif
            
            <button 
                class="btn btn-secondary btn-marcar-leida" 
                data-alerta-id="{{ $alerta->id }}"
                onclick="marcarComoLeida({{ $alerta->id }})"
            >
                ✓ Marcar como leída
            </button>
        </div>
    </div>
</div>

<style>
/* ═══════════════════════════════════════════════════════════
   ESTILOS ESPECIALES PARA ALERTAS DE RIEGO
   ═══════════════════════════════════════════════════════════ */

/* Badge superior para alertas de riego */
.badge-riego {
    position: absolute;
    top: -12px;
    right: 20px;
    padding: 8px 20px;
    border-radius: 20px;
    color: white;
    font-weight: 800;
    font-size: 12px;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    z-index: 10;
    text-transform: uppercase;
}

/* Efecto de pulso para alertas URGENTES */
.alerta-riego[data-pulso="true"] {
    animation: pulsoAlerta 2s ease-in-out infinite;
}

@keyframes pulsoAlerta {
    0%, 100% {
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }
    50% {
        box-shadow: 0 8px 30px rgba(220, 53, 69, 0.6);
        transform: translateY(-2px);
    }
}

/* Borde más grueso para alertas de riego */
.alerta-riego {
    border-left-width: 8px !important;
}

/* Detalles con estilo especial */
.alerta-detalles {
    background: #f8f9fa;
    padding: 18px;
    border-radius: 12px;
    margin: 15px 0;
    border-left: 4px solid #dee2e6;
}

.alerta-detalles-riego {
    background: linear-gradient(135deg, #e3f2fd, #f8f9fa);
    border-left-color: #2196f3;
}

.alerta-detalles strong {
    display: block;
    margin-bottom: 12px;
    font-size: 14px;
    color: #495057;
}

/* Grid de detalles */
.detalles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.detalle-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: white;
    border-radius: 8px;
    font-size: 13px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.detalle-icon {
    font-size: 18px;
    flex-shrink: 0;
}

.detalle-label {
    color: #6c757d;
    font-weight: 600;
}

.detalle-valor {
    margin-left: auto;
    font-weight: 800;
    color: #212529;
}

/* Barra de urgencia */
.detalle-urgencia {
    flex-direction: column;
    align-items: flex-start;
    grid-column: 1 / -1;
}

.barra-urgencia {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 8px;
}

.barra-progreso {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #ffc107, #dc3545);
    border-radius: 10px;
    transition: width 0.5s ease;
}

/* Responsive */
@media (max-width: 768px) {
    .detalles-grid {
        grid-template-columns: 1fr;
    }
    
    .badge-riego {
        top: -10px;
        right: 10px;
        font-size: 11px;
        padding: 6px 14px;
    }
}
</style>