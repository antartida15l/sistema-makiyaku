<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Recomendacion extends Model
{
    use HasFactory;

    // ============================================
    // CONSTANTES (CATEGORÍAS)
    // ============================================
    public const CATEGORIA_RIEGO          = 'riego';
    public const CATEGORIA_CLIMA          = 'clima';
    public const CATEGORIA_FERTILIZACION  = 'fertilizacion';
    public const CATEGORIA_PLAGAS         = 'plagas';
    public const CATEGORIA_COSECHA        = 'cosecha';
    public const CATEGORIA_MANTENIMIENTO  = 'mantenimiento';
    public const CATEGORIA_HIDROGEL       = 'hidrogel';
    public const CATEGORIA_GENERAL        = 'general';

    // ============================================
    // CONSTANTES (PRIORIDAD)
    // ============================================
    public const PRIORIDAD_CRITICA = 'critica';
    public const PRIORIDAD_ALTA    = 'alta';
    public const PRIORIDAD_MEDIA   = 'media';
    public const PRIORIDAD_BAJA    = 'baja';
    public const PRIORIDAD_INFO    = 'info';

    // ============================================
    // TABLA
    // ============================================
    protected $table = 'recomendaciones';

    // ============================================
    // MASS ASSIGNMENT
    // ============================================
    protected $fillable = [
        'parcela_id',
        'user_id',
        'categoria',
        'prioridad',
        'titulo',
        'descripcion',
        'icono',
        'accion_texto',
        'accion_url',
        'datos_analisis',
        'condiciones_activacion',
        'puntaje_relevancia',
        'aplicada',
        'aplicada_en',
        'descartada',
        'descartada_en',
        'notas_usuario',
        'vigente_desde',
        'vigente_hasta',
        'activa',
        'tipo_generacion',
        'fuente',
    ];

    // ============================================
    // CASTS
    // ============================================
    protected $casts = [
        'datos_analisis'          => 'array',
        'condiciones_activacion'  => 'array',
        'aplicada'                => 'boolean',
        'descartada'              => 'boolean',
        'activa'                  => 'boolean',
        'aplicada_en'             => 'datetime',
        'descartada_en'           => 'datetime',
        'vigente_desde'           => 'datetime',
        'vigente_hasta'           => 'datetime',
    ];

    // ============================================
    // RELACIONES
    // ============================================
    public function parcela()
    {
        return $this->belongsTo(Parcela::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ============================================
    // SCOPES
    // ============================================
    public function scopeActivas(Builder $query)
    {
        return $query
            ->where('activa', true)
            ->where(function ($q) {
                $q->whereNull('vigente_hasta')
                  ->orWhere('vigente_hasta', '>=', now());
            })
            ->where('aplicada', false)
            ->where('descartada', false);
    }

    public function scopeOrdenarPorRelevancia(Builder $query)
    {
        return $query
            ->orderByRaw("
                CASE prioridad
                    WHEN 'critica' THEN 1
                    WHEN 'alta' THEN 2
                    WHEN 'media' THEN 3
                    WHEN 'baja' THEN 4
                    WHEN 'info' THEN 5
                END
            ")
            ->orderByDesc('puntaje_relevancia')
            ->orderByDesc('created_at');
    }

    // ============================================
    // MÉTODOS DE ESTADO
    // ============================================
    public function marcarComoAplicada(?string $notas = null): void
    {
        $this->update([
            'aplicada'      => true,
            'aplicada_en'   => now(),
            'notas_usuario' => $notas,
            'activa'        => false,
        ]);
    }

    public function marcarComoDescartada(?string $notas = null): void
    {
        $this->update([
            'descartada'    => true,
            'descartada_en' => now(),
            'notas_usuario' => $notas,
            'activa'        => false,
        ]);
    }

    // ============================================
    // ACCESSORS (PARA LA VISTA)
    // ============================================
    public function getEtiquetaPrioridadAttribute(): string
    {
        return match ($this->prioridad) {
            self::PRIORIDAD_CRITICA => 'Crítica',
            self::PRIORIDAD_ALTA    => 'Alta',
            self::PRIORIDAD_MEDIA   => 'Media',
            self::PRIORIDAD_BAJA    => 'Baja',
            self::PRIORIDAD_INFO    => 'Info',
            default                 => ucfirst($this->prioridad),
        };
    }

    public function getEtiquetaCategoriaAttribute(): string
    {
        return match ($this->categoria) {
            self::CATEGORIA_RIEGO         => 'Riego',
            self::CATEGORIA_CLIMA         => 'Clima',
            self::CATEGORIA_FERTILIZACION => 'Fertilización',
            self::CATEGORIA_PLAGAS        => 'Plagas',
            self::CATEGORIA_COSECHA       => 'Cosecha',
            self::CATEGORIA_MANTENIMIENTO => 'Mantenimiento',
            self::CATEGORIA_HIDROGEL      => 'Hidrogel',
            default                       => 'General',
        };
    }
}
