<?php

namespace App\Services;

use App\Models\Parcela;
use App\Models\Alerta;
use App\Models\Cultivo;
use App\Models\ClimaHistorial;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AlertaService
{
    private const PESO_DIAS_SIN_REGAR = 30;
    private const PESO_CLIMA = 40;
    private const PESO_PRONOSTICO = 15;
    private const PESO_SENSIBILIDAD = 10;
    private const AJUSTE_HIDROGEL = -5;

    // ============================================
    //  Control de duplicados
    // ============================================
    
    /**
     *  CLAVE DEL FIX: Verificar si existe alerta ACTIVA O ARCHIVADA RECIENTE
     */
    private function existeAlertaReciente(Parcela $parcela, string $tipo, int $horasRecientes = 24): bool
    {
        return Alerta::where('parcela_id', $parcela->id)
            ->where('tipo', $tipo)
            ->where(function($query) use ($horasRecientes) {
                // Alertas activas (no archivadas)
                $query->where('archivada', false)
                    // O alertas archivadas hace menos de X horas
                    ->orWhere(function($q) use ($horasRecientes) {
                        $q->where('archivada', true)
                          ->where('archivada_en', '>', now()->subHours($horasRecientes));
                    });
            })
            ->exists();
    }

    // ============================================
    // MÉTODO PRINCIPAL
    // ============================================
    
    public function generarAlertasParcela(Parcela $parcela): array
    {
        $parcela->load(['cultivo', 'ubicacion', 'climaActual', 'user']);

        if (!$parcela->cultivo) {
            return [];
        }

        $alertasCreadas = [];

        // 1. ALERTAS DE RIEGO
        $alertaRiego = $this->procesarAlertaRiego($parcela);
        if ($alertaRiego) {
            $alertasCreadas[] = $alertaRiego;
        }

        // 2. ALERTAS CLIMÁTICAS
        $alertasClima = $this->procesarAlertasClimaticas($parcela);
        $alertasCreadas = array_merge($alertasCreadas, $alertasClima);

        // 3. ALERTAS DE MANTENIMIENTO
        $alertasMantenimiento = $this->procesarAlertasMantenimiento($parcela);
        $alertasCreadas = array_merge($alertasCreadas, $alertasMantenimiento);

        return array_filter($alertasCreadas);
    }

    public function generarAlertasUsuario(int $userId): int
    {
        $parcelas = Parcela::where('user_id', $userId)
            ->activas()
            ->with(['cultivo', 'ubicacion', 'climaActual'])
            ->get();

        $totalAlertas = 0;

        foreach ($parcelas as $parcela) {
            $alertas = $this->generarAlertasParcela($parcela);
            $totalAlertas += count($alertas);
        }

        return $totalAlertas;
    }

    // ============================================
    // SISTEMA DE PUNTUACIÓN DE RIEGO
    // ============================================

    private function calcularPuntajeUrgencia(Parcela $parcela): array
    {
        $cultivo = $parcela->cultivo;
        $clima = $parcela->climaActual;

        $desglose = [
            'dias_sin_regar' => 0,
            'clima_actual' => 0,
            'pronostico' => 0,
            'sensibilidad' => 0,
            'hidrogel' => 0,
        ];

        if ($parcela->ultimo_riego) {
            $diasSinRegar = Carbon::parse($parcela->ultimo_riego)->diffInDays(Carbon::today());
            $frecuenciaIdeal = ($cultivo->frecuenciaRiegoMin + $cultivo->frecuenciaRiegoMax) / 2;

            if ($diasSinRegar >= $cultivo->diasCriticosSinAgua) {
                $desglose['dias_sin_regar'] = self::PESO_DIAS_SIN_REGAR;
            } elseif ($diasSinRegar >= $frecuenciaIdeal + 2) {
                $desglose['dias_sin_regar'] = self::PESO_DIAS_SIN_REGAR * 0.9;
            } elseif ($diasSinRegar >= $frecuenciaIdeal) {
                $desglose['dias_sin_regar'] = self::PESO_DIAS_SIN_REGAR * 0.7;
            } elseif ($diasSinRegar >= $frecuenciaIdeal - 1) {
                $desglose['dias_sin_regar'] = self::PESO_DIAS_SIN_REGAR * 0.4;
            }
        }

        if ($clima) {
            $puntosClima = 0;

            if ($clima->temperatura > 25 && $clima->humedad < 40) {
                $puntosClima += 20;
            } elseif ($clima->temperatura > 28 && $clima->humedad < 50) {
                $puntosClima += 15;
            } elseif ($clima->humedad < 35) {
                $puntosClima += 10;
            }

            if ($clima->temperatura > $cultivo->tempCriticaMaxima) {
                $puntosClima += 12;
            } elseif ($clima->temperatura < $cultivo->tempCriticaMinima) {
                $puntosClima += 8;
            }

            if ($clima->viento_velocidad > 8 && $clima->humedad < 50) {
                $puntosClima += 10;
            } elseif ($clima->viento_velocidad > 5 && $clima->humedad < 40) {
                $puntosClima += 5;
            }

            $desglose['clima_actual'] = min($puntosClima, self::PESO_CLIMA);
        }

        if ($clima) {
            if ($clima->probabilidad_lluvia_24h > 60) {
                $desglose['pronostico'] = -15;
            } elseif ($clima->probabilidad_lluvia_24h < 20 && $desglose['dias_sin_regar'] > 0) {
                $desglose['pronostico'] = 10;
            } elseif ($clima->probabilidad_lluvia_24h >= 30 && $clima->probabilidad_lluvia_24h <= 60) {
                $desglose['pronostico'] = 0;
            }
        }

        $sensibilidad = $cultivo->sensibilidadSequia ?? 'Media';
        $desglose['sensibilidad'] = match($sensibilidad) {
            'Muy Alta' => 10,
            'Alta' => 7,
            'Media-Alta' => 5,
            'Media' => 3,
            'Baja' => 1,
            default => 0,
        };

        if ($parcela->tieneHidrogelAplicado) {
            $desglose['hidrogel'] = self::AJUSTE_HIDROGEL;
        }

        $total = array_sum($desglose);
        $total = max(0, min(100, $total));

        return [
            'total' => round($total),
            'desglose' => $desglose,
            'dias_sin_regar' => $parcela->ultimo_riego 
                ? Carbon::parse($parcela->ultimo_riego)->diffInDays(Carbon::today())
                : 0,
        ];
    }

    // ============================================
    // PROCESAMIENTO DE ALERTAS DE RIEGO
    // ============================================

    private function procesarAlertaRiego(Parcela $parcela): ?Alerta
    {
        $puntaje = $this->calcularPuntajeUrgencia($parcela);

        if ($puntaje['total'] >= 70) {
            return $this->crearOActualizarAlerta($parcela, [
                'tipo' => Alerta::TIPO_RIEGO_URGENTE,
                'nivel' => Alerta::NIVEL_CRITICO,
                'icono' => '🚨',
                'titulo' => 'RIEGO URGENTE',
                'puntaje' => $puntaje,
            ]);
        } elseif ($puntaje['total'] >= 50) {
            return $this->crearOActualizarAlerta($parcela, [
                'tipo' => Alerta::TIPO_RIEGO_HOY,
                'nivel' => Alerta::NIVEL_ALTO,
                'icono' => '💧',
                'titulo' => 'Riego Recomendado Hoy',
                'puntaje' => $puntaje,
            ]);
        } elseif ($puntaje['total'] >= 30) {
            return $this->crearOActualizarAlerta($parcela, [
                'tipo' => Alerta::TIPO_RIEGO_PROXIMO,
                'nivel' => Alerta::NIVEL_MEDIO,
                'icono' => '🌱',
                'titulo' => 'Riego Próximo',
                'puntaje' => $puntaje,
            ]);
        }

        $this->archivarAlertasTipo($parcela, [
            Alerta::TIPO_RIEGO_URGENTE,
            Alerta::TIPO_RIEGO_HOY,
            Alerta::TIPO_RIEGO_PROXIMO,
        ]);

        return null;
    }

    private function crearOActualizarAlerta(Parcela $parcela, array $config): Alerta
    {
        $puntaje = $config['puntaje'];
        $cultivo = $parcela->cultivo;

        $litrosNecesarios = $cultivo->calcularLitrosNecesarios(
            $parcela->area,
            $puntaje['dias_sin_regar'],
            $parcela->tieneHidrogelAplicado
        );

        $mensaje = $this->construirMensajeRiego($parcela, $config['tipo'], $puntaje, $litrosNecesarios);

        // Buscar alerta ACTIVA
        $alertaExistente = Alerta::where('parcela_id', $parcela->id)
            ->where('tipo', $config['tipo'])
            ->activas()
            ->first();

        $datosAlerta = [
            'user_id' => $parcela->user_id,
            'parcela_id' => $parcela->id,
            'tipo' => $config['tipo'],
            'nivel' => $config['nivel'],
            'icono' => $config['icono'],
            'prioridad' => $this->mapearNivelAPrioridad($config['nivel']),
            'puntaje_urgencia' => $puntaje['total'],
            'titulo' => $config['titulo'],
            'mensaje' => $mensaje,
            'accion_url' => route('parcelas.show', $parcela->id),
            'accion_texto' => 'Registrar Riego',
            'datos_adicionales' => [
                'litros_recomendados' => round($litrosNecesarios, 2),
                'dias_sin_regar' => $puntaje['dias_sin_regar'],
                'puntaje_desglose' => $puntaje['desglose'],
                'frecuencia_ideal' => ($cultivo->frecuenciaRiegoMin + $cultivo->frecuenciaRiegoMax) / 2,
            ],
            'fecha_expiracion' => null,
            'archivada' => false,
            'archivada_en' => null,
        ];

        if ($alertaExistente) {
            $alertaExistente->update($datosAlerta);
            return $alertaExistente;
        }

        return Alerta::create($datosAlerta);
    }

    private function construirMensajeRiego(Parcela $parcela, string $tipo, array $puntaje, float $litros): string
    {
        $cultivo = $parcela->cultivo->nombreCultivo;
        $dias = $puntaje['dias_sin_regar'];

        return match($tipo) {
            Alerta::TIPO_RIEGO_URGENTE => 
                "🚨 Tu cultivo de {$cultivo} lleva {$dias} días sin agua y está en riesgo. " .
                "Necesita riego URGENTE con aproximadamente " . number_format($litros, 0) . " litros.",
            
            Alerta::TIPO_RIEGO_HOY => 
                "💧 Es momento de regar tu parcela '{$parcela->nombre}' ({$cultivo}). " .
                "Cantidad sugerida: " . number_format($litros, 0) . " litros.",
            
            Alerta::TIPO_RIEGO_PROXIMO => 
                "🌱 Tu parcela '{$parcela->nombre}' ({$cultivo}) necesitará riego en los próximos 1-2 días. " .
                "Prepara aproximadamente " . number_format($litros, 0) . " litros.",
            
            default => "Alerta de riego para {$parcela->nombre}",
        };
    }

    // ============================================
    // ALERTAS CLIMÁTICAS 
    // ============================================
    
    private function procesarAlertasClimaticas(Parcela $parcela): array
    {
        $clima = $parcela->climaActual;
        if (!$clima) {
            return [];
        }

        $alertas = [];

        //  HELADA - Solo crear si NO existe una reciente
        if ($clima->alerta_helada || $clima->temperatura < $parcela->cultivo->tempCriticaMinima) {
            if (!$this->existeAlertaReciente($parcela, Alerta::TIPO_HELADA, 12)) {
                $alertas[] = $this->crearAlertaClimatica($parcela, 'helada', $clima);
            }
        } else {
            $this->archivarAlertasTipo($parcela, [Alerta::TIPO_HELADA]);
        }

        // CALOR EXTREMO - Solo crear si NO existe una reciente
        if ($clima->alerta_calor_extremo || $clima->temperatura > $parcela->cultivo->tempCriticaMaxima) {
            if (!$this->existeAlertaReciente($parcela, Alerta::TIPO_CALOR_EXTREMO, 12)) {
                $alertas[] = $this->crearAlertaClimatica($parcela, 'calor', $clima);
            }
        } else {
            $this->archivarAlertasTipo($parcela, [Alerta::TIPO_CALOR_EXTREMO]);
        }

        // LLUVIA PRÓXIMA - Solo crear si NO existe una reciente
        if ($clima->probabilidad_lluvia_24h > 60 && $parcela->necesita_riego) {
            if (!$this->existeAlertaReciente($parcela, Alerta::TIPO_LLUVIA_PROXIMA, 24)) {
                $alertas[] = $this->crearAlertaClimatica($parcela, 'lluvia', $clima);
            }
        } else {
            $this->archivarAlertasTipo($parcela, [Alerta::TIPO_LLUVIA_PROXIMA]);
        }

        return array_filter($alertas);
    }

    private function crearAlertaClimatica(Parcela $parcela, string $tipoClima, ClimaHistorial $clima): ?Alerta
    {
        $config = match($tipoClima) {
            'helada' => [
                'tipo' => Alerta::TIPO_HELADA,
                'nivel' => Alerta::NIVEL_ALTO,
                'icono' => '❄️',
                'titulo' => 'Riesgo de Helada',
                'mensaje' => "⚠️ Temperatura bajará a {$clima->temperatura}°C. " .
                             "Protege tu cultivo de {$parcela->cultivo->nombreCultivo} con coberturas o riego preventivo.",
                'puntaje' => 80,
            ],
            'calor' => [
                'tipo' => Alerta::TIPO_CALOR_EXTREMO,
                'nivel' => Alerta::NIVEL_ALTO,
                'icono' => '🔥',
                'titulo' => 'Calor Extremo',
                'mensaje' => "🌡️ Temperatura de {$clima->temperatura}°C detectada (crítica para {$parcela->cultivo->nombreCultivo}). " .
                             "Considera riego adicional, sombra o mulching.",
                'puntaje' => 75,
            ],
            'lluvia' => [
                'tipo' => Alerta::TIPO_LLUVIA_PROXIMA,
                'nivel' => Alerta::NIVEL_INFO,
                'icono' => '🌧️',
                'titulo' => 'Lluvia Próxima',
                'mensaje' => "☔ Probabilidad de lluvia: {$clima->probabilidad_lluvia_24h}%. " .
                             "Puedes posponer el riego programado para '{$parcela->nombre}'.",
                'puntaje' => 20,
            ],
            default => null,
        };

        if (!$config) {
            return null;
        }

        //  NO buscar existente, solo crear nueva
        $datosAlerta = [
            'user_id' => $parcela->user_id,
            'parcela_id' => $parcela->id,
            'tipo' => $config['tipo'],
            'nivel' => $config['nivel'],
            'icono' => $config['icono'],
            'prioridad' => $this->mapearNivelAPrioridad($config['nivel']),
            'puntaje_urgencia' => $config['puntaje'],
            'titulo' => $config['titulo'],
            'mensaje' => $config['mensaje'],
            'accion_url' => route('parcelas.show', $parcela->id),
            'accion_texto' => 'Ver Detalles',
            'datos_adicionales' => [
                'temperatura' => $clima->temperatura,
                'humedad' => $clima->humedad,
                'probabilidad_lluvia' => $clima->probabilidad_lluvia_24h,
            ],
            'fecha_expiracion' => now()->addHours(24),
            'archivada' => false,
            'archivada_en' => null,
        ];

        return Alerta::create($datosAlerta);
    }

    // ============================================
    // ALERTAS DE MANTENIMIENTO
    // ============================================

    private function procesarAlertasMantenimiento(Parcela $parcela): array
    {
        $alertas = [];

        //  RENOVAR HIDROGEL
        if ($parcela->tieneHidrogelAplicado && $parcela->hidrogel_fecha_aplicacion) {
            $diasDesdeAplicacion = Carbon::parse($parcela->hidrogel_fecha_aplicacion)->diffInDays(Carbon::today());
            
            if ($diasDesdeAplicacion >= 90) {
                if (!$this->existeAlertaReciente($parcela, Alerta::TIPO_RENOVAR_HIDROGEL, 168)) { // 7 días
                    $alertas[] = $this->crearAlertaMantenimiento($parcela, 'hidrogel', $diasDesdeAplicacion);
                }
            } else {
                $this->archivarAlertasTipo($parcela, [Alerta::TIPO_RENOVAR_HIDROGEL]);
            }
        } else {
            $this->archivarAlertasTipo($parcela, [Alerta::TIPO_RENOVAR_HIDROGEL]);
        }

        // FIN DE CICLO
        if ($parcela->cultivo->cicloCultivoDias && $parcela->dias_desde_siembra > 0) {
            $progreso = ($parcela->dias_desde_siembra / $parcela->cultivo->cicloCultivoDias) * 100;
            
            if ($progreso >= 95) {
                if (!$this->existeAlertaReciente($parcela, Alerta::TIPO_CICLO_COMPLETADO, 168)) {
                    $alertas[] = $this->crearAlertaMantenimiento($parcela, 'ciclo_completo', $progreso);
                }
            } elseif ($progreso >= 85) {
                if (!$this->existeAlertaReciente($parcela, Alerta::TIPO_FIN_CICLO, 168)) {
                    $alertas[] = $this->crearAlertaMantenimiento($parcela, 'fin_ciclo', $progreso);
                }
            } else {
                $this->archivarAlertasTipo($parcela, [Alerta::TIPO_FIN_CICLO, Alerta::TIPO_CICLO_COMPLETADO]);
            }
        } else {
            $this->archivarAlertasTipo($parcela, [Alerta::TIPO_FIN_CICLO, Alerta::TIPO_CICLO_COMPLETADO]);
        }

        return array_filter($alertas);
    }

    private function crearAlertaMantenimiento(Parcela $parcela, string $tipoMantenimiento, $valor): ?Alerta
    {
        $config = match($tipoMantenimiento) {
            'hidrogel' => [
                'tipo' => Alerta::TIPO_RENOVAR_HIDROGEL,
                'nivel' => Alerta::NIVEL_MEDIO,
                'icono' => '🧪',
                'titulo' => 'Renovar Hidrogel',
                'mensaje' => "Han pasado {$valor} días desde la aplicación de hidrogel en '{$parcela->nombre}'. " .
                             "Considera renovar para mantener la eficiencia de retención de agua.",
                'puntaje' => 40,
            ],
            'ciclo_completo' => [
                'tipo' => Alerta::TIPO_CICLO_COMPLETADO,
                'nivel' => Alerta::NIVEL_MEDIO,
                'icono' => '🌾',
                'titulo' => 'Cultivo Listo para Cosechar',
                'mensaje' => "🎉 Tu cultivo de {$parcela->cultivo->nombreCultivo} ha completado su ciclo productivo. " .
                             "¡Es momento de cosechar!",
                'puntaje' => 50,
            ],
            'fin_ciclo' => [
                'tipo' => Alerta::TIPO_FIN_CICLO,
                'nivel' => Alerta::NIVEL_INFO,
                'icono' => '📅',
                'titulo' => 'Fin de Ciclo Próximo',
                'mensaje' => "Tu cultivo de {$parcela->cultivo->nombreCultivo} está al " . round($valor) . "% de su ciclo. " .
                             "Prepárate para la cosecha en los próximos días.",
                'puntaje' => 30,
            ],
            default => null,
        };

        if (!$config) {
            return null;
        }

        $datosAlerta = [
            'user_id' => $parcela->user_id,
            'parcela_id' => $parcela->id,
            'tipo' => $config['tipo'],
            'nivel' => $config['nivel'],
            'icono' => $config['icono'],
            'prioridad' => $this->mapearNivelAPrioridad($config['nivel']),
            'puntaje_urgencia' => $config['puntaje'],
            'titulo' => $config['titulo'],
            'mensaje' => $config['mensaje'],
            'accion_url' => route('parcelas.show', $parcela->id),
            'accion_texto' => 'Ver Parcela',
            'datos_adicionales' => [
                'valor' => $valor,
            ],
            'archivada' => false,
            'archivada_en' => null,
        ];

        return Alerta::create($datosAlerta);
    }

    // ============================================
    // MÉTODOS AUXILIARES
    // ============================================
    
    private function mapearNivelAPrioridad(string $nivel): string
    {
        return match($nivel) {
            'alto' => 'alta',
            'medio' => 'media',
            'info' => 'baja',
            'critico' => 'critica',
            default => 'media',
        };
    }

    private function archivarAlertasTipo(Parcela $parcela, array $tipos): void
    {
        Alerta::where('parcela_id', $parcela->id)
            ->whereIn('tipo', $tipos)
            ->activas()
            ->update([
                'leida' => true,
                'leida_en' => now(),
                'archivada' => true,
                'archivada_en' => now(),
            ]);
    }

    public function archivarAlertasRiegoPorRiego(Parcela $parcela): void
    {
        Alerta::where('parcela_id', $parcela->id)
            ->whereIn('tipo', [
                Alerta::TIPO_RIEGO_URGENTE,
                Alerta::TIPO_RIEGO_HOY,
                Alerta::TIPO_RIEGO_PROXIMO,
            ])
            ->activas()
            ->update([
                'leida' => true,
                'leida_en' => now(),
                'archivada' => true,
                'archivada_en' => now(),
            ]);
    }

    public static function limpiarAlertasExpiradas(): int
    {
        return Alerta::where('fecha_expiracion', '<', now())
            ->activas()
            ->update([
                'leida' => true,
                'leida_en' => now(),
                'archivada' => true,
                'archivada_en' => now(),
            ]);
    }

    public static function eliminarHistorialAntiguo(): int
    {
        return Alerta::where('archivada', true)
            ->where('archivada_en', '<', now()->subDays(30))
            ->delete();
    }
}