<?php

namespace App\Models;

use App\Services\OpenWeatherService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Parcela extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'nombre', 'area', 'estado',
        'idUbicacion', 'idCultivo',
        'ultimo_riego', 'frecuencia_riego_dias', 'litros_por_riego', 'proximo_riego_estimado',
        'fecha_siembra', 'dias_desde_siembra',
        'tieneHidrogelAplicado', 'hidrogel_fecha_aplicacion', 'hidrogel_cantidad_kg',
        'notas_usuario', 'condiciones_especiales',
        'tipo_cultivo', 'humedad_minima', 'humedad_maxima',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'ultimo_riego' => 'datetime',
        'proximo_riego_estimado' => 'date',
        'fecha_siembra' => 'date',
        'hidrogel_fecha_aplicacion' => 'date',
        'frecuencia_riego_dias' => 'integer',
        'dias_desde_siembra' => 'integer',
        'litros_por_riego' => 'decimal:2',
        'hidrogel_cantidad_kg' => 'decimal:2',
        'tieneHidrogelAplicado' => 'boolean',
        'condiciones_especiales' => 'array',
        'humedad_minima' => 'decimal:2',
        'humedad_maxima' => 'decimal:2',
    ];

    /**
     * Eventos automáticos
     */
    protected static function boot()
    {
        parent::boot();

        /**
         *  Al eliminar una parcela, verificar si debe eliminar la ubicación
         */
        static::deleting(function ($parcela) {
            // Solo si es softDelete o delete real
            if ($parcela->idUbicacion) {
                // Verificar si hay otras parcelas usando la misma ubicación
                $otrasParcelas = Parcela::where('idUbicacion', $parcela->idUbicacion)
                    ->where('id', '!=', $parcela->id)
                    ->withTrashed() 
                    ->count();

                // Si no hay otras parcelas, eliminar la ubicación
                if ($otrasParcelas === 0) {
                    \App\Models\Ubicacion::find($parcela->idUbicacion)?->delete();
                }
            }
        });

        /**
         *  Al restaurar una parcela (si usas softDeletes)
         */
        static::restoring(function ($parcela) {
            // Si la ubicación fue eliminada, restaurarla también
            if ($parcela->idUbicacion) {
                \App\Models\Ubicacion::withTrashed()
                    ->find($parcela->idUbicacion)
                    ?->restore();
            }
        });

        /**
         *  Al eliminar permanentemente (forceDelete)
         */
        static::forceDeleting(function ($parcela) {
            // Verificar si hay otras parcelas usando esta ubicación
            if ($parcela->idUbicacion) {
                $otrasParcelas = Parcela::where('idUbicacion', $parcela->idUbicacion)
                    ->where('id', '!=', $parcela->id)
                    ->withTrashed()
                    ->count();

                // Si no hay otras parcelas, eliminar la ubicación permanentemente
                if ($otrasParcelas === 0) {
                    \App\Models\Ubicacion::withTrashed()
                        ->find($parcela->idUbicacion)
                        ?->forceDelete();
                }
            }

            // Eliminar registros relacionados
            $parcela->eventosRiego()->forceDelete();
            $parcela->climaHistorial()->forceDelete();
            $parcela->alertas()->forceDelete();
        });
    }

     // ============================================
    // RELACIONES
    // ============================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usamos el nombre "ubicacion" directamente para evitar conflictos
     */
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'idUbicacion', 'idUbicacion');
    }

    public function cultivo()
    {
        return $this->belongsTo(Cultivo::class, 'idCultivo', 'idCultivo');
    }

    public function eventosRiego()
    {
        return $this->hasMany(EventoRiego::class);
    }

    public function alertas()
    {
        return $this->hasMany(Alerta::class);
    }

    /**
     * Historial de clima
     */
    public function climaHistorial()
    {
        return $this->hasMany(ClimaHistorial::class);
    }

    /**
     * Clima más reciente de la parcela
     */
    public function climaActual()
    {
        return $this->hasOne(ClimaHistorial::class)
            ->latestOfMany('fecha_registro');
    }

    /**
     * Recomendaciones de la parcela
     */
    public function recomendaciones()
    {
        return $this->hasMany(Recomendacion::class);
    }

    // ============================================
    // ACCESSORS
    // ============================================

    public function getNecesitaRiegoAttribute(): bool
    {
        if (!$this->proximo_riego_estimado) {
            return false;
        }
        
        return $this->proximo_riego_estimado->lte(Carbon::today());
    }

    public function getRiegoAtrasadoAttribute(): bool
    {
        if (!$this->proximo_riego_estimado) {
            return false;
        }
        
        return $this->proximo_riego_estimado->lt(Carbon::today());
    }

    public function getDiasParaRiegoAttribute(): int
    {
        if (!$this->proximo_riego_estimado) {
            return 0;
        }
        
        return Carbon::today()->diffInDays($this->proximo_riego_estimado, false);
    }

    public function getEstadoRiegoAttribute(): string
    {
        if (!$this->proximo_riego_estimado) {
            return 'sin_datos';
        }

        $dias = $this->dias_para_riego;
        
        if ($dias < 0) {
            return 'critico';
        } elseif ($dias === 0) {
            return 'hoy';
        } elseif ($dias <= 2) {
            return 'proximo';
        }
        
        return 'ok';
    }

    /**
     *  Usa la relación renombrada
     */
  
    public function getUbicacionCompletaAttribute(): string
    {
        // Si la relación está cargada, usarla
        if ($this->relationLoaded('ubicacion') && $this->ubicacion) {
            return "{$this->ubicacion->distrito}, {$this->ubicacion->provincia}, {$this->ubicacion->region}";
        }
        
        // Si no está cargada, intentar cargarla
        $ubicacion = $this->ubicacion;
        if ($ubicacion) {
            return "{$ubicacion->distrito}, {$ubicacion->provincia}, {$ubicacion->region}";
        }
        
        // Fallback: usar el campo legacy si existe
        return $this->attributes['ubicacion'] ?? 'Sin ubicación';
    }

    public function getEtapaCrecimientoAttribute(): string
    {
        if (!$this->fecha_siembra || !$this->cultivo) {
            return 'desconocida';
        }

        $dias = $this->dias_desde_siembra;
        
        if ($dias < 15) {
            return 'germinacion';
        } elseif ($dias < 45) {
            return 'vegetativa';
        } elseif ($dias < 90) {
            return 'floracion';
        } elseif ($dias < 150) {
            return 'fructificacion';
        }
        
        return 'madura';
    }

    public function getProgresoSiembraAttribute(): ?int
    {
        if (!$this->fecha_siembra || !$this->cultivo || !$this->cultivo->cicloCultivoDias) {
            return null;
        }

        $progreso = ($this->dias_desde_siembra / $this->cultivo->cicloCultivoDias) * 100;
        
        return min(100, (int) $progreso);
    }

    public function getTotalAguaUtilizadaAttribute(): float
    {
        return $this->eventosRiego()->sum('cantidad_agua');
    }

    public function getPromedioAguaPorRiegoAttribute(): float
    {
        return $this->eventosRiego()->avg('cantidad_agua') ?? 0;
    }

    /**
     * Verifica si tiene clima actualizado hoy
     */
    public function getTieneClimaHoyAttribute(): bool
    {
        return $this->climaHistorial()
            ->whereDate('fecha_registro', Carbon::today())
            ->exists();
    }

    /**
     * Verifica si tiene alertas climáticas activas
     */
    public function getTieneAlertasClimaticasAttribute(): bool
    {
        $clima = $this->climaActual;
        
        return $clima && $clima->tiene_alertas;
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeDelUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeMisParcelas($query)
    {
        return $query->where('user_id', auth()->id());
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    public function scopeConHidrogel($query)
    {
        return $query->where('tieneHidrogelAplicado', true);
    }

    public function scopeNecesitanRiego($query)
    {
        return $query->whereDate('proximo_riego_estimado', '<=', Carbon::today());
    }

    public function scopeRiegoAtrasado($query)
    {
        return $query->whereDate('proximo_riego_estimado', '<', Carbon::today());
    }

    public function scopeRiegoHoy($query)
    {
        return $query->whereDate('proximo_riego_estimado', '=', Carbon::today());
    }

    public function scopeOrdenarPorUrgencia($query)
    {
        return $query->orderByRaw("
            CASE 
                WHEN proximo_riego_estimado < CURDATE() THEN 1
                WHEN proximo_riego_estimado = CURDATE() THEN 2
                ELSE 3
            END
        ")->orderBy('proximo_riego_estimado', 'asc');
    }

    /**
     * Parcelas con alertas climáticas activas
     */
    public function scopeConAlertasClimaticas($query)
    {
        return $query->whereHas('climaActual', function($q) {
            $q->where(function($subQ) {
                $subQ->where('alerta_helada', true)
                     ->orWhere('alerta_calor_extremo', true)
                     ->orWhere('alerta_lluvia_proxima', true)
                     ->orWhere('necesita_riego_urgente', true);
            });
        });
    }

    /**
     * Registra un nuevo riego
     */
    public function registrarRiego(float $litrosUtilizados = null, string $tipoRiego = 'manual', ?string $notas = null): EventoRiego
    {
        // Si no se especifican litros, calcularlos ahora
        if ($litrosUtilizados === null) {
            $litrosUtilizados = $this->calcularLitrosPorRiego();
            
            // Si sigue siendo 0, usar un valor por defecto
            if ($litrosUtilizados == 0) {
                $litrosUtilizados = $this->area * 5; // 5 litros por m² como fallback
            }
        }
        
        // Crear evento de riego
        $eventoRiego = $this->eventosRiego()->create([
            'fecha_riego' => Carbon::now(),
            'cantidad_agua' => $litrosUtilizados,
            'tipo_riego' => $tipoRiego,
            'notas' => $notas,
        ]);

        // Actualizar fechas de riego
        $this->update([
            'ultimo_riego' => Carbon::now(),
            'proximo_riego_estimado' => Carbon::now()->addDays($this->frecuencia_riego_dias),
        ]);

        return $eventoRiego;
    }

    public function actualizarDiasSiembra(): void
    {
        if (!$this->fecha_siembra) {
            return;
        }

        $dias = $this->fecha_siembra->diffInDays(Carbon::today());
        
        if ($this->dias_desde_siembra !== $dias) {
            $this->update(['dias_desde_siembra' => $dias]);
        }
    }

    public function recalcularProximoRiego(): void
    {
        if (!$this->ultimo_riego || !$this->frecuencia_riego_dias) {
            return;
        }

        $proximoRiego = Carbon::parse($this->ultimo_riego)->addDays($this->frecuencia_riego_dias);
        
        $this->update(['proximo_riego_estimado' => $proximoRiego]);
    }

    public function aplicarHidrogel(float $cantidadKg, ?Carbon $fechaAplicacion = null): void
    {
        $this->update([
            'tieneHidrogelAplicado' => true,
            'hidrogel_fecha_aplicacion' => $fechaAplicacion ?? Carbon::today(),
            'hidrogel_cantidad_kg' => $cantidadKg,
        ]);

        $this->ajustarFrecuenciaConHidrogel();
    }

    /**
     * Calcula los litros por riego según área y cultivo
     * @return float
     */
    public function calcularLitrosPorRiego(): float
    {
        if (!$this->cultivo || !$this->area) {
            return 0;
        }

        // Fórmula: litros/m² según requerimiento de humedad del cultivo
        $litrosPorM2 = ($this->cultivo->requerimientoHumedad / 100) * 9;
        
        // Ajuste por hidrogel (reduce 25% el consumo)
        $factorHidrogel = $this->tieneHidrogelAplicado ? 0.75 : 1.0;
        
        // Calcular total
        return round($this->area * $litrosPorM2 * $factorHidrogel, 2);
    }

    private function ajustarFrecuenciaConHidrogel(): void
    {
        if (!$this->cultivo) {
            return;
        }

        $frecuenciaBase = match(true) {
            $this->cultivo->requerimientoHumedad >= 80 => 3,
            $this->cultivo->requerimientoHumedad >= 65 => 5,
            $this->cultivo->requerimientoHumedad >= 50 => 7,
            default => 10,
        };

        $nuevaFrecuencia = $this->tieneHidrogelAplicado ? $frecuenciaBase + 2 : $frecuenciaBase;
        
        $this->update(['frecuencia_riego_dias' => $nuevaFrecuencia]);
    }

    public function obtenerEstadisticasRiego(): array
    {
        $eventosRiego = $this->eventosRiego;

        return [
            'total_riegos' => $eventosRiego->count(),
            'total_agua_litros' => $eventosRiego->sum('cantidad_agua'),
            'promedio_agua_por_riego' => $eventosRiego->avg('cantidad_agua'),
            'ultimo_riego' => $this->ultimo_riego,
            'proximo_riego' => $this->proximo_riego_estimado,
            'dias_para_riego' => $this->dias_para_riego,
            'estado_riego' => $this->estado_riego,
        ];
    }

    /**
     * Obtiene estadísticas climáticas de la parcela
     */
    public function obtenerEstadisticasClima(int $dias = 7): array
    {
        return ClimaHistorial::estadisticasUltimosDias($this->id, $dias);
    }

    /**
     * Fuerza actualización del clima en tiempo real
     */
    public function actualizarClimaAhora(): ?ClimaHistorial
    {
        $service = app(OpenWeatherService::class);
        
        //  Usar la relación renombrada
        $ubicacionData = $this->ubicacionRelacion;
        
        if (!$ubicacionData) {
            return null;
        }

        try {
            $clima = $service->forceRefresh(
                "{$ubicacionData->distrito}, {$ubicacionData->provincia}",
                'PE'
            );

            if (!$clima) {
                return null;
            }

            // Obtener pronóstico
            $pronostico = $service->getWeatherSummaryForIrrigation(
                $clima['lat'] ?? 0,
                $clima['lon'] ?? 0
            );

            // Detectar alertas
            $alertas = $this->detectarAlertas($clima, $pronostico);

            // Crear registro
            return $this->climaHistorial()->create([
                'ciudad' => $clima['name'],
                'distrito' => $ubicacionData->distrito,
                'lat' => $clima['lat'],
                'lon' => $clima['lon'],
                'temperatura' => $clima['temp'],
                'sensacion_termica' => $clima['feels_like'],
                'temp_minima' => $clima['temp_min'],
                'temp_maxima' => $clima['temp_max'],
                'humedad' => $clima['humidity'],
                'presion' => $clima['pressure'],
                'viento_velocidad' => $clima['wind_speed'],
                'viento_direccion' => $clima['wind_deg'],
                'viento_direccion_texto' => $clima['wind_direction'],
                'viento_rafaga' => $clima['wind_gust'],
                'lluvia_1h' => $clima['rain_1h'],
                'lluvia_3h' => $clima['rain_3h'],
                'lluvia_24h' => $pronostico['rain_forecast_24h'] ?? 0,
                'probabilidad_lluvia_24h' => $pronostico['rain_probability_24h'] ?? 0,
                'condicion_principal' => $clima['weather_main'],
                'descripcion' => $clima['weather_description'],
                'icono_codigo' => $clima['weather_icon'],
                'nubosidad' => $clima['clouds'],
                'visibilidad' => $clima['visibility'],
                'amanecer' => $clima['sunrise_formatted'],
                'atardecer' => $clima['sunset_formatted'],
                'alerta_helada' => $alertas['helada'],
                'alerta_calor_extremo' => $alertas['calor_extremo'],
                'alerta_lluvia_proxima' => $alertas['lluvia_proxima'],
                'necesita_riego_urgente' => $alertas['riego_urgente'],
                'fecha_registro' => now(),
                'tipo_actualizacion' => 'manual',
            ]);

        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Detecta alertas climáticas
     */
    private function detectarAlertas(array $clima, array $pronostico): array
    {
        $alertas = [
            'helada' => false,
            'calor_extremo' => false,
            'lluvia_proxima' => false,
            'riego_urgente' => false,
        ];

        if (!$this->cultivo) {
            return $alertas;
        }

        if ($clima['temp'] <= $this->cultivo->tempCriticaMinima) {
            $alertas['helada'] = true;
        }

        if ($clima['temp'] >= $this->cultivo->tempCriticaMaxima) {
            $alertas['calor_extremo'] = true;
        }

        if (($pronostico['rain_probability_24h'] ?? 0) > 50) {
            $alertas['lluvia_proxima'] = true;
        }

        $necesitaRiego = false;
        if ($clima['temp'] > 28 && $clima['humidity'] < 40) {
            $necesitaRiego = true;
        }
        if ($this->riego_atrasado && ($pronostico['rain_probability_24h'] ?? 0) < 30) {
            $necesitaRiego = true;
        }
        if (($clima['wind_speed'] ?? 0) > 8 && $clima['humidity'] < 50) {
            $necesitaRiego = true;
        }

        $alertas['riego_urgente'] = $necesitaRiego;

        return $alertas;
    }
}