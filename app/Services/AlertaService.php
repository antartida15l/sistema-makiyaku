<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\ClimaHistorial;
use App\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Motor de alertas.
 *
 * Reglas que corrigen los problemas anteriores:
 *
 *  1. Una sola escala de intensidad (Alerta::nivelPorPuntaje). Riego, helada,
 *     calor, lluvia y ciclo se miden igual, asi nivel, prioridad y color
 *     nunca se contradicen.
 *  2. Una sola alerta activa por parcela y grupo. Si el riego pasa de
 *     "proximo" a "hoy" se actualiza la misma alerta, ya no se acumulan.
 *  3. Las alertas se coordinan: si hay lluvia que cubre el riego no se pide
 *     regar; si el riego es critico no se sugiere esperar la lluvia.
 *  4. Solo se avisa de nuevo cuando la alerta SUBE de nivel; los cambios
 *     menores actualizan el texto sin volver a sonar.
 *  5. Lo que el usuario descarta no vuelve a aparecer durante un tiempo,
 *     salvo que empeore.
 *  6. Las cantidades de agua salen de RiegoService (misma cifra en alertas,
 *     recomendaciones y registro de riego).
 *  7. Con clima desactualizado (sin internet) no se inventan alertas
 *     climaticas.
 */
class AlertaService
{
    /** Horas que se respeta una alerta descartada por el usuario, por grupo. */
    private const ENFRIAMIENTO_HORAS = [
        Alerta::GRUPO_RIEGO => 8,
        Alerta::GRUPO_HELADA => 6,
        Alerta::GRUPO_CALOR => 6,
        Alerta::GRUPO_LLUVIA => 12,
        Alerta::GRUPO_CICLO => 72,
        Alerta::GRUPO_HIDROGEL => 72,
    ];

    /** Horas despues de las cuales el clima guardado ya no es confiable. */
    private const CLIMA_VENCE_HORAS = 6;

    public function __construct(private RiegoService $riego)
    {
    }

    // =========================================================
    // API PUBLICA
    // =========================================================

    /**
     * Recalcula las alertas de una parcela.
     *
     * @return Alerta[] alertas nuevas o que subieron de nivel
     */
    public function generarAlertasParcela(Parcela $parcela): array
    {
        $resultado = Cache::lock('alertas:parcela:' . $parcela->id, 30)
            ->get(fn () => $this->generar($parcela));

        return $resultado === false ? [] : $resultado;
    }

    /**
     * Recalcula las alertas de todas las parcelas activas del usuario.
     *
     * @return int cantidad de alertas nuevas o que subieron de nivel
     */
    public function generarAlertasUsuario(int $userId): int
    {
        $total = 0;

        Parcela::where('user_id', $userId)
            ->activas()
            ->with(['cultivo', 'ubicacion', 'climaActual'])
            ->get()
            ->each(function (Parcela $p) use (&$total) {
                $total += count($this->generarAlertasParcela($p));
            });

        return $total;
    }

    /**
     * Cuando el agricultor registra un riego se cierran las alertas de riego.
     */
    public function archivarAlertasRiegoPorRiego(Parcela $parcela): void
    {
        $this->archivarGrupo($parcela, Alerta::GRUPO_RIEGO, Alerta::POR_RIEGO);
        $this->archivarGrupo($parcela, Alerta::GRUPO_LLUVIA, Alerta::POR_RIEGO);
    }

    public static function limpiarAlertasExpiradas(): int
    {
        return Alerta::where('archivada', false)
            ->whereNotNull('fecha_expiracion')
            ->where('fecha_expiracion', '<', now())
            ->update([
                'leida' => true,
                'leida_en' => now(),
                'archivada' => true,
                'archivada_en' => now(),
                'archivada_por' => Alerta::POR_SISTEMA,
            ]);
    }

    public static function eliminarHistorialAntiguo(): int
    {
        return Alerta::where('archivada', true)
            ->where('archivada_en', '<', now()->subDays(30))
            ->delete();
    }

    // =========================================================
    // GENERACION
    // =========================================================

