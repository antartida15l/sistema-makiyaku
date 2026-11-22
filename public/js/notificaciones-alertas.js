/**
 * ════════════════════════════════════════════════════════════
 * SISTEMA DE NOTIFICACIONES SONORAS PARA ALERTAS
 * ════════════════════════════════════════════════════════════
 * 
 * Características:
 * - Reproduce sonido cuando hay nuevas alertas
 * - Re-notifica cada 10 minutos si hay alertas críticas sin leer
 * - Re-notifica cada 5 minutos adicionales si persisten
 * - Muestra notificaciones toast visuales
 * - Permite silenciar temporalmente
 */

class SistemaNotificacionesAlertas {
    constructor() {
        this.intervaloVerificacion = 60000; // 1 minuto
        this.tiempoRenotificacion1 = 600000; // 10 minutos
        this.tiempoRenotificacion2 = 300000; // 5 minutos adicionales
        this.ultimaVerificacion = Date.now();
        this.alertasCriticasDetectadas = new Map(); // Cambio: usar Map para guardar más info
        this.silenciadoGlobal = false;
        
        // Contextos de audio para diferentes navegadores
        this.audioContext = null;
        this.inicializarAudio();
        
        // Iniciar sistema
        this.iniciar();
    }

    /**
     * Inicializar contexto de audio
     */
    inicializarAudio() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audioContext = new AudioContext();
            console.log('Contexto de audio inicializado');
        } catch (e) {
            console.warn('Audio no soportado en este navegador:', e);
        }
    }

    /**
     * Reproducir sonido de alerta según severidad
     */
    async reproducirSonido(severidad) {
        if (this.silenciadoGlobal || !this.audioContext) {
            return;
        }

        try {
            // Reanudar contexto de audio si está suspendido
            if (this.audioContext.state === 'suspended') {
                await this.audioContext.resume();
            }

            const oscilador = this.audioContext.createOscillator();
            const ganancia = this.audioContext.createGain();
            
            oscilador.connect(ganancia);
            ganancia.connect(this.audioContext.destination);

            // Configurar sonido según severidad
            switch(severidad) {
                case 'critica':
                case 'critico':
                    // Sonido de alarma urgente (3 beeps rápidos)
                    this.beepUrgente(oscilador, ganancia);
                    break;
                case 'alta':
                case 'alto':
                    // Sonido de advertencia (2 beeps)
                    this.beepAdvertencia(oscilador, ganancia);
                    break;
                case 'media':
                case 'medio':
                    // Sonido de notificación (1 beep)
                    this.beepNotificacion(oscilador, ganancia);
                    break;
                default:
                    // Sonido suave informativo
                    this.beepInformativo(oscilador, ganancia);
            }
        } catch (e) {
            console.error('Error reproduciendo sonido:', e);
        }
    }

    /**
     * Beep urgente (3 beeps rápidos)
     */
    beepUrgente(osc, gain) {
        const ahora = this.audioContext.currentTime;
        
        osc.frequency.setValueAtTime(880, ahora); // A5
        gain.gain.setValueAtTime(0.3, ahora);
        
        // Beep 1
        gain.gain.linearRampToValueAtTime(0.3, ahora + 0.1);
        gain.gain.linearRampToValueAtTime(0, ahora + 0.15);
        
        // Beep 2
        gain.gain.setValueAtTime(0.3, ahora + 0.25);
        gain.gain.linearRampToValueAtTime(0, ahora + 0.3);
        
        // Beep 3
        gain.gain.setValueAtTime(0.3, ahora + 0.4);
        gain.gain.linearRampToValueAtTime(0, ahora + 0.5);
        
        osc.start(ahora);
        osc.stop(ahora + 0.5);
    }

    /**
     * Beep de advertencia (2 beeps)
     */
    beepAdvertencia(osc, gain) {
        const ahora = this.audioContext.currentTime;
        
        osc.frequency.setValueAtTime(660, ahora); // E5
        gain.gain.setValueAtTime(0.25, ahora);
        
        // Beep 1
        gain.gain.linearRampToValueAtTime(0.25, ahora + 0.15);
        gain.gain.linearRampToValueAtTime(0, ahora + 0.2);
        
        // Beep 2
        gain.gain.setValueAtTime(0.25, ahora + 0.35);
        gain.gain.linearRampToValueAtTime(0, ahora + 0.45);
        
        osc.start(ahora);
        osc.stop(ahora + 0.45);
    }

    /**
     * Beep de notificación (1 beep)
     */
    beepNotificacion(osc, gain) {
        const ahora = this.audioContext.currentTime;
        
        osc.frequency.setValueAtTime(440, ahora); // A4
        gain.gain.setValueAtTime(0.2, ahora);
        gain.gain.linearRampToValueAtTime(0, ahora + 0.2);
        
        osc.start(ahora);
        osc.stop(ahora + 0.2);
    }

    /**
     * Beep informativo (suave)
     */
    beepInformativo(osc, gain) {
        const ahora = this.audioContext.currentTime;
        
        osc.frequency.setValueAtTime(330, ahora); // E4
        gain.gain.setValueAtTime(0.15, ahora);
        gain.gain.linearRampToValueAtTime(0, ahora + 0.3);
        
        osc.start(ahora);
        osc.stop(ahora + 0.3);
    }

    /**
     * Mostrar notificación toast
     */
    mostrarToast(alerta) {
        // Crear elemento de toast si no existe
        let contenedor = document.getElementById('toast-container');
        if (!contenedor) {
            contenedor = document.createElement('div');
            contenedor.id = 'toast-container';
            contenedor.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
                max-width: 400px;
            `;
            document.body.appendChild(contenedor);
        }

        // Crear toast
        const toast = document.createElement('div');
        toast.className = `alerta-toast alerta-toast-${alerta.nivel}`;
        toast.style.cssText = `
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.3s ease-out;
            cursor: pointer;
            background: white;
            border-left: 4px solid ${this.getColorPorNivel(alerta.nivel)};
            transition: all 0.3s ease;
        `;

        toast.innerHTML = `
            <div style="width:6px;align-self:stretch;border-radius:3px;background:${alerta.nivel === "critico" ? "#dc3545" : (alerta.nivel === "alto" ? "#ffc107" : "#17a2b8")};flex-shrink:0;"></div>
            <div style="flex: 1;">
                <div style="font-weight: 700; font-size: 14px; margin-bottom: 4px; color: #343a40;">
                    ${alerta.titulo}
                </div>
                <div style="font-size: 12px; color: #6c757d;">
                    ${alerta.parcela}
                </div>
            </div>
            <button onclick="this.parentElement.remove()" style="
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                color: #999;
                padding: 0;
                width: 24px;
                height: 24px;
            ">×</button>
        `;

        // Click para ir a la alerta (si tiene URL)
        if (alerta.url) {
            toast.addEventListener('click', (e) => {
                if (e.target.tagName !== 'BUTTON') {
                    window.location.href = alerta.url;
                }
            });
        }

        contenedor.appendChild(toast);

        // Auto-remover después de 8 segundos
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 8000);
    }

    /**
     * Obtener color según nivel de severidad
     */
    getColorPorNivel(nivel) {
        const colores = {
            'critica': '#dc3545',
            'critico': '#dc3545',
            'alta': '#ffc107',
            'alto': '#ffc107',
            'media': '#17a2b8',
            'medio': '#17a2b8',
            'info': '#28a745'
        };
        return colores[nivel] || '#6c757d';
    }

    /**
     * Verificar alertas nuevas
     */
    async verificarAlertas() {
        try {
            const response = await fetch('/alertas/recientes', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                console.error('Error en respuesta:', response.status);
                return;
            }

            const data = await response.json();
            
            if (data.success && data.alertas && data.alertas.length > 0) {
                this.procesarAlertasNuevas(data.alertas);
            }
        } catch (error) {
            console.error('Error verificando alertas:', error);
        }
    }

    /**
     * Procesar alertas nuevas
     */
    procesarAlertasNuevas(alertas) {
        const ahora = Date.now();
        
        alertas.forEach(alerta => {
            const alertaId = alerta.id;
            
            // Si es una alerta nueva que no hemos detectado
            if (!this.alertasCriticasDetectadas.has(alertaId)) {
                
                console.log(`Nueva alerta detectada: ${alerta.titulo} (Nivel: ${alerta.nivel})`);
                
                // Reproducir sonido
                this.reproducirSonido(alerta.nivel);
                
                // Mostrar toast
                this.mostrarToast(alerta);
                
                // Registrar alerta con información para re-notificaciones
                this.alertasCriticasDetectadas.set(alertaId, {
                    primeraDeteccion: ahora,
                    ultimaNotificacion: ahora,
                    contadorNotificaciones: 1,
                    nivel: alerta.nivel,
                    titulo: alerta.titulo,
                    parcela: alerta.parcela,
                    icono: alerta.icono,
                    url: alerta.url
                });
            }
            // Si ya existe, verificar si necesita re-notificación
            else if (alerta.nivel === 'critica' || alerta.nivel === 'alta' || alerta.nivel === 'critico' || alerta.nivel === 'alto') {
                this.verificarRenotificacion(alertaId, ahora);
            }
        });
    }

    /**
     * Verificar si debe re-notificar
     */
    verificarRenotificacion(alertaId, ahora) {
        const info = this.alertasCriticasDetectadas.get(alertaId);
        if (!info) return;

        const tiempoTranscurrido = ahora - info.ultimaNotificacion;
        
        // Primera re-notificación: después de 10 minutos
        if (info.contadorNotificaciones === 1 && tiempoTranscurrido >= this.tiempoRenotificacion1) {
            console.log(`RE-NOTIFICACIÓN (10 min): ${info.titulo}`);
            
            this.reproducirSonido(info.nivel);
            this.mostrarToast({
                icono: info.icono,
                nivel: info.nivel,
                titulo: `RECORDATORIO: ${info.titulo}`,
                parcela: info.parcela,
                url: info.url
            });
            
            info.ultimaNotificacion = ahora;
            info.contadorNotificaciones++;
            this.alertasCriticasDetectadas.set(alertaId, info);
        }
        // Re-notificaciones subsiguientes: cada 5 minutos
        else if (info.contadorNotificaciones > 1 && tiempoTranscurrido >= this.tiempoRenotificacion2) {
            console.log(`RE-NOTIFICACIÓN (5 min) #${info.contadorNotificaciones}: ${info.titulo}`);
            
            this.reproducirSonido(info.nivel);
            this.mostrarToast({
                icono: info.icono,
                nivel: info.nivel,
                titulo: `URGENTE: ${info.titulo} (${info.contadorNotificaciones}° recordatorio)`,
                parcela: info.parcela,
                url: info.url
            });
            
            info.ultimaNotificacion = ahora;
            info.contadorNotificaciones++;
            this.alertasCriticasDetectadas.set(alertaId, info);
        }
    }

    /**
     * Silenciar temporalmente (30 minutos)
     */
    silenciarTemporal() {
        this.silenciadoGlobal = true;
        
        console.log('Notificaciones silenciadas por 30 minutos');
        
        // Mostrar mensaje
        this.mostrarToast({
            icono: '',
            nivel: 'info',
            titulo: 'Notificaciones silenciadas',
            parcela: 'Se reanudarán en 30 minutos',
            url: null
        });

        // Cambiar texto del botón
        const boton = document.getElementById('btn-silenciar-alertas');
        if (boton) {
            boton.innerHTML = 'Silenciado (30min)';
            boton.style.background = 'linear-gradient(135deg, #dc3545, #c82333)';
        }

        // Reactivar después de 30 minutos
        setTimeout(() => {
            this.silenciadoGlobal = false;
            console.log('Notificaciones reactivadas');
            
            this.mostrarToast({
                icono: '',
                nivel: 'info',
                titulo: 'Notificaciones reactivadas',
                parcela: 'Recibirás alertas nuevamente',
                url: null
            });

            // Restaurar botón
            if (boton) {
                boton.innerHTML = 'Silenciar 30min';
                boton.style.background = 'linear-gradient(135deg, #6c757d, #495057)';
            }
        }, 1800000); // 30 minutos
    }

    /**
     * Actualizar contador de alertas en badge
     */
    async actualizarContador() {
        try {
            const response = await fetch('/alertas/contador');
            const data = await response.json();
            
            if (data.success) {
                // Actualizar badge en el sidebar
                const badges = document.querySelectorAll('.nav-badge');
                badges.forEach(badge => {
                    badge.textContent = data.criticas;
                    badge.style.display = data.criticas > 0 ? 'inline-block' : 'none';
                });
            }
        } catch (error) {
            console.error('Error actualizando contador:', error);
        }
    }

    /**
     * Iniciar sistema
     */
    iniciar() {
        console.log('Iniciando sistema de notificaciones...');
        
        // Verificar alertas inmediatamente
        this.verificarAlertas();
        this.actualizarContador();

        // Verificar periódicamente
        setInterval(() => {
            this.verificarAlertas();
            this.actualizarContador();
        }, this.intervaloVerificacion);

        // Botón de silenciar (agregar a la UI)
        this.crearBotonSilenciar();

        console.log('Sistema de notificaciones iniciado');
        console.log(`Verificación cada ${this.intervaloVerificacion/1000} segundos`);
        console.log(`Re-notificación: ${this.tiempoRenotificacion1/60000} min, luego cada ${this.tiempoRenotificacion2/60000} min`);
    }

    /**
     * Crear botón de silenciar en la UI
     */
    crearBotonSilenciar() {
        // Verificar si ya existe
        if (document.getElementById('btn-silenciar-alertas')) {
            return;
        }

        const boton = document.createElement('button');
        boton.id = 'btn-silenciar-alertas';
        boton.innerHTML = 'Silenciar 30min';
        boton.onclick = () => this.silenciarTemporal();
        
        document.body.appendChild(boton);
    }
}

// ════════════════════════════════════════════════════════════
// INICIALIZACIÓN AUTOMÁTICA
// ════════════════════════════════════════════════════════════

// Iniciar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.sistemaNotificaciones = new SistemaNotificacionesAlertas();
    });
} else {
    window.sistemaNotificaciones = new SistemaNotificacionesAlertas();
}

console.log('Módulo de notificaciones cargado');