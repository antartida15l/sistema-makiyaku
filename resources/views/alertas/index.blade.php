@extends('layouts.app')

@section('title', 'Mis Alertas')

@push('styles')
<style>
    .alertas-container {
        max-width: 1400px;
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
        background: linear-gradient(135deg, #ffc107, #ff9800);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        box-shadow: 0 8px 20px rgba(255, 193, 7, 0.3);
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

    /* Resumen de alertas */
    .alertas-resumen {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 35px;
    }

    .resumen-card {
        background: var(--white);
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border-left: 4px solid;
        transition: all 0.3s;
    }

    .resumen-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    }

    .resumen-card.criticas {
        border-left-color: #dc3545;
        background: linear-gradient(135deg, #ffffff, #ffe5e8);
    }

    .resumen-card.altas {
        border-left-color: #ffc107;
        background: linear-gradient(135deg, #ffffff, #fff8e5);
    }

    .resumen-card.medias {
        border-left-color: #17a2b8;
        background: linear-gradient(135deg, #ffffff, #e5f7fa);
    }

    .resumen-card.info {
        border-left-color: #007bff;
        background: linear-gradient(135deg, #ffffff, #e5f0ff);
    }

    .resumen-numero {
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 5px;
    }

    .resumen-label {
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--muted);
    }

    /* Sección de alertas */
    .alertas-seccion {
        margin-bottom: 40px;
    }

    .seccion-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        padding: 15px 20px;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 12px;
        border-left: 5px solid;
    }

    .seccion-header.criticas { border-left-color: #dc3545; }
    .seccion-header.altas { border-left-color: #ffc107; }
    .seccion-header.medias { border-left-color: #17a2b8; }
    .seccion-header.info { border-left-color: #007bff; }

    .seccion-icon {
        font-size: 32px;
    }

    .seccion-titulo {
        flex: 1;
    }

    .seccion-titulo h3 {
        font-size: 20px;
        font-weight: 800;
        margin-bottom: 2px;
    }

    .seccion-count {
        background: var(--white);
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    /* Tarjeta de alerta */
    .alerta-card {
        background: var(--white);
        border-radius: 14px;
        padding: 25px;
        margin-bottom: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border-left: 5px solid;
        transition: all 0.3s;
        position: relative;
    }

    .alerta-card:hover {
        transform: translateX(5px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    }

    .alerta-card.critica { border-left-color: #dc3545; }
    .alerta-card.alta { border-left-color: #ffc107; }
    .alerta-card.media { border-left-color: #17a2b8; }
    .alerta-card.info { border-left-color: #007bff; }

    .alerta-header {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 15px;
    }

    .alerta-icon {
        font-size: 48px;
        flex-shrink: 0;
    }

    .alerta-content {
        flex: 1;
    }

    .alerta-parcela {
        font-size: 13px;
        color: var(--muted);
        font-weight: 600;
        margin-bottom: 5px;
    }

    .alerta-titulo {
        font-size: 20px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 8px;
    }

    .alerta-mensaje {
        font-size: 15px;
        color: #495057;
        line-height: 1.6;
        font-weight: 500;
    }

    .alerta-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 2px solid #f0f0f0;
    }

    .alerta-tiempo {
        font-size: 13px;
        color: var(--muted);
        font-weight: 600;
    }

    .alerta-acciones {
        display: flex;
        gap: 10px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: var(--white);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
    }

    .btn-secondary {
        background: #6c757d;
        color: var(--white);
        box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    .btn-archivar {
        background: #e9ecef;
        color: #495057;
        border: 2px solid #dee2e6;
    }

    .btn-archivar:hover {
        background: #dee2e6;
        border-color: #28a745;
        color: #28a745;
    }

    .btn-marcar-leida {
    background: #e9ecef;
    color: #495057;
    border: 2px solid #dee2e6;
    transition: all 0.3s;
    }

    .btn-marcar-leida:hover {
        background: #28a745;
        color: white;
        border-color: #28a745;
        transform: translateY(-2px);
    }

    .btn-marcar-leida:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: var(--white);
        border-radius: 14px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .empty-state-icon {
        font-size: 80px;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        font-size: 24px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 10px;
    }

    .empty-state p {
        font-size: 16px;
        color: var(--muted);
        font-weight: 500;
    }

    .alert {
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .alert-success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border: 2px solid #28a745;
        color: #155724;
    }

    @media (max-width: 768px) {
        .alertas-resumen {
            grid-template-columns: 1fr;
        }

        .alerta-footer {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }

        .alerta-acciones {
            width: 100%;
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

@section('content')
<div class="alertas-container">
    {{-- HEADER --}}
    <div class="page-header">
        <div class="page-header-content">
            {{-- Agrega esto DESPUÉS del page-header, antes del resumen --}}
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 15px; background: #f8f9fa; border-radius: 12px; border-left: 4px solid #28a745;">
                <div>
                    <span style="font-size: 14px; color: #6c757d;">
                        ⏰ Última actualización: 
                        <strong>{{ session('ultima_actualizacion_alertas') ? session('ultima_actualizacion_alertas')->diffForHumans() : 'Nunca' }}</strong>
                    </span>
                </div>
                
                <form action="{{ route('alertas.refrescar') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
                        <span style="font-size: 18px;">🔄</span>
                        Refrescar Alertas
                    </button>
                </form>
            </div>
            <div class="page-icon">🔔</div>
            <div class="page-title-group">
                <h1>Mis Alertas</h1>
                <p class="page-subtitle">
                    {{ $resumen['total'] }} alerta(s) activa(s) • {{ $resumen['nuevas_hoy'] }} nueva(s) hoy
                </p>
            </div>
        </div>
    </div>

    {{-- MENSAJES DE ÉXITO --}}
    @if(session('success'))
        <div class="alert alert-success">
            <span style="font-size: 24px;">✅</span>
            <strong>{{ session('success') }}</strong>
        </div>
    @endif

    {{-- RESUMEN DE ALERTAS --}}
    <div class="alertas-resumen">
        <div class="resumen-card criticas">
            <div class="resumen-numero" style="color: #dc3545;">{{ $resumen['criticas'] }}</div>
            <div class="resumen-label">Críticas</div>
        </div>

        <div class="resumen-card altas">
            <div class="resumen-numero" style="color: #ffc107;">{{ $resumen['altas'] }}</div>
            <div class="resumen-label">Altas</div>
        </div>

        <div class="resumen-card medias">
            <div class="resumen-numero" style="color: #17a2b8;">{{ $resumen['medias'] }}</div>
            <div class="resumen-label">Medias</div>
        </div>

        <div class="resumen-card info">
            <div class="resumen-numero" style="color: #007bff;">{{ $resumen['info'] }}</div>
            <div class="resumen-label">Informativas</div>
        </div>
    </div>

    {{-- ALERTAS CRÍTICAS --}}
    @if($alertasPorNivel['criticas']->count() > 0)
        <div class="alertas-seccion">
            <div class="seccion-header criticas">
                <div class="seccion-icon">🚨</div>
                <div class="seccion-titulo">
                    <h3>Alertas Críticas</h3>
                </div>
                <div class="seccion-count" style="color: #dc3545;">
                    {{ $alertasPorNivel['criticas']->count() }}
                </div>
            </div>

            @foreach($alertasPorNivel['criticas'] as $alerta)
                @include('alertas.partials.alerta-card', ['alerta' => $alerta])
            @endforeach
        </div>
    @endif

    {{-- ALERTAS ALTAS --}}
    @if($alertasPorNivel['altas']->count() > 0)
        <div class="alertas-seccion">
            <div class="seccion-header altas">
                <div class="seccion-icon">⚠️</div>
                <div class="seccion-titulo">
                    <h3>Alertas Altas</h3>
                </div>
                <div class="seccion-count" style="color: #ffc107;">
                    {{ $alertasPorNivel['altas']->count() }}
                </div>
            </div>

            @foreach($alertasPorNivel['altas'] as $alerta)
                @include('alertas.partials.alerta-card', ['alerta' => $alerta])
            @endforeach
        </div>
    @endif

    {{-- ALERTAS MEDIAS --}}
    @if($alertasPorNivel['medias']->count() > 0)
        <div class="alertas-seccion">
            <div class="seccion-header medias">
                <div class="seccion-icon">ℹ️</div>
                <div class="seccion-titulo">
                    <h3>Alertas Medias</h3>
                </div>
                <div class="seccion-count" style="color: #17a2b8;">
                    {{ $alertasPorNivel['medias']->count() }}
                </div>
            </div>

            @foreach($alertasPorNivel['medias'] as $alerta)
                @include('alertas.partials.alerta-card', ['alerta' => $alerta])
            @endforeach
        </div>
    @endif

    {{-- ALERTAS INFORMATIVAS --}}
    @if($alertasPorNivel['info']->count() > 0)
        <div class="alertas-seccion">
            <div class="seccion-header info">
                <div class="seccion-icon">💡</div>
                <div class="seccion-titulo">
                    <h3>Alertas Informativas</h3>
                </div>
                <div class="seccion-count" style="color: #007bff;">
                    {{ $alertasPorNivel['info']->count() }}
                </div>
            </div>

            @foreach($alertasPorNivel['info'] as $alerta)
                @include('alertas.partials.alerta-card', ['alerta' => $alerta])
            @endforeach
        </div>
    @endif

    {{-- ESTADO VACÍO --}}
    @if($resumen['total'] === 0)
        <div class="empty-state">
            <div class="empty-state-icon">🎉</div>
            <h3>¡Todo al día!</h3>
            <p>No tienes alertas pendientes en este momento</p>
        </div>
    @endif


</div>

@push('scripts')
<script>
    /**
 * ═══════════════════════════════════════════════════════════
 * SCRIPT PARA MARCAR ALERTAS COMO LEÍDAS
 * Funciona para TODO TIPO de alertas (riego, clima, mantenimiento)
 * ═══════════════════════════════════════════════════════════
 */

/**
 * Marcar alerta como leída (AJAX)
 */
function marcarComoLeida(alertaId) {
    // Deshabilitar el botón mientras se procesa
    const boton = document.querySelector(`[data-alerta-id="${alertaId}"]`);
    if (boton) {
        boton.disabled = true;
        boton.innerHTML = '⏳ Archivando...';
    }

    // Enviar petición AJAX
    fetch(`/alertas/${alertaId}/marcar-leida`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Ocultar la alerta con animación
            const alertaCard = document.getElementById(`alerta-${alertaId}`);
            if (alertaCard) {
                alertaCard.style.transition = 'all 0.3s ease';
                alertaCard.style.opacity = '0';
                alertaCard.style.transform = 'translateX(-30px)';
                
                setTimeout(() => {
                    alertaCard.remove();
                    
                    // Verificar si quedan alertas en la sección
                    verificarSeccionVacia(alertaCard);
                    
                    // Actualizar contadores
                    actualizarContadores();
                }, 300);
            }

            // Mostrar notificación de éxito
            mostrarNotificacion('✅ Alerta archivada correctamente', 'success');
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('❌ Error al archivar la alerta: ' + error.message, 'error');
        
        // Rehabilitar el botón
        if (boton) {
            boton.disabled = false;
            boton.innerHTML = '✓ Marcar como leída';
        }
    });
}

/**
 * Verificar si una sección quedó sin alertas
 */
function verificarSeccionVacia(alertaEliminada) {
    const seccion = alertaEliminada.closest('.alertas-seccion');
    if (seccion) {
        const alertasRestantes = seccion.querySelectorAll('.alerta-card');
        
        if (alertasRestantes.length === 0) {
            // Ocultar toda la sección si no quedan alertas
            seccion.style.transition = 'all 0.3s ease';
            seccion.style.opacity = '0';
            seccion.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                seccion.remove();
                
                // Si no quedan alertas en ninguna sección, mostrar estado vacío
                const todasLasSecciones = document.querySelectorAll('.alertas-seccion');
                if (todasLasSecciones.length === 0) {
                    mostrarEstadoVacio();
                }
            }, 300);
        }
    }
}

/**
 * Actualizar contadores del resumen
 */
function actualizarContadores() {
    const totalCriticas = document.querySelectorAll('.alerta-card.critica').length;
    const totalAltas = document.querySelectorAll('.alerta-card.alta').length;
    const totalMedias = document.querySelectorAll('.alerta-card.media').length;
    const totalInfo = document.querySelectorAll('.alerta-card.info').length;
    const totalGeneral = totalCriticas + totalAltas + totalMedias + totalInfo;

    // Actualizar números en las tarjetas de resumen
    const resumenCards = document.querySelectorAll('.resumen-numero');
    if (resumenCards.length >= 4) {
        resumenCards[0].textContent = totalCriticas;
        resumenCards[1].textContent = totalAltas;
        resumenCards[2].textContent = totalMedias;
        resumenCards[3].textContent = totalInfo;
    }

    // Actualizar subtítulo del header
    const pageSubtitle = document.querySelector('.page-subtitle');
    if (pageSubtitle) {
        pageSubtitle.textContent = `${totalGeneral} alerta(s) activa(s)`;
    }

    // Actualizar contadores de cada sección
    document.querySelectorAll('.seccion-count').forEach((contador, index) => {
        const totales = [totalCriticas, totalAltas, totalMedias, totalInfo];
        contador.textContent = totales[index] || 0;
    });

    // Actualizar badge del sidebar
    actualizarBadgeSidebar(totalGeneral);
}

/**
 * Actualizar badge de alertas en el sidebar
 */
function actualizarBadgeSidebar(cantidad) {
    const badges = document.querySelectorAll('.nav-badge');
    badges.forEach(badge => {
        if (cantidad > 0) {
            badge.textContent = cantidad;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    });
}

/**
 * Mostrar estado vacío cuando no hay alertas
 */
function mostrarEstadoVacio() {
    const container = document.querySelector('.alertas-container');
    if (container) {
        const emptyState = `
            <div class="empty-state" style="animation: fadeIn 0.5s;">
                <div class="empty-state-icon">🎉</div>
                <h3>¡Todo al día!</h3>
                <p>No tienes alertas pendientes en este momento</p>
            </div>
        `;
        
        // Eliminar secciones y agregar estado vacío
        const secciones = container.querySelectorAll('.alertas-seccion');
        secciones.forEach(s => s.remove());
        
        container.insertAdjacentHTML('beforeend', emptyState);
    }
}

/**
 * Mostrar notificación temporal
 */
function mostrarNotificacion(mensaje, tipo = 'success') {
    const colores = {
        success: { bg: '#d4edda', border: '#28a745', text: '#155724', icon: '✅' },
        error: { bg: '#f8d7da', border: '#dc3545', text: '#721c24', icon: '❌' },
        warning: { bg: '#fff3cd', border: '#ffc107', text: '#856404', icon: '⚠️' },
        info: { bg: '#d1ecf1', border: '#17a2b8', text: '#0c5460', icon: 'ℹ️' }
    };
    
    const config = colores[tipo] || colores.success;
    
    const notif = document.createElement('div');
    notif.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 320px;
        max-width: 400px;
        background: ${config.bg};
        color: ${config.text};
        border-left: 4px solid ${config.border};
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
    `;
    
    notif.innerHTML = `
        <span style="font-size: 24px;">${config.icon}</span>
        <span style="flex: 1;">${mensaje}</span>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 20px; cursor: pointer; opacity: 0.6; transition: all 0.2s;">×</button>
    `;
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notif.remove(), 300);
    }, 4000);
}

/**
 * Marcar TODAS las alertas como leídas
 */
function marcarTodasLeidas() {
    if (!confirm('¿Estás seguro de marcar TODAS las alertas como leídas?')) {
        return;
    }

    fetch('/alertas/marcar-todas-leidas', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion(`✅ ${data.count} alertas archivadas`, 'success');
            
            // Recargar página después de 1 segundo
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('❌ Error al archivar alertas', 'error');
    });
}

// Animaciones CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
`;
document.head.appendChild(style);

// Inicializar contadores al cargar
document.addEventListener('DOMContentLoaded', function() {
    actualizarContadores();
    
    // Auto-cerrar mensajes de sesión después de 5 segundos
    const sessionAlerts = document.querySelectorAll('.alert[role="alert"]');
    sessionAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'slideInDown 0.4s reverse';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });
});

console.log('✅ Sistema de alertas cargado correctamente');
</script>
@endpush
@endsection