    private function generar(Parcela $parcela): array
    {
        $parcela->loadMissing(['cultivo', 'ubicacion']);
        $parcela->load('climaActual');

        $cultivo = $parcela->cultivo;

        if (!$cultivo || $parcela->estado !== 'activa') {
            foreach (array_keys(self::ENFRIAMIENTO_HORAS) as $grupo) {
                $this->archivarGrupo($parcela, $grupo, Alerta::POR_SISTEMA);
            }

            return [];
        }

        $clima = $parcela->climaActual;
        $climaVigente = $clima && $clima->fecha_registro
            && $clima->fecha_registro->gte(now()->subHours(self::CLIMA_VENCE_HORAS));

        $climaUsable = $climaVigente ? $clima : null;

        // ---- 1. Evaluar cada tema por separado ----
        $evRiego = $this->evaluarRiego($parcela, $climaUsable);
        $helada = $climaUsable ? $this->evaluarHelada($parcela, $climaUsable) : null;
        $calor = $climaUsable ? $this->evaluarCalor($parcela, $climaUsable) : null;
        $lluvia = $climaUsable ? $this->evaluarLluvia($parcela, $climaUsable, $evRiego) : null;
        $ciclo = $this->evaluarCiclo($parcela);
        $hidrogel = $this->evaluarHidrogel($parcela);

        // ---- 2. Sincronizar con la base de datos ----
        $cambios = [];

        $this->sincronizar($parcela, Alerta::GRUPO_RIEGO, $evRiego['candidata'], $cambios);
        $this->sincronizar($parcela, Alerta::GRUPO_CICLO, $ciclo, $cambios);
        $this->sincronizar($parcela, Alerta::GRUPO_HIDROGEL, $hidrogel, $cambios);

        // Las alertas climaticas solo se tocan con clima vigente: sin internet
        // se conservan las que ya existian hasta que venzan.
        if ($climaUsable) {
            $this->sincronizar($parcela, Alerta::GRUPO_HELADA, $helada, $cambios);
            $this->sincronizar($parcela, Alerta::GRUPO_CALOR, $calor, $cambios);
            $this->sincronizar($parcela, Alerta::GRUPO_LLUVIA, $lluvia, $cambios);

            // Los indicadores del registro de clima reflejan lo mismo que las alertas.
            $clima->update([
                'alerta_helada' => $helada !== null,
                'alerta_calor_extremo' => $calor !== null,
                'alerta_lluvia_proxima' => $lluvia !== null,
                'necesita_riego_urgente' => ($evRiego['candidata']['puntaje'] ?? 0) >= 65,
            ]);
        }

        return $cambios;
    }

    // =========================================================
    // EVALUADORES (devuelven una "candidata" o null)
    // =========================================================

