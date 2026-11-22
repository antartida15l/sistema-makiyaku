<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ClimaHistorial extends Model
{
    use HasFactory;

    protected $table = 'clima_historial';

    protected $fillable = [
        'parcela_id',
        'ciudad',
        'distrito',
        'lat',
        'lon',
        'temperatura',
        'sensacion_termica',
        'temp_minima',
        'temp_maxima',
        'humedad',
        'presion',
        'viento_velocidad',
        'viento_direccion',
        'viento_direccion_texto',
        'viento_rafaga',
        'lluvia_1h',
        'lluvia_3h',
        'lluvia_24h',
        'probabilidad_lluvia_24h',
        'condicion_principal',
        'descripcion',
        'icono_codigo',
        'nubosidad',
        'visibilidad',
        'amanecer',
        'atardecer',
        'alerta_helada',
        'alerta_calor_extremo',
        'alerta_lluvia_proxima',
        'necesita_riego_urgente',
        'et0_mm',
        'lluvia_72h_mm',
        'temp_min_prevista',
        'temp_min_suelo',
        'temp_max_prevista',
        'hora_temp_min',
        'lluvia_inicio',
        'lluvia_fin',
        'elevacion',
        'fuente',
        'desactualizado',
        'fecha_registro',
        'tipo_actualizacion',
        'notas',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lon' => 'decimal:7',
        'temperatura' => 'decimal:2',
        'sensacion_termica' => 'decimal:2',
        'temp_minima' => 'decimal:2',
        'temp_maxima' => 'decimal:2',
        'humedad' => 'integer',
        'presion' => 'integer',
        'viento_velocidad' => 'decimal:2',
        'viento_direccion' => 'integer',
        'viento_rafaga' => 'decimal:2',
        'lluvia_1h' => 'decimal:2',
        'lluvia_3h' => 'decimal:2',
        'lluvia_24h' => 'decimal:2',
        'probabilidad_lluvia_24h' => 'integer',
        'nubosidad' => 'integer',
        'visibilidad' => 'decimal:1',
        'alerta_helada' => 'boolean',
        'alerta_calor_extremo' => 'boolean',
        'alerta_lluvia_proxima' => 'boolean',
        'necesita_riego_urgente' => 'boolean',
        'fecha_registro' => 'datetime',
        'et0_mm' => 'decimal:2',
        'lluvia_72h_mm' => 'decimal:1',
        'temp_min_prevista' => 'decimal:1',
        'temp_min_suelo' => 'decimal:1',
        'temp_max_prevista' => 'decimal:1',
        'hora_temp_min' => 'datetime',
        'lluvia_inicio' => 'datetime',
        'lluvia_fin' => 'datetime',
        'desactualizado' => 'boolean',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    public function parcela()
    {
        return $this->belongsTo(Parcela::class);
    }

    // ============================================
    // ACCESSORS - Atributos Calculados
    // ============================================

    /**
     * Verifica si tiene alguna alerta activa
     */
    public function getTieneAlertasAttribute(): bool
    {
        return $this->alerta_helada 
            || $this->alerta_calor_extremo 
            || $this->alerta_lluvia_proxima 
            || $this->necesita_riego_urgente;
    }

    /**
     * Obtiene el total de alertas activas
     */
    public function getCantidadAlertasAttribute(): int
    {
        $count = 0;
        if ($this->alerta_helada) $count++;
        if ($this->alerta_calor_extremo) $count++;
        if ($this->alerta_lluvia_proxima) $count++;
        if ($this->necesita_riego_urgente) $count++;
        return $count;
    }

    /**
     * Obtiene array con las alertas activas
     */
    public function getAlertasActivasAttribute(): array
    {
        $alertas = [];

        if ($this->alerta_helada) {
            $alertas[] = [
                'tipo' => 'helada',
                'mensaje' => 'Riesgo de helada',
                'severidad' => 'alta',
            ];
        }

        if ($this->alerta_calor_extremo) {
            $alertas[] = [
                'tipo' => 'calor',
                'mensaje' => 'Calor extremo',
                'severidad' => 'alta',
            ];
        }

        if ($this->alerta_lluvia_proxima) {
            $alertas[] = [
                'tipo' => 'lluvia',
                'mensaje' => 'Alta probabilidad de lluvia',
                'severidad' => 'media',
            ];
        }

        if ($this->necesita_riego_urgente) {
            $alertas[] = [
                'tipo' => 'riego',
                'mensaje' => 'Riego urgente recomendado',
                'severidad' => 'alta',
            ];
        }

        return $alertas;
    }

    /**
     * Se mantiene por compatibilidad; el sistema ya no usa emojis
     */
    public function getEmojiClimaAttribute(): string
    {
        return '';
    }

    /**
     * URL del icono local (public/img/clima)
     */
    public function getIconoUrlAttribute(): string
    {
        if (!$this->icono_codigo) {
            return '';
        }
        return asset("img/clima/{$this->icono_codigo}.svg");
    }

    /**
     * Clasifica la intensidad del viento
     */
    public function getIntensidadVientoAttribute(): string
    {
        if (!$this->viento_velocidad) {
            return 'sin_datos';
        }

        return match(true) {
            $this->viento_velocidad < 2 => 'calma',
            $this->viento_velocidad < 6 => 'brisa_suave',
            $this->viento_velocidad < 12 => 'brisa_moderada',
            $this->viento_velocidad < 18 => 'viento_fuerte',
            default => 'viento_muy_fuerte',
        };
    }

    /**
     * Clasifica la humedad
     */
    public function getClasificacionHumedadAttribute(): string
    {
        return match(true) {
            $this->humedad < 30 => 'muy_baja',
            $this->humedad < 50 => 'baja',
            $this->humedad < 70 => 'normal',
            $this->humedad < 85 => 'alta',
            default => 'muy_alta',
        };
    }

    /**
     * Verifica si los datos son del día actual
     */
    public function getEsHoyAttribute(): bool
    {
        return $this->fecha_registro->isToday();
    }

    /**
     * Obtiene hace cuánto tiempo se registró
     */
    public function getTiempoTranscurridoAttribute(): string
    {
        return $this->fecha_registro->diffForHumans();
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Filtrar por parcela
     */
    public function scopeDeParcela($query, int $parcelaId)
    {
        return $query->where('parcela_id', $parcelaId);
    }

    /**
     * Registros de hoy
     */
    public function scopeDeHoy($query)
    {
        return $query->whereDate('fecha_registro', Carbon::today());
    }

    /**
     * Registros de los últimos N días
     */
    public function scopeUltimosDias($query, int $dias = 7)
    {
        return $query->where('fecha_registro', '>=', Carbon::today()->subDays($dias));
    }

    /**
     * Registros con alertas activas
     */
    public function scopeConAlertas($query)
    {
        return $query->where(function($q) {
            $q->where('alerta_helada', true)
              ->orWhere('alerta_calor_extremo', true)
              ->orWhere('alerta_lluvia_proxima', true)
              ->orWhere('necesita_riego_urgente', true);
        });
    }

    /**
     * Registros con alertas de helada
     */
    public function scopeConAlertaHelada($query)
    {
        return $query->where('alerta_helada', true);
    }

    /**
     * Registros con alertas de riego urgente
     */
    public function scopeNecesitaRiego($query)
    {
        return $query->where('necesita_riego_urgente', true);
    }

    /**
     * Solo actualizaciones automáticas
     */
    public function scopeAutomaticas($query)
    {
        return $query->where('tipo_actualizacion', 'automatica');
    }

    /**
     * Solo actualizaciones manuales
     */
    public function scopeManuales($query)
    {
        return $query->where('tipo_actualizacion', 'manual');
    }

    /**
     * Ordenar por fecha más reciente
     */
    public function scopeMasRecientes($query)
    {
        return $query->orderBy('fecha_registro', 'desc');
    }

    // ============================================
    // MÉTODOS ESTÁTICOS - Consultas Complejas
    // ============================================

    /**
     * Obtiene el clima más reciente de una parcela
     */
    public static function climaActualDeParcela(int $parcelaId): ?self
    {
        return self::where('parcela_id', $parcelaId)
            ->orderBy('fecha_registro', 'desc')
            ->first();
    }

    /**
     * Obtiene estadísticas climáticas de los últimos N días
     */
    public static function estadisticasUltimosDias(int $parcelaId, int $dias = 7): array
    {
        $registros = self::where('parcela_id', $parcelaId)
            ->where('fecha_registro', '>=', Carbon::today()->subDays($dias))
            ->orderBy('fecha_registro', 'desc')
            ->get();

        if ($registros->isEmpty()) {
            return [
                'tiene_datos' => false,
                'mensaje' => 'No hay datos climáticos en el período solicitado',
            ];
        }

        return [
            'tiene_datos' => true,
            'periodo_dias' => $dias,
            'total_registros' => $registros->count(),
            
            // Temperaturas
            'temperatura_promedio' => round($registros->avg('temperatura'), 1),
            'temperatura_minima' => $registros->min('temperatura'),
            'temperatura_maxima' => $registros->max('temperatura'),
            
            // Humedad
            'humedad_promedio' => round($registros->avg('humedad'), 0),
            'humedad_minima' => $registros->min('humedad'),
            'humedad_maxima' => $registros->max('humedad'),
            
            // Lluvia
            'lluvia_total' => round($registros->sum('lluvia_1h'), 2),
            'lluvia_promedio_diaria' => round($registros->avg('lluvia_1h'), 2),
            'dias_con_lluvia' => $registros->where('lluvia_1h', '>', 0)->count(),
            
            // Viento
            'viento_promedio' => round($registros->avg('viento_velocidad'), 1),
            'viento_maximo' => $registros->max('viento_velocidad'),
            
            // Alertas
            'total_alertas' => $registros->where(function($q) {
                return $q->alerta_helada || $q->alerta_calor_extremo || 
                       $q->alerta_lluvia_proxima || $q->necesita_riego_urgente;
            })->count(),
            'alertas_helada' => $registros->where('alerta_helada', true)->count(),
            'alertas_calor' => $registros->where('alerta_calor_extremo', true)->count(),
            'alertas_riego' => $registros->where('necesita_riego_urgente', true)->count(),
            
            // Última actualización
            'ultima_actualizacion' => $registros->first()->fecha_registro,
            'ultimo_clima' => $registros->first(),
        ];
    }

    /**
     * Obtiene parcelas que necesitan atención por clima
     */
    public static function parcelasConAlertasActivas(): \Illuminate\Support\Collection
    {
        return self::whereHas('parcela', function($q) {
            $q->activas();
        })
        ->whereDate('fecha_registro', Carbon::today())
        ->conAlertas()
        ->with('parcela.cultivo', 'parcela.ubicacion')
        ->get()
        ->groupBy('parcela_id')
        ->map(function($registros) {
            return $registros->first();
        })
        ->values();
    }

    /**
     * Limpia registros antiguos (mantener solo últimos N meses)
     */
    public static function limpiarHistorialAntiguo(int $mesesAMantener = 3): int
    {
        $fechaLimite = Carbon::today()->subMonths($mesesAMantener);
        
        return self::where('fecha_registro', '<', $fechaLimite)->delete();
    }

    // ============================================
    // MÉTODOS DE INSTANCIA
    // ============================================

    /**
     * Verifica si el clima es favorable para riego
     */
    public function esFavorableParaRiego(): bool
    {
        // No favorable si va a llover pronto
        if ($this->probabilidad_lluvia_24h > 50) {
            return false;
        }

        // No favorable si hay viento muy fuerte
        if ($this->viento_velocidad > 10) {
            return false;
        }

        // No favorable si hace mucho calor (mejor regar temprano/tarde)
        if ($this->temperatura > 32) {
            return false;
        }

        return true;
    }

    /**
     * Obtiene recomendación de riego basada en clima
     */
    public function obtenerRecomendacionRiego(): array
    {
        $recomendacion = [
            'puede_regar' => true,
            'momento_ideal' => 'ahora',
            'cantidad_sugerida' => 'normal',
            'razones' => [],
        ];

        // Verificar lluvia próxima
        if ($this->probabilidad_lluvia_24h > 60) {
            $recomendacion['puede_regar'] = false;
            $recomendacion['razones'][] = 'Alta probabilidad de lluvia próximamente';
        }

        // Verificar temperatura
        if ($this->temperatura > 30) {
            $recomendacion['momento_ideal'] = 'temprano_en_mañana_o_tarde';
            $recomendacion['razones'][] = 'Temperatura muy alta, regar en horas frescas';
        }

        // Verificar viento
        if ($this->viento_velocidad > 8) {
            $recomendacion['razones'][] = 'Viento fuerte, puede afectar distribución de agua';
        }

        // Ajustar cantidad según humedad
        if ($this->humedad < 30) {
            $recomendacion['cantidad_sugerida'] = 'aumentada';
            $recomendacion['razones'][] = 'Humedad muy baja, considerar regar más';
        } elseif ($this->humedad > 80) {
            $recomendacion['cantidad_sugerida'] = 'reducida';
            $recomendacion['razones'][] = 'Humedad alta, puede reducir cantidad';
        }

        return $recomendacion;
    }

    /**
     * Genera resumen legible del clima
     */
    public function generarResumen(): string
    {
        $partes = [
            "{$this->descripcion}",
            "{$this->temperatura}°C",
        ];

        if ($this->sensacion_termica && abs($this->sensacion_termica - $this->temperatura) > 3) {
            $partes[] = "(se siente como {$this->sensacion_termica}°C)";
        }

        $partes[] = "Humedad: {$this->humedad}%";

        if ($this->viento_velocidad > 5) {
            $partes[] = "Viento: {$this->viento_velocidad} m/s";
        }

        if ($this->lluvia_1h > 0) {
            $partes[] = "Lluvia: {$this->lluvia_1h}mm";
        }

        return implode(' - ', $partes);
    }
}