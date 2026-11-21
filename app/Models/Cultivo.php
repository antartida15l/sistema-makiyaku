<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cultivo extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'cultivos';

    /**
     * Clave primaria de la tabla.
     */
    protected $primaryKey = 'idCultivo';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'nombreCultivo',
        'nombreCientifico',
        'tipo',
        'region',
        // Datos de riego
        'frecuenciaRiegoMin',
        'frecuenciaRiegoMax',
        'litrosPorM2Dia',
        'humedadOptimaMin',
        'humedadOptimaMax',
        'sensibilidadSequia',
        'diasCriticosSinAgua',
        'etapaMayorConsumo',
        'diasEtapaConsumoMin',
        'diasEtapaConsumoMax',
        'reduccionConHidrogel',
        // Datos de clima crítico
        'tempCriticaMinima',
        'tempCriticaMaxima',
        'sensibilidadHeladas',
        'efectoTempMinima',
        'efectoTempMaxima',
        // Datos generales
        'cicloProductivo',
        'descripcion',
        'observaciones',
    ];

    /**
     * Atributos que deben ser casteados a tipos nativos.
     */
    protected $casts = [
        'frecuenciaRiegoMin' => 'integer',
        'frecuenciaRiegoMax' => 'integer',
        'litrosPorM2Dia' => 'decimal:2',
        'humedadOptimaMin' => 'decimal:2',
        'humedadOptimaMax' => 'decimal:2',
        'diasCriticosSinAgua' => 'integer',
        'diasEtapaConsumoMin' => 'integer',
        'diasEtapaConsumoMax' => 'integer',
        'reduccionConHidrogel' => 'decimal:2',
        'tempCriticaMinima' => 'decimal:2',
        'tempCriticaMaxima' => 'decimal:2',
        'cicloProductivo' => 'integer',
    ];

    /**
     * Tipos de cultivos disponibles.
     */
    public const TIPOS = [
        'Hortalizas' => 'Hortalizas',
        'Cereales' => 'Cereales',
        'Frutales' => 'Frutales',
        'Legumbres' => 'Legumbres',
        'Tubérculos' => 'Tubérculos',
        'Aromáticas' => 'Aromáticas',
        'Oleaginosas' => 'Oleaginosas',
        'Otros' => 'Otros',
    ];

    /**
     * Regiones de Perú disponibles.
     */
    public const REGIONES = [
        'Costa Norte' => 'Costa Norte',
        'Costa Central y Sur' => 'Costa Central y Sur',
        'Sierra' => 'Sierra',
        'Selva' => 'Selva',
    ];

    /**
     * Niveles de sensibilidad.
     */
    public const SENSIBILIDAD = [
        'Muy Baja' => 'Muy Baja',
        'Baja' => 'Baja',
        'Media' => 'Media',
        'Media-Alta' => 'Media-Alta',
        'Alta' => 'Alta',
        'Muy Alta' => 'Muy Alta',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    /**
     * Un cultivo puede estar en múltiples parcelas.
     */
    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class, 'idCultivo', 'idCultivo');
    }

    // ============================================
    // MÉTODOS DE CÁLCULO DE RIEGO
    // ============================================

    /**
     * Calcula la frecuencia de riego recomendada en días.
     * 
     * @param bool $tieneHidrogel
     * @param float $humedadAmbiental Humedad ambiental en %
     * @return int Días entre riegos
     */
    public function calcularFrecuenciaRiego(bool $tieneHidrogel = false, float $humedadAmbiental = 50): int
    {
        // Frecuencia base (promedio entre mín y máx)
        $frecuenciaBase = round(($this->frecuenciaRiegoMin + $this->frecuenciaRiegoMax) / 2);

        // Ajuste por humedad ambiental
        if ($humedadAmbiental < 40) {
            $frecuenciaBase = max($this->frecuenciaRiegoMin, $frecuenciaBase - 1); // Más seco, regar más seguido
        } elseif ($humedadAmbiental > 70) {
            $frecuenciaBase = min($this->frecuenciaRiegoMax, $frecuenciaBase + 1); // Más húmedo, regar menos
        }

        // Ajuste por hidrogel
        if ($tieneHidrogel && $this->reduccionConHidrogel) {
            $ajusteHidrogel = ($frecuenciaBase * $this->reduccionConHidrogel) / 100;
            $frecuenciaBase += round($ajusteHidrogel);
        }

        return (int) $frecuenciaBase;
    }

    /**
     * Calcula litros necesarios por riego según área.
     * 
     * @param float $areaParcela Área en metros cuadrados
     * @param int $diasSinRegar Días transcurridos sin regar
     * @param bool $tieneHidrogel
     * @return float Litros de agua necesarios
     */
    public function calcularLitrosNecesarios(float $areaParcela, int $diasSinRegar = 1, bool $tieneHidrogel = false): float
    {
        // Litros base = área × litros/m²/día × días sin regar
        $litrosBase = $areaParcela * $this->litrosPorM2Dia * $diasSinRegar;

        // Reducción por hidrogel
        if ($tieneHidrogel && $this->reduccionConHidrogel) {
            $litrosBase *= (1 - ($this->reduccionConHidrogel / 100));
        }

        return round($litrosBase, 2);
    }

    /**
     * Verifica si la temperatura está en rango crítico.
     * 
     * @param float $temperatura Temperatura actual en °C
     * @return array ['estado' => string, 'mensaje' => string]
     */
    public function evaluarTemperatura(float $temperatura): array
    {
        if ($temperatura < $this->tempCriticaMinima) {
            return [
                'estado' => 'critico_frio',
                'mensaje' => $this->efectoTempMinima ?? "Temperatura muy baja para {$this->nombreCultivo}",
            ];
        } elseif ($temperatura > $this->tempCriticaMaxima) {
            return [
                'estado' => 'critico_calor',
                'mensaje' => $this->efectoTempMaxima ?? "Temperatura muy alta para {$this->nombreCultivo}",
            ];
        }

        return [
            'estado' => 'optimo',
            'mensaje' => 'Temperatura dentro del rango óptimo',
        ];
    }

    /**
     * Verifica si el cultivo está en etapa de mayor consumo de agua.
     * 
     * @param int $diasDesdeSiembra
     * @return bool
     */
    public function estaEnEtapaMayorConsumo(int $diasDesdeSiembra): bool
    {
        if (!$this->diasEtapaConsumoMin || !$this->diasEtapaConsumoMax) {
            return false;
        }

        return $diasDesdeSiembra >= $this->diasEtapaConsumoMin 
            && $diasDesdeSiembra <= $this->diasEtapaConsumoMax;
    }

    /**
     * Calcula la urgencia de riego basado en días sin agua.
     * 
     * @param int $diasSinRegar
     * @return string 'optimo', 'alerta', 'urgente', 'critico'
     */
    public function evaluarUrgenciaRiego(int $diasSinRegar): string
    {
        $frecuenciaPromedio = ($this->frecuenciaRiegoMin + $this->frecuenciaRiegoMax) / 2;

        if ($diasSinRegar >= $this->diasCriticosSinAgua) {
            return 'critico';
        } elseif ($diasSinRegar >= $frecuenciaPromedio + 2) {
            return 'urgente';
        } elseif ($diasSinRegar >= $frecuenciaPromedio) {
            return 'alerta';
        }

        return 'optimo';
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    public function scopeSensiblesSequia($query)
    {
        return $query->whereIn('sensibilidadSequia', ['Alta', 'Muy Alta']);
    }

    public function scopeSensiblesHeladas($query)
    {
        return $query->whereIn('sensibilidadHeladas', ['Alta', 'Muy Alta']);
    }

    public function scopeCicloCorto($query, int $dias = 90)
    {
        return $query->where('cicloProductivo', '<=', $dias);
    }

    public function scopeCicloLargo($query, int $dias = 180)
    {
        return $query->where('cicloProductivo', '>=', $dias);
    }

    // ============================================
    // ACCESSORS
    // ============================================

    public function getFrecuenciaRiegoPromedioAttribute(): int
    {
        return round(($this->frecuenciaRiegoMin + $this->frecuenciaRiegoMax) / 2);
    }

    public function getRangoTemperaturaAttribute(): string
    {
        return "{$this->tempCriticaMinima}°C - {$this->tempCriticaMaxima}°C";
    }

    public function getConsumoAguaDiarioAttribute(): string
    {
        return "{$this->litrosPorM2Dia} L/m²/día";
    }
}