    /**
     * @return array{candidata: ?array, suprimida_por_lluvia: bool, puntaje: int}
     */
    private function evaluarRiego(Parcela $parcela, ?ClimaHistorial $clima): array
    {
        $cultivo = $parcela->cultivo;
        $dias = $this->riego->diasSinRegar($parcela);

        $vacio = ['candidata' => null, 'suprimida_por_lluvia' => false, 'puntaje' => 0];

        if ($dias === null) {
            return $vacio; // sin dato de ultimo riego: no se inventa una alerta
        }

        $frecuencia = $this->riego->frecuenciaDias($parcela, $clima);
        $criticos = (int) ($cultivo->diasCriticosSinAgua ?: $frecuencia * 2);
        $criticos = max($criticos, $frecuencia + 1);
        $consumo = max(0.5, $this->riego->consumoDiarioPorM2($parcela, $clima));

        // La lluvia reciente cuenta como riego: 6 mm con un consumo de 3 mm/dia = 2 dias menos.
        $lluviaUtil = $clima ? (float) $clima->lluvia_72h_mm * 0.75 : 0.0;
        $diasEfectivos = max(0.0, $dias - $lluviaUtil / $consumo);

        $desglose = [
            'dias_sin_regar' => 0.0,
            'clima' => 0.0,
            'sensibilidad' => 0.0,
            'etapa_consumo' => 0.0,
            'hidrogel' => 0.0,
        ];

        // ---- Base: que tan cerca o pasado esta del riego ideal ----
        $ratio = $diasEfectivos / max(1, $frecuencia);

        if ($ratio < 0.6) {
            $base = 0.0;
        } elseif ($ratio < 0.8) {
            $base = 40 * ($ratio - 0.6) / 0.2;            // 0 a 40
        } elseif ($ratio < 1.0) {
            $base = 40 + 25 * ($ratio - 0.8) / 0.2;       // 40 a 65 (llega el dia)
        } elseif ($diasEfectivos < $criticos) {
            $base = 65 + 20 * ($diasEfectivos - $frecuencia) / max(1, $criticos - $frecuencia); // 65 a 85
        } else {
            $base = 85 + min(10, 5 * ($diasEfectivos - $criticos));                              // 85 a 95
        }

        $desglose['dias_sin_regar'] = round($base, 1);

        // Los ajustes solo suman cuando ya hay algo de necesidad: un cultivo
        // sensible no debe alertar el mismo dia que se rego.
        if ($base > 0) {
            if ($clima) {
                $factor = $this->riego->factorClima($parcela, $clima);
                $ajuste = max(-6, min(8, ($factor - 1) / 0.4 * 8));

                if ((float) $clima->viento_velocidad > 8 && (int) $clima->humedad < 50) {
                    $ajuste += 4;
                }

                $tmax = $clima->temp_max_prevista ?? $clima->temp_maxima;
                if ($tmax !== null && $cultivo->tempCriticaMaxima && (float) $tmax > (float) $cultivo->tempCriticaMaxima) {
                    $ajuste += 6;
                }

                $desglose['clima'] = round($ajuste, 1);
            }

            $desglose['sensibilidad'] = match ($cultivo->sensibilidadSequia) {
                'Muy Alta' => 6,
                'Alta' => 4,
                'Media-Alta' => 3,
                'Media' => 1,
                default => 0,
            };

            if ($cultivo->estaEnEtapaMayorConsumo((int) $parcela->dias_desde_siembra)) {
                $desglose['etapa_consumo'] = 4;
            }

            if ($parcela->tieneHidrogelAplicado && $diasEfectivos < $criticos) {
                $desglose['hidrogel'] = -5;
            }
        }

        $puntaje = (int) round(max(0, min(100, array_sum($desglose))));

        if ($puntaje < 40) {
            return ['candidata' => null, 'suprimida_por_lluvia' => false, 'puntaje' => $puntaje];
        }

        // ---- Coordinacion con la lluvia prevista ----
        $lluviaPrevista = $clima && (int) $clima->probabilidad_lluvia_24h >= 60 && (float) $clima->lluvia_24h >= 5;

        if ($lluviaPrevista && $puntaje < 85) {
            return ['candidata' => null, 'suprimida_por_lluvia' => true, 'puntaje' => $puntaje];
        }

        $nivel = Alerta::nivelPorPuntaje($puntaje);
        $calculo = $this->riego->calcular($parcela, $clima);

        [$tipo, $titulo] = match ($nivel) {
            Alerta::NIVEL_CRITICO => [Alerta::TIPO_RIEGO_URGENTE, 'Riego urgente'],
            Alerta::NIVEL_ALTO => [Alerta::TIPO_RIEGO_HOY, 'Riego recomendado hoy'],
            default => [Alerta::TIPO_RIEGO_PROXIMO, 'Riego en uno o dos dias'],
        };

        $mensaje = $this->mensajeRiego($parcela, $nivel, $dias, $frecuencia, $calculo, $clima, $lluviaPrevista);

        return [
            'candidata' => [
                'grupo' => Alerta::GRUPO_RIEGO,
                'tipo' => $tipo,
                'puntaje' => $puntaje,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'icono' => 'agua',
                'accion_texto' => 'Registrar riego',
                'datos' => [
                    'litros_recomendados' => $calculo['litros_total'],
                    'litros_por_m2' => $calculo['litros_m2'],
                    'baldes' => $calculo['baldes'],
                    'dias_sin_regar' => $dias,
                    'frecuencia_ideal' => $frecuencia,
                    'factor_clima' => $calculo['factor_clima'],
                    'lluvia_aprovechada_mm' => $calculo['lluvia_aprovechada_mm'],
                    'puntaje_desglose' => $desglose,
                ],
                'expira' => null,
            ],
            'suprimida_por_lluvia' => false,
            'puntaje' => $puntaje,
        ];
    }

