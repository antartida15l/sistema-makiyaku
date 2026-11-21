<?php

namespace App\Services;

use App\Models\Parcela;
use App\Models\Recomendacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecomendacionService
{
    /**
     * Genera todas las recomendaciones para una parcela
     */
    public function generarRecomendacionesParcela(Parcela $parcela): int
    {
        try {
            // Cargar relaciones necesarias
            $parcela->load(['cultivo', 'ubicacion', 'climaActual']);

            $recomendaciones = [];

            // 1. Recomendaciones de riego
            $recomendaciones = array_merge($recomendaciones, $this->generarRecomendacionesRiego($parcela));

            // 2. Recomendaciones climáticas
            $recomendaciones = array_merge($recomendaciones, $this->generarRecomendacionesClima($parcela));

            // 3. Recomendaciones de ciclo de cultivo
            $recomendaciones = array_merge($recomendaciones, $this->generarRecomendacionesCiclo($parcela));

            // 4. Recomendaciones de hidrogel
            $recomendaciones = array_merge($recomendaciones, $this->generarRecomendacionesHidrogel($parcela));

            // 5. Recomendaciones de fertilización
            $recomendaciones = array_merge($recomendaciones, $this->generarRecomendacionesFertilizacion($parcela));

            // Guardar todas
            $creadas = 0;
            foreach ($recomendaciones as $recomendacion) {
                if ($this->debeCrearRecomendacion($parcela, $recomendacion)) {
                    Recomendacion::create($recomendacion);
                    $creadas++;
                }
            }

            return $creadas;
        } catch (\Exception $e) {
            Log::error("Error generando recomendaciones para parcela {$parcela->id}: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return 0;
        }
    }

    /**
     * RECOMENDACIONES DE RIEGO
     */
    private function generarRecomendacionesRiego(Parcela $parcela): array
    {
        $recomendaciones = [];
        $cultivo = $parcela->cultivo;
        $clima = $parcela->climaActual;

        if (!$cultivo) {
            Log::warning("Parcela {$parcela->id} sin cultivo asignado");
            return $recomendaciones;
        }

        // Calcular días sin regar
        $diasSinRegar = $parcela->ultimo_riego 
            ? Carbon::parse($parcela->ultimo_riego)->diffInDays(Carbon::today())
            : 999;

        // RIEGO CRÍTICO
        if ($diasSinRegar >= ($cultivo->diasCriticosSinAgua ?? 7)) {
            $litrosNecesarios = method_exists($cultivo, 'calcularLitrosNecesarios')
                ? $cultivo->calcularLitrosNecesarios(
                    $parcela->area ?? 100,
                    $diasSinRegar,
                    $parcela->tieneHidrogelAplicado ?? false
                )
                : ($parcela->area ?? 100) * 5; // Default: 5L por m²

            $recomendaciones[] = [
                'parcela_id' => $parcela->id,
                'user_id' => $parcela->user_id,
                'categoria' => Recomendacion::CATEGORIA_RIEGO,
                'prioridad' => Recomendacion::PRIORIDAD_CRITICA,
                'titulo' => '🚨 Riego Crítico Urgente',
                'descripcion' => "Tu cultivo de {$cultivo->nombreCultivo} lleva {$diasSinRegar} días sin agua. " .
                    "Ha superado el límite crítico. " .
                    "Es necesario regar INMEDIATAMENTE con aproximadamente " . number_format($litrosNecesarios, 0) . " litros. " .
                    "El estrés hídrico puede causar daños irreversibles.",
                'icono' => '🚨',
                'accion_texto' => 'Registrar Riego Ahora',
                'accion_url' => route('parcelas.show', $parcela),
                'puntaje_relevancia' => 100,
                'datos_analisis' => [
                    'dias_sin_regar' => $diasSinRegar,
                    'litros_necesarios' => $litrosNecesarios,
                ],
                'vigente_hasta' => now()->addHours(12),
                'activa' => true,
                'tipo_generacion' => 'automatica',
                'fuente' => 'sistema_riego',
            ];
        }

        // OPTIMIZACIÓN POR CLIMA
        if ($clima && isset($clima->humedad) && isset($clima->temperatura)) {
            if ($clima->humedad < 40 && $clima->temperatura > 28) {
                $recomendaciones[] = [
                    'parcela_id' => $parcela->id,
                    'user_id' => $parcela->user_id,
                    'categoria' => Recomendacion::CATEGORIA_RIEGO,
                    'prioridad' => Recomendacion::PRIORIDAD_ALTA,
                    'titulo' => '🌡️ Ajustar Riego por Condiciones Secas',
                    'descripcion' => "Las condiciones actuales (temperatura {$clima->temperatura}°C y humedad {$clima->humedad}%) " .
                        "aumentan la evapotranspiración. Considera aumentar la frecuencia de riego en un 20-30% " .
                        "o regar en las horas más frescas (temprano en la mañana o al atardecer).",
                    'icono' => '🌡️',
                    'accion_texto' => 'Ver Clima Actual',
                    'accion_url' => route('parcelas.show', $parcela),
                    'puntaje_relevancia' => 75,
                    'datos_analisis' => [
                        'temperatura' => $clima->temperatura,
                        'humedad' => $clima->humedad,
                        'condicion' => 'seco_caluroso',
                    ],
                    'vigente_hasta' => now()->addDays(2),
                    'activa' => true,
                    'tipo_generacion' => 'automatica',
                    'fuente' => 'analisis_clima',
                ];
            }
        }

        return $recomendaciones;
    }

    /**
     * RECOMENDACIONES CLIMÁTICAS
     */
    private function generarRecomendacionesClima(Parcela $parcela): array
    {
        $recomendaciones = [];
        $clima = $parcela->climaActual;
        $cultivo = $parcela->cultivo;

        if (!$clima || !$cultivo) return $recomendaciones;

        // PROTECCIÓN CONTRA HELADAS
        if (isset($clima->temperatura) && isset($cultivo->tempCriticaMinima)) {
            if ($clima->temperatura <= $cultivo->tempCriticaMinima) {
                $recomendaciones[] = [
                    'parcela_id' => $parcela->id,
                    'user_id' => $parcela->user_id,
                    'categoria' => Recomendacion::CATEGORIA_CLIMA,
                    'prioridad' => Recomendacion::PRIORIDAD_CRITICA,
                    'titulo' => '❄️ Protección contra Helada',
                    'descripcion' => "La temperatura está en {$clima->temperatura}°C, cerca o por debajo del límite crítico " .
                        "para {$cultivo->nombreCultivo} ({$cultivo->tempCriticaMinima}°C). " .
                        "ACCIONES URGENTES: 1) Regar antes del amanecer. " .
                        "2) Cubrir plantas con mantas térmicas. 3) Considerar calentadores si es posible.",
                    'icono' => '❄️',
                    'puntaje_relevancia' => 95,
                    'datos_analisis' => [
                        'temperatura_actual' => $clima->temperatura,
                        'temperatura_critica' => $cultivo->tempCriticaMinima,
                    ],
                    'vigente_hasta' => now()->addHours(24),
                    'activa' => true,
                    'tipo_generacion' => 'automatica',
                    'fuente' => 'alerta_clima',
                ];
            }
        }

        // PROTECCIÓN CONTRA CALOR EXTREMO
        if (isset($clima->temperatura) && isset($cultivo->tempCriticaMaxima)) {
            if ($clima->temperatura >= $cultivo->tempCriticaMaxima) {
                $recomendaciones[] = [
                    'parcela_id' => $parcela->id,
                    'user_id' => $parcela->user_id,
                    'categoria' => Recomendacion::CATEGORIA_CLIMA,
                    'prioridad' => Recomendacion::PRIORIDAD_ALTA,
                    'titulo' => '🔥 Protección contra Calor Extremo',
                    'descripcion' => "Temperatura de {$clima->temperatura}°C detectada (crítica: {$cultivo->tempCriticaMaxima}°C). " .
                        "ACCIONES: 1) Regar al amanecer y atardecer. " .
                        "2) Aplicar mulch orgánico. 3) Considerar malla sombra (30-50%).",
                    'icono' => '🔥',
                    'puntaje_relevancia' => 90,
                    'datos_analisis' => [
                        'temperatura_actual' => $clima->temperatura,
                        'temperatura_critica_max' => $cultivo->tempCriticaMaxima,
                    ],
                    'vigente_hasta' => now()->addHours(36),
                    'activa' => true,
                    'tipo_generacion' => 'automatica',
                    'fuente' => 'alerta_clima',
                ];
            }
        }

        return $recomendaciones;
    }

    /**
     * RECOMENDACIONES DE CICLO DE CULTIVO
     */
    private function generarRecomendacionesCiclo(Parcela $parcela): array
    {
        $recomendaciones = [];
        $cultivo = $parcela->cultivo;

        if (!$cultivo || !$parcela->fecha_siembra) return $recomendaciones;

        $progreso = $parcela->progreso_siembra ?? 0;

        // PRÓXIMA A COSECHAR
        if ($progreso >= 90 && $progreso < 100) {
            $diasFaltantes = ($cultivo->cicloCultivoDias ?? 120) - ($parcela->dias_desde_siembra ?? 0);
            
            $recomendaciones[] = [
                'parcela_id' => $parcela->id,
                'user_id' => $parcela->user_id,
                'categoria' => Recomendacion::CATEGORIA_COSECHA,
                'prioridad' => Recomendacion::PRIORIDAD_ALTA,
                'titulo' => '🌾 Preparar para Cosecha',
                'descripcion' => "Tu cultivo de {$cultivo->nombreCultivo} está al {$progreso}% de su ciclo. " .
                    "Faltan aproximadamente {$diasFaltantes} días para la cosecha. " .
                    "PREPARATIVOS: 1) Reducir riego gradualmente. 2) No fertilizar en esta etapa. " .
                    "3) Inspeccionar signos de madurez.",
                'icono' => '🌾',
                'accion_texto' => 'Ver Detalles del Cultivo',
                'accion_url' => route('parcelas.show', $parcela),
                'puntaje_relevancia' => 85,
                'datos_analisis' => [
                    'progreso' => $progreso,
                    'dias_faltantes' => $diasFaltantes,
                ],
                'vigente_hasta' => Carbon::parse($parcela->fecha_siembra)->addDays($cultivo->cicloCultivoDias ?? 120),
                'activa' => true,
                'tipo_generacion' => 'automatica',
                'fuente' => 'analisis_ciclo',
            ];
        }

        return $recomendaciones;
    }

    /**
     * RECOMENDACIONES DE HIDROGEL
     */
    private function generarRecomendacionesHidrogel(Parcela $parcela): array
    {
        $recomendaciones = [];

        // APLICAR HIDROGEL SI NO TIENE
        if (!($parcela->tieneHidrogelAplicado ?? false) && $parcela->cultivo) {
            $recomendaciones[] = [
                'parcela_id' => $parcela->id,
                'user_id' => $parcela->user_id,
                'categoria' => Recomendacion::CATEGORIA_HIDROGEL,
                'prioridad' => Recomendacion::PRIORIDAD_MEDIA,
                'titulo' => '🧪 Considera Aplicar Hidrogel',
                'descripcion' => "El hidrogel puede reducir el consumo de agua hasta un 25% " .
                    "para tu cultivo de {$parcela->cultivo->nombreCultivo}. " .
                    "BENEFICIOS: 1) Retiene agua en el suelo. 2) Reduce frecuencia de riego. " .
                    "3) Mejora resistencia a sequía. Dosis recomendada: 2-5 kg por cada 100 m².",
                'icono' => '🧪',
                'puntaje_relevancia' => 60,
                'datos_analisis' => [
                    'area_parcela' => $parcela->area ?? 0,
                ],
                'activa' => true,
                'tipo_generacion' => 'automatica',
                'fuente' => 'optimizacion_recursos',
            ];
        }

        return $recomendaciones;
    }

    /**
     * RECOMENDACIONES DE FERTILIZACIÓN
     */
    private function generarRecomendacionesFertilizacion(Parcela $parcela): array
    {
        $recomendaciones = [];
        $cultivo = $parcela->cultivo;

        if (!$cultivo || !$parcela->fecha_siembra) return $recomendaciones;

        $diasDesdeSiembra = $parcela->dias_desde_siembra ?? 0;

        // FERTILIZACIÓN EN ETAPA VEGETATIVA (15-45 días)
        if ($diasDesdeSiembra >= 15 && $diasDesdeSiembra <= 45) {
            $recomendaciones[] = [
                'parcela_id' => $parcela->id,
                'user_id' => $parcela->user_id,
                'categoria' => Recomendacion::CATEGORIA_FERTILIZACION,
                'prioridad' => Recomendacion::PRIORIDAD_MEDIA,
                'titulo' => '🌱 Fertilización en Etapa Vegetativa',
                'descripcion' => "Tu {$cultivo->nombreCultivo} está en etapa vegetativa (día {$diasDesdeSiembra}). " .
                    "Es momento de aplicar fertilizante rico en NITRÓGENO (N) para promover crecimiento. " .
                    "Fórmula recomendada: NPK 20-10-10 o similar. Aplicar cada 15-20 días.",
                'icono' => '🌱',
                'puntaje_relevancia' => 70,
                'datos_analisis' => [
                    'etapa' => 'vegetativa',
                    'dias_desde_siembra' => $diasDesdeSiembra,
                    'nutriente_principal' => 'Nitrógeno',
                ],
                'vigente_hasta' => Carbon::parse($parcela->fecha_siembra)->addDays(45),
                'activa' => true,
                'tipo_generacion' => 'automatica',
                'fuente' => 'plan_nutricional',
            ];
        }

        return $recomendaciones;
    }

    /**
     * Verifica si debe crear la recomendación (evitar duplicados)
     */
    private function debeCrearRecomendacion(Parcela $parcela, array $datos): bool
    {
        // Buscar si existe una recomendación similar activa
        $existe = Recomendacion::where('parcela_id', $parcela->id)
            ->where('categoria', $datos['categoria'])
            ->where('titulo', $datos['titulo'])
            ->activas()
            ->exists();

        return !$existe;
    }
}