<?php

namespace App\Services;

use App\Models\ClimaHistorial;
use App\Models\Parcela;
use Carbon\Carbon;

/**
 * Unica fuente de verdad para frecuencia y cantidad de agua.
 *
 * Antes existian tres formulas distintas (Cultivo, Parcela y ParcelaController)
 * que daban litros diferentes y algunas usaban campos que no existen en la
 * tabla de cultivos. Ahora alertas, recomendaciones y registro de riego usan
 * este servicio y siempre muestran los mismos numeros.
 *
 * Idea del calculo (1 mm de agua sobre 1 m2 = 1 litro):
 *   litros/m2 = consumo del cultivo por día x días a cubrir - lluvia aprovechable
 *   ajustado por la evapotranspiracion real (ET0) de la zona y limitado a un
 *   maximo razonable para no recomendar volumenes absurdos.
 */
class RiegoService
{
    /** Capacidad de un balde comun en el campo. */
    public const LITROS_BALDE = 20;

    /** ET0 tipica (mm/día) por region; sirve de referencia para comparar. */
    private const ET0_REFERENCIA = [
        'Costa Norte' => 4.5,
        'Costa Central y Sur' => 3.5,
        'Sierra' => 4.0,
        'Selva' => 3.5,
    ];

    private const ET0_REFERENCIA_DEFECTO = 4.0;

    /** Fraccion de la lluvia que realmente queda en el suelo. */
    private const EFICIENCIA_LLUVIA = 0.75;

    /** Consumo por m2/día si el cultivo no lo tiene cargado. */
    private const CONSUMO_DEFECTO = 3.0;

    // =========================================================
    // FACTOR CLIMATICO
    // =========================================================

    /**
     * Cuanto mas seco/caluroso esta el ambiente que lo normal de la zona.
     * 1.0 = normal. Se limita entre 0.7 y 1.4 para evitar saltos bruscos.
     */
    public function factorClima(Parcela $parcela, ?ClimaHistorial $clima): float
    {
        $et0 = $clima?->et0_mm;

        if ($et0 === null || $et0 <= 0) {
            return 1.0;
        }

        // La region natural sale de la altitud de la parcela; si no se sabe, de la region del cultivo.
        $region = match (true) {
            $parcela->altitud !== null && $parcela->altitud >= 2300 => 'Sierra',
            $parcela->altitud !== null && $parcela->altitud <= 800 => 'Costa Central y Sur',
            $parcela->altitud !== null => 'Selva',
            default => $parcela->cultivo?->region,
        };
        $ref = self::ET0_REFERENCIA[$region] ?? self::ET0_REFERENCIA_DEFECTO;

        return round(max(0.7, min(1.4, $et0 / $ref)), 2);
    }

    /**
     * Litros por m2 que consume el cultivo cada día, ya ajustado al clima.
     */
    public function consumoDiarioPorM2(Parcela $parcela, ?ClimaHistorial $clima = null): float
    {
        $base = (float) ($parcela->cultivo?->litrosPorM2Dia ?: self::CONSUMO_DEFECTO);

        return round($base * $this->factorClima($parcela, $clima), 2);
    }

    // =========================================================
    // FRECUENCIA
    // =========================================================

    /**
     * Cada cuantos días conviene regar.
     */
    public function frecuenciaDias(Parcela $parcela, ?ClimaHistorial $clima = null): int
    {
        $cultivo = $parcela->cultivo;

        if (!$cultivo || !$cultivo->frecuenciaRiegoMin) {
            return max(1, (int) ($parcela->frecuencia_riego_dias ?: 7));
        }

        $humedad = $clima?->humedad ?? 50;
        $días = $cultivo->calcularFrecuenciaRiego((bool) $parcela->tieneHidrogelAplicado, (float) $humedad);

        // Si la zona está muy seca por ET0, se adelanta un día (sin bajar del minimo del cultivo).
        $factor = $this->factorClima($parcela, $clima);
        if ($factor >= 1.25) {
            $días = max((int) $cultivo->frecuenciaRiegoMin, $días - 1);
        } elseif ($factor <= 0.8) {
            $días = min((int) $cultivo->frecuenciaRiegoMax + ($parcela->tieneHidrogelAplicado ? 3 : 0), $días + 1);
        }

        return max(1, $días);
    }

    // =========================================================
    // CANTIDAD DE AGUA
    // =========================================================