    private function mensajeRiego(
        Parcela $parcela,
        string $nivel,
        int $dias,
        int $frecuencia,
        array $calculo,
        ?ClimaHistorial $clima,
        bool $lluviaPrevista
    ): string {
        $nombre = $parcela->nombre;
        $cultivo = $parcela->cultivo->nombreCultivo;
        $cantidad = $this->riego->textoCantidad($calculo);
        $diasTxt = $dias === 1 ? '1 dia' : "{$dias} dias";

        $caluroso = $clima && ($clima->temp_max_prevista ?? $clima->temp_maxima ?? 0) >= 28;
        $hora = $caluroso
            ? 'Riega temprano (antes de las 9:00) o despues de las 17:00 para no perder agua por el calor.'
            : 'Lo mejor es regar temprano en la manana.';

        $texto = match ($nivel) {
            Alerta::NIVEL_CRITICO =>
                "{$nombre} ({$cultivo}): lleva {$diasTxt} sin riego y el cultivo esta en riesgo. " .
                "Riega ahora: {$cantidad}. {$hora}",
            Alerta::NIVEL_ALTO =>
                "{$nombre} ({$cultivo}): hoy toca regar (lo normal es cada {$frecuencia} dias, van {$diasTxt}). " .
                "Aplica {$cantidad}. {$hora}",
            default =>
                "{$nombre} ({$cultivo}): el riego sera necesario en uno o dos dias (van {$diasTxt}). " .
                "Ve preparando {$cantidad}.",
        };

        if ($calculo['lluvia_aprovechada_mm'] >= 1) {
            $texto .= ' Ya se descontaron unos ' . $this->riego->num($calculo['lluvia_aprovechada_mm']) . ' mm de lluvia reciente.';
        }

        if ($lluviaPrevista) {
            $texto .= ' Se espera lluvia, pero el cultivo esta muy seco: no esperes a que llueva.';
        }

        return $texto;
    }

    private function evaluarHelada(Parcela $parcela, ClimaHistorial $clima): ?array
    {
        $crit = $parcela->cultivo->tempCriticaMinima;

        if ($crit === null) {
            return null;
        }

        $crit = (float) $crit;
        $tmin = $clima->temp_min_suelo ?? $clima->temp_min_prevista ?? $clima->temp_minima ?? $clima->temperatura;

        if ($tmin === null) {
            return null;
        }

        $tmin = (float) $tmin;
        $margen = $tmin - $crit;

        // Histeresis: una alerta ya activa se mantiene hasta que la minima
        // supere el limite en 3 grados, asi no aparece y desaparece cada hora.
        $activa = $this->alertaActiva($parcela, Alerta::GRUPO_HELADA) !== null;
        $avisoHasta = $activa ? 3.0 : 2.0;

        $puntaje = match (true) {
            $margen <= -2 => 90,
            $margen <= 0 => 75,
            $margen <= $avisoHasta => 50,
            default => null,
        };

        if ($puntaje === null) {
            return null;
        }

        $esHelada = $crit <= 2;
        $titulo = $esHelada ? 'Riesgo de helada' : 'Temperatura baja para el cultivo';
        $cuando = $this->describirMomento($clima->hora_temp_min);
        $minAire = $clima->temp_min_prevista !== null ? (float) $clima->temp_min_prevista : $tmin;

        $mensaje = "Se espera una minima de {$this->n($minAire)} C {$cuando} " .
            "(a nivel del suelo puede sentirse cerca de {$this->n($tmin)} C). " .
            "El limite de {$parcela->cultivo->nombreCultivo} es {$this->n($crit)} C. ";

        $mensaje .= $margen <= 0
            ? 'Antes del anochecer cubre las plantas con paja, plastico o mantas y evita dejar el suelo desnudo. '
            : 'Prepara coberturas por si la temperatura baja mas de lo previsto. ';

        if ($esHelada) {
            $mensaje .= 'Un riego ligero por la tarde ayuda a conservar el calor del suelo durante la noche.';
        }

        return [
            'grupo' => Alerta::GRUPO_HELADA,
            'tipo' => Alerta::TIPO_HELADA,
            'puntaje' => $puntaje,
            'titulo' => $titulo,
            'mensaje' => trim($mensaje),
            'icono' => 'helada',
            'accion_texto' => 'Ver parcela',
            'datos' => [
                'temperatura' => $clima->temperatura,
                'humedad' => $clima->humedad,
                'temp_min_prevista' => $minAire,
                'temp_min_suelo' => $tmin,
                'limite_cultivo' => $crit,
            ],
            'expira' => $clima->hora_temp_min
                ? Carbon::parse($clima->hora_temp_min)->addHours(3)
                : now()->addHours(12),
        ];
    }

