<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoRiego extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'eventos_riego';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'parcela_id',
        'fecha_riego',      
        'cantidad_agua',    
        'tipo_riego',       
        'notas',
    ];

    /**
     * Atributos que deben ser casteados a tipos nativos.
     */
    protected $casts = [
        'fecha_riego' => 'datetime',
        'cantidad_agua' => 'decimal:2',
    ];

    /**
     * Valores por defecto para atributos.
     */
    protected $attributes = [
        'tipo_riego' => 'manual',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    /**
     * Un evento de riego pertenece a una parcela.
     * 
     * @return BelongsTo
     */
    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class, 'parcela_id');
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope: Eventos de riego recientes (últimos 30 días).
     */
    public function scopeRecientes($query, int $dias = 30)
    {
        return $query->where('fecha_riego', '>=', now()->subDays($dias))
                     ->orderBy('fecha_riego', 'desc');
    }

    /**
     * Scope: Eventos por tipo de riego.
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_riego', $tipo);
    }

    /**
     * Scope: Eventos de una parcela específica.
     */
    public function scopeDeParcela($query, int $parcelaId)
    {
        return $query->where('parcela_id', $parcelaId);
    }

    // ============================================
    // MÉTODOS AUXILIARES
    // ============================================

    /**
     * Obtiene el total de litros usados en un rango de fechas.
     * 
     * @param int $parcelaId
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return float
     */
    public static function totalLitrosEnRango(int $parcelaId, string $fechaInicio, string $fechaFin): float
    {
        return self::where('parcela_id', $parcelaId)
            ->whereBetween('fecha_riego', [$fechaInicio, $fechaFin])
            ->sum('cantidad_agua');
    }

    /**
     * Cuenta cuántos riegos se han hecho este mes.
     * 
     * @param int $parcelaId
     * @return int
     */
    public static function riegosEsteMes(int $parcelaId): int
    {
        return self::where('parcela_id', $parcelaId)
            ->whereMonth('fecha_riego', now()->month)
            ->whereYear('fecha_riego', now()->year)
            ->count();
    }
}