    /**
     * Calcula cuanta agua aplicar ahora.
     *
     * @param  bool  $descontarPronostico  restar la lluvia que se espera en 24 h
     * @return array{
     *   litros_total: float, litros_m2: float, baldes: float, dias_cubiertos: float,
     *   consumo_dia_m2: float, factor_clima: float, lluvia_aprovechada_mm: float,
     *   litros_sin_lluvia: float, area: float
     * }
     */
    public function calcular(Parcela $parcela, ?ClimaHistorial $clima = null, bool $descontarPronostico = true): array
    {
        $cultivo = $parcela->cultivo;
        $area = (float) $parcela->area;
        $frecuencia = $this->frecuenciaDias($parcela, $clima);
        $consumo = $this->consumoDiarioPorM2($parcela, $clima);

        if (!$cultivo || $area <= 0) {
            return $this->vacio($area);
        }

        // Dias de consumo que hay que reponer: los que pasaron desde el ultimo
        // riego, pero nunca mas de la frecuencia + 2 (el suelo no guarda mas).
        $diasSinRegar = $this->diasSinRegar($parcela);
        $maxDias = $frecuencia + 2;
        $días = $diasSinRegar === null ? $frecuencia : max(1, min($diasSinRegar, $maxDias));

        $bruto = $consumo * $días;

        // Lluvia que ya mojo el suelo (ultimos 3 días) y la prevista para 24 h.
        $lluvia = 0.0;
        if ($clima) {
            $lluvia += (float) $clima->lluvia_72h_mm * self::EFICIENCIA_LLUVIA;

            if ($descontarPronostico && (int) $clima->probabilidad_lluvia_24h >= 60) {
                $lluvia += (float) $clima->lluvia_24h * self::EFICIENCIA_LLUVIA;
            }
        }

        $reduccionHidrogel = $parcela->tieneHidrogelAplicado
            ? min(0.40, max(0, (float) $cultivo->reduccionConHidrogel) / 100)
            : 0.0;

        $factorHidrogel = 1 - $reduccionHidrogel;

        $sinLluvia = $bruto * $factorHidrogel;
        $neto = max(0.0, $bruto - $lluvia) * $factorHidrogel;

        // Tope: no mas que lo que consume el cultivo en (frecuencia + 2) días.
        $tope = $consumo * $maxDias * $factorHidrogel;
        $neto = min($neto, $tope);
        $sinLluvia = min($sinLluvia, $tope);

        return [
            'litros_total' => round($neto * $area, 0),
            'litros_m2' => round($neto, 1),
            'baldes' => $this->aBaldes($neto * $area),
            'dias_cubiertos' => (float) $días,
            'consumo_dia_m2' => $consumo,
            'factor_clima' => $this->factorClima($parcela, $clima),
            'lluvia_aprovechada_mm' => round($lluvia, 1),
            'litros_sin_lluvia' => round($sinLluvia * $area, 0),
            'area' => $area,
        ];
    }

    /**
     * Litros para un riego normal (se guarda en parcelas.litros_por_riego).
     */
    public function litrosPorRiego(Parcela $parcela, ?ClimaHistorial $clima = null): float
    {
        $cultivo = $parcela->cultivo;
        $area = (float) $parcela->area;

        if (!$cultivo || $area <= 0) {
            return 0.0;
        }

        $frecuencia = $this->frecuenciaDias($parcela, $clima);
        $consumo = $this->consumoDiarioPorM2($parcela, $clima);
        $hidrogel = $parcela->tieneHidrogelAplicado
            ? 1 - min(0.40, max(0, (float) $cultivo->reduccionConHidrogel) / 100)
            : 1.0;

        return round($consumo * $frecuencia * $hidrogel * $area, 0);
    }

    // =========================================================
    // AUXILIARES
    // =========================================================

    /**
     * Dias completos desde el ultimo riego (null si nunca se registro).
     */
    public function diasSinRegar(Parcela $parcela): ?int
    {
        if (!$parcela->ultimo_riego) {
            return null;
        }

        return (int) max(0, Carbon::parse($parcela->ultimo_riego)->startOfDay()->diffInDays(Carbon::today()));
    }

    /**
     * Baldes de 20 L redondeados a medio balde.
     */
    public function aBaldes(float $litros): float
    {
        return round($litros / self::LITROS_BALDE * 2) / 2;
    }

    /**
     * Texto corto de cantidad para mensajes.
     * Menos de 1.000 L: "180 L en total (9 L por m², unos 9 baldes de 20 L)".
     * Desde 1.000 L: "14,6 m3 en total (36,5 L por m²)", porque contar cientos
     * de baldes no sirve en una parcela grande.
     */
    public function textoCantidad(array $calculo): string
    {
        $m2 = $this->num($calculo['litros_m2']);
        $total = (float) $calculo['litros_total'];

        if ($total >= 1000) {
            return $this->num($total / 1000) . " m³ ({$this->num($total, 0)} L) en total ({$m2} L por m²)";
        }

        $l = number_format($total, 0, ',', '.');
        $b = $calculo['baldes'];
        $baldes = floor($b) == $b ? (string) (int) $b : $this->num($b);
        $unidad = $b == 1 ? 'balde' : 'baldes';

        return "{$l} L en total ({$m2} L por m², unos {$baldes} {$unidad} de " . self::LITROS_BALDE . ' L)';
    }

    /**
     * Numero al estilo del pais: coma decimal, sin ceros sobrantes.
     */
    public function num(float $valor, int $decimales = 1): string
    {
        $t = number_format($valor, $decimales, ',', '.');

        return $decimales > 0 && str_contains($t, ',') ? rtrim(rtrim($t, '0'), ',') : $t;
    }

    private function vacio(float $area): array
    {
        return [
            'litros_total' => 0.0,
            'litros_m2' => 0.0,
            'baldes' => 0.0,
            'dias_cubiertos' => 0.0,
            'consumo_dia_m2' => 0.0,
            'factor_clima' => 1.0,
            'lluvia_aprovechada_mm' => 0.0,
            'litros_sin_lluvia' => 0.0,
            'area' => $area,
        ];
    }
}