    private function evaluarCalor(Parcela $parcela, ClimaHistorial $clima): ?array
    {
        $crit = $parcela->cultivo->tempCriticaMaxima;

        if ($crit === null) {
            return null;
        }

        $crit = (float) $crit;
        $tmax = $clima->temp_max_prevista ?? $clima->temp_maxima ?? $clima->temperatura;

        if ($tmax === null) {
            return null;
        }

        $tmax = (float) $tmax;
        $margen = $tmax - $crit;

        $activa = $this->alertaActiva($parcela, Alerta::GRUPO_CALOR) !== null;
        $avisoDesde = $activa ? -3.0 : -2.0;

        $puntaje = match (true) {
            $margen >= 3 => 85,
            $margen >= 0 => 70,
            $margen >= $avisoDesde => 45,
            default => null,
        };

        if ($puntaje === null) {
            return null;
        }

        $mensaje = "Se esperan hasta {$this->n($tmax)} C en las proximas 24 horas; " .
            "el limite de {$parcela->cultivo->nombreCultivo} es {$this->n($crit)} C. " .
            'Riega temprano o al atardecer, da sombra si puedes y cubre el suelo con paja o rastrojo para que no se seque.';

        return [
            'grupo' => Alerta::GRUPO_CALOR,
            'tipo' => Alerta::TIPO_CALOR_EXTREMO,
            'puntaje' => $puntaje,
            'titulo' => $margen >= 0 ? 'Calor extremo' : 'Calor cerca del limite',
            'mensaje' => $mensaje,
            'icono' => 'calor',
            'accion_texto' => 'Ver parcela',
            'datos' => [
                'temperatura' => $clima->temperatura,
                'humedad' => $clima->humedad,
                'temp_max_prevista' => $tmax,
                'limite_cultivo' => $crit,
            ],
            'expira' => now()->endOfDay()->addHours(2),
        ];
    }

    /**
     * Aviso de lluvia. No se crea si contradice a una alerta de riego critica.
     */
    private function evaluarLluvia(Parcela $parcela, ClimaHistorial $clima, array $evRiego): ?array
    {
        $mm = (float) $clima->lluvia_24h;
        $prob = (int) $clima->probabilidad_lluvia_24h;

        if ($prob < 60 || $mm < 5) {
            return null;
        }

        $fuerte = $mm >= 30;
        $riegoCritico = ($evRiego['candidata']['puntaje'] ?? 0) >= 85;

        // Lluvia suave mientras el riego es critico: se prioriza regar.
        if ($riegoCritico && !$fuerte) {
            return null;
        }

        // Lluvia suave sin que el riego estuviera por tocar: no hace falta avisar.
        if (!$fuerte && !$evRiego['suprimida_por_lluvia']) {
            return null;
        }

        $cuando = $clima->lluvia_inicio
            ? 'desde las ' . Carbon::parse($clima->lluvia_inicio)->format('H:i')
            : 'en las proximas 24 horas';

        if ($fuerte) {
            $mensaje = "Se esperan unos {$this->n($mm)} mm de lluvia {$cuando} (probabilidad {$prob}%). " .
                'No riegues. Revisa que las acequias y el drenaje esten despejados para evitar encharcamiento y arrastre de suelo.';
            $puntaje = 65;
            $titulo = 'Lluvia fuerte prevista';
        } else {
            $ahorro = $this->riego->calcular($parcela, $clima, false)['litros_sin_lluvia'];
            $mensaje = "Se esperan unos {$this->n($mm)} mm de lluvia {$cuando} (probabilidad {$prob}%). " .
                'Puedes posponer el riego de ' . $parcela->nombre . '.' .
                ($ahorro > 0 ? ' Ahorras alrededor de ' . number_format($ahorro, 0, ',', '.') . ' L de agua.' : '');
            $puntaje = 30;
            $titulo = 'Lluvia prevista: pospon el riego';
        }

        return [
            'grupo' => Alerta::GRUPO_LLUVIA,
            'tipo' => Alerta::TIPO_LLUVIA_PROXIMA,
            'puntaje' => $puntaje,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'icono' => 'lluvia',
            'accion_texto' => 'Ver parcela',
            'datos' => [
                'temperatura' => $clima->temperatura,
                'humedad' => $clima->humedad,
                'probabilidad_lluvia' => $prob,
                'lluvia_prevista_mm' => $mm,
            ],
            'expira' => $clima->lluvia_fin
                ? Carbon::parse($clima->lluvia_fin)->addHours(2)
                : now()->addHours(12),
        ];
    }

