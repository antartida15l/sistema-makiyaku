<?php

namespace App\Models;

use App\Services\ClimaService;
use App\Services\RiegoService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Parcela extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'nombre', 'area', 'estado',
        'idUbicacion', 'idCultivo', 'latitud', 'longitud', 'altitud',
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
        'latitud' => 'float',
        'longitud' => 'float',
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

    // ============================================
    // GPS y balance de agua
    // ============================================

    /** Latitud de la parcela; si no tiene, la del distrito. */
    public function getLatitudEfectivaAttribute(): ?float
    {
        $v = $this->latitud ?? $this->ubicacion?->coordenadaLatitud;

        return $v === null ? null : (float) $v;
    }

    public function getLongitudEfectivaAttribute(): ?float
    {
        $v = $this->longitud ?? $this->ubicacion?->coordenadaLongitud;

        return $v === null ? null : (float) $v;
    }

    public function getTieneGpsAttribute(): bool
    {
        return $this->latitud_efectiva !== null && $this->longitud_efectiva !== null;
    }

    /** true si las coordenadas son de la propia parcela y no del distrito. */
    public function getGpsPropioAttribute(): bool
    {
        return $this->latitud !== null && $this->longitud !== null;
    }

    /**
     * Cuanta agua le queda al suelo segun el ciclo de riego (0 a 100). Es lo que dibuja la gota:
     * recien regado = 100, toca regar = 0.
     */
    public function getNivelAguaAttribute(): int
    {
        if (!$this->ultimo_riego || !$this->cultivo) {
            return 50;
        }

        $dias = (int) Carbon::parse($this->ultimo_riego)->startOfDay()->diffInDays(Carbon::today());
        $frecuencia = max(1, app(RiegoService::class)->frecuenciaDias($this, $this->climaActual));

        return (int) max(0, min(100, round(100 * (1 - $dias / $frecuencia))));
    }

    /** Color de la gota: sigue a la alerta de riego activa; si no hay, al nivel de agua. */
    public function getTonoRiegoAttribute(): string
    {
        if ($this->relationLoaded('alertas')) {
            $a = $this->alertas->where('grupo', Alerta::GRUPO_RIEGO)->where('archivada', false)->first();
            if ($a) {
                return $a->nivel;
            }
        }

        return $this->nivel_agua <= 25 ? 'medio' : 'ok';
    }

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
        if (!$this->fecha_siembra || !$this->cultivo || !$this->cultivo->cicloProductivo) {
            return null;
        }

        $progreso = ($this->dias_desde_siembra / $this->cultivo->cicloProductivo) * 100;
        
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
        $hoy = Carbon::today()->toDateString();

        return $query->orderByRaw("
            CASE 
                WHEN proximo_riego_estimado < ? THEN 1
                WHEN proximo_riego_estimado = ? THEN 2
                ELSE 3
            END
        ", [$hoy, $hoy])->orderBy('proximo_riego_estimado', 'asc');
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
     * Registra un riego, actualiza las fechas y deja la frecuencia calculada con el clima actual.
     */
    public function registrarRiego(?float $litrosUtilizados = null, string $tipoRiego = 'manual', ?string $notas = null, ?Carbon $fecha = null): EventoRiego
    {
        $fecha ??= Carbon::now();

        if ($litrosUtilizados === null) {
            $litrosUtilizados = $this->calcularLitrosPorRiego() ?: $this->area * 5;
        }

        $evento = $this->eventosRiego()->create([
            'fecha_riego' => $fecha,
            'cantidad_agua' => $litrosUtilizados,
            'tipo_riego' => $tipoRiego,
            'notas' => $notas,
        ]);

        // Solo adelanta el "ultimo riego": si se registra uno viejo no pisa a uno mas reciente
        if (!$this->ultimo_riego || $fecha->greaterThanOrEqualTo($this->ultimo_riego)) {
            $this->loadMissing('cultivo');
            $frecuencia = app(RiegoService::class)->frecuenciaDias($this, $this->climaActual) ?: $this->frecuencia_riego_dias;

            $this->update([
                'ultimo_riego' => $fecha,
                'frecuencia_riego_dias' => $frecuencia,
                'proximo_riego_estimado' => $fecha->copy()->addDays($frecuencia),
            ]);
        }

        return $evento;
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
     * Litros para un riego normal de esta parcela.
     * Usa RiegoService, la misma formula que las alertas y recomendaciones.
     */
    public function calcularLitrosPorRiego(): float
    {
        return app(RiegoService::class)->litrosPorRiego($this, $this->climaActual);
    }

    private function ajustarFrecuenciaConHidrogel(): void
    {
        if (!$this->cultivo) {
            return;
        }

        $this->update([
            'frecuencia_riego_dias' => app(RiegoService::class)->frecuenciaDias($this, $this->climaActual),
        ]);
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
     * Fuerza actualizacion del clima desde Open-Meteo.
     */
    public function actualizarClimaAhora(): ?ClimaHistorial
    {
        try {
            return app(ClimaService::class)->actualizarClimaParcela($this, true, 'manual');
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