    private function evaluarCiclo(Parcela $parcela): ?array
    {
        $ciclo = (int) $parcela->cultivo->cicloProductivo;

        if ($ciclo <= 0 || !$parcela->fecha_siembra) {
            return null;
        }

        $diasCultivo = (int) Carbon::parse($parcela->fecha_siembra)->startOfDay()->diffInDays(Carbon::today());
        $progreso = $diasCultivo / $ciclo * 100;
        $cultivo = $parcela->cultivo->nombreCultivo;

        if ($progreso >= 100) {
            return [
                'grupo' => Alerta::GRUPO_CICLO,
                'tipo' => Alerta::TIPO_CICLO_COMPLETADO,
                'puntaje' => 45,
                'titulo' => 'Cultivo listo para cosechar',
                'mensaje' => "{$parcela->nombre}: {$cultivo} ya cumplio su ciclo ({$diasCultivo} dias). Revisa el estado de los frutos o granos y programa la cosecha.",
                'icono' => 'ciclo',
                'accion_texto' => 'Ver parcela',
                'datos' => ['dias_cultivo' => $diasCultivo, 'ciclo_dias' => $ciclo, 'progreso' => round($progreso)],
                'expira' => null,
            ];
        }

        if ($progreso >= 85) {
            $faltan = max(1, $ciclo - $diasCultivo);

            return [
                'grupo' => Alerta::GRUPO_CICLO,
                'tipo' => Alerta::TIPO_FIN_CICLO,
                'puntaje' => 30,
                'titulo' => 'Se acerca la cosecha',
                'mensaje' => "{$parcela->nombre}: {$cultivo} esta al " . round($progreso) . "% de su ciclo; faltan unos {$faltan} dias. Ve organizando la cosecha.",
                'icono' => 'ciclo',
                'accion_texto' => 'Ver parcela',
                'datos' => ['dias_cultivo' => $diasCultivo, 'ciclo_dias' => $ciclo, 'progreso' => round($progreso)],
                'expira' => null,
            ];
        }

        return null;
    }

    private function evaluarHidrogel(Parcela $parcela): ?array
    {
        if (!$parcela->tieneHidrogelAplicado || !$parcela->hidrogel_fecha_aplicacion) {
            return null;
        }

        $dias = (int) Carbon::parse($parcela->hidrogel_fecha_aplicacion)->startOfDay()->diffInDays(Carbon::today());

        if ($dias < 90) {
            return null;
        }

        return [
            'grupo' => Alerta::GRUPO_HIDROGEL,
            'tipo' => Alerta::TIPO_RENOVAR_HIDROGEL,
            'puntaje' => 45,
            'titulo' => 'Renovar hidrogel',
            'mensaje' => "{$parcela->nombre}: el hidrogel se aplico hace {$dias} dias. Con el tiempo pierde capacidad de retener agua; considera renovarlo.",
            'icono' => 'hidrogel',
            'accion_texto' => 'Ver parcela',
            'datos' => ['dias_desde_aplicacion' => $dias],
            'expira' => null,
        ];
    }

    // =========================================================
    // SINCRONIZACION CON LA BASE DE DATOS
    // =========================================================

    /**
     * Deja exactamente una alerta activa para (parcela, grupo) o ninguna.
     *
     * @param  Alerta[]  $cambios  se agregan las nuevas o que subieron de nivel
     */
    private function sincronizar(Parcela $parcela, string $grupo, ?array $candidata, array &$cambios): void
    {
        $activas = Alerta::where('parcela_id', $parcela->id)
            ->where('grupo', $grupo)
            ->where('archivada', false)
            ->orderByDesc('id')
            ->get();

        $existente = $activas->shift();

        // Duplicados heredados: se cierran, queda solo la mas reciente.
        foreach ($activas as $duplicada) {
            $duplicada->archivar(Alerta::POR_SISTEMA);
        }

        // Una alerta vencida se cierra y se evalua como si no existiera.
        if ($existente && $existente->fecha_expiracion && $existente->fecha_expiracion->isPast()) {
            $existente->archivar(Alerta::POR_SISTEMA);
            $existente = null;
        }

        if ($candidata === null) {
            $existente?->archivar(Alerta::POR_SISTEMA);

            return;
        }

        $nivel = Alerta::nivelPorPuntaje($candidata['puntaje']);

        $datos = [
            'user_id' => $parcela->user_id,
            'parcela_id' => $parcela->id,
            'grupo' => $grupo,
            'tipo' => $candidata['tipo'],
            'nivel' => $nivel,
            'prioridad' => Alerta::prioridadPorNivel($nivel),
            'icono' => $candidata['icono'],
            'puntaje_urgencia' => $candidata['puntaje'],
            'titulo' => $candidata['titulo'],
            'mensaje' => $candidata['mensaje'],
            'accion_url' => route('parcelas.show', $parcela->id),
            'accion_texto' => $candidata['accion_texto'],
            'datos_adicionales' => $candidata['datos'],
            'fecha_expiracion' => $candidata['expira'],
            'archivada' => false,
            'archivada_en' => null,
            'archivada_por' => null,
        ];

        if ($existente) {
            $subio = Alerta::rangoNivel($nivel) > Alerta::rangoNivel($existente->nivel);

            if ($subio) {
                // Empeoro: vuelve a ser "nueva" para que se vuelva a avisar.
                $datos += [
                    'leida' => false,
                    'leida_en' => null,
                    'notificacion_enviada' => false,
                    'fecha_notificacion' => null,
                    'actualizada_en' => now(),
                ];
            }

            $existente->update($datos);

            if ($subio) {
                $cambios[] = $existente;
            }

            return;
        }

        // Si el usuario la descarto hace poco, solo vuelve si empeora.
        $horas = self::ENFRIAMIENTO_HORAS[$grupo] ?? 6;
        $descartada = Alerta::where('parcela_id', $parcela->id)
            ->where('grupo', $grupo)
            ->where('archivada_por', Alerta::POR_USUARIO)
            ->where('archivada_en', '>', now()->subHours($horas))
            ->orderByDesc('archivada_en')
            ->first();

        if ($descartada && Alerta::rangoNivel($nivel) <= Alerta::rangoNivel($descartada->nivel)) {
            return;
        }

        $cambios[] = Alerta::create($datos + ['actualizada_en' => now()]);
    }

    private function alertaActiva(Parcela $parcela, string $grupo): ?Alerta
    {
        return Alerta::where('parcela_id', $parcela->id)
            ->where('grupo', $grupo)
            ->activas()
            ->first();
    }

    private function archivarGrupo(Parcela $parcela, string $grupo, string $por): void
    {
        Alerta::where('parcela_id', $parcela->id)
            ->where('grupo', $grupo)
            ->where('archivada', false)
            ->update([
                'leida' => true,
                'leida_en' => now(),
                'archivada' => true,
                'archivada_en' => now(),
                'archivada_por' => $por,
            ]);
    }

    // =========================================================
    // TEXTO
    // =========================================================

    private function n(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 1, ',', ''), '0'), ',');
    }

    private function describirMomento($hora): string
    {
        if (!$hora) {
            return 'en las proximas 24 horas';
        }

        $h = Carbon::parse($hora);
        $dia = match (true) {
            $h->isToday() => 'hoy',
            $h->isTomorrow() => 'manana',
            default => 'el ' . $h->format('d/m'),
        };

        return "{$dia} hacia las " . $h->format('H:i');
    }
}
