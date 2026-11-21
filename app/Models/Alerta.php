<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Alerta extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parcela_id',
        'tipo',
        'nivel',
        'icono',
        'prioridad',
        'puntaje_urgencia',
        'titulo',
        'mensaje',
        'accion_url',
        'accion_texto',
        'datos_adicionales',
        'leida',
        'leida_en',
        'archivada',           
        'archivada_en',        
        'fecha_expiracion',
        'notificacion_enviada',
        'fecha_notificacion',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'leida_en' => 'datetime',
        'archivada' => 'boolean',     
        'archivada_en' => 'datetime',  
        'fecha_expiracion' => 'datetime',
        'notificacion_enviada' => 'boolean',
        'fecha_notificacion' => 'datetime',
        'datos_adicionales' => 'array',
        'puntaje_urgencia' => 'integer',
    ];

    // ============================================
    // CONSTANTES - Tipos de alertas
    // ============================================
    public const TIPO_RIEGO_URGENTE = 'riego_urgente';
    public const TIPO_RIEGO_HOY = 'riego_hoy';
    public const TIPO_RIEGO_PROXIMO = 'riego_proximo';
    public const TIPO_HELADA = 'helada';
    public const TIPO_CALOR_EXTREMO = 'calor_extremo';
    public const TIPO_LLUVIA_PROXIMA = 'lluvia_proxima';
    public const TIPO_RENOVAR_HIDROGEL = 'renovar_hidrogel';
    public const TIPO_FIN_CICLO = 'fin_ciclo_proximo';
    public const TIPO_CICLO_COMPLETADO = 'ciclo_completado';

    // Niveles de alerta
    public const NIVEL_INFO = 'info';
    public const NIVEL_MEDIO = 'medio';
    public const NIVEL_ALTO = 'alto';
    public const NIVEL_CRITICO = 'critico';

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
    // SCOPES - Filtros de consulta 
    // ============================================
    
    /**
     *  Alertas no leídas
     */
    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false)
            ->where('archivada', false);
    }

    /**
     *  Alertas activas (no archivadas y no expiradas)
     */
    public function scopeActivas($query)
    {
        return $query->where('archivada', false)  
            ->where(function($q) {
                $q->whereNull('fecha_expiracion')
                  ->orWhere('fecha_expiracion', '>', now());
            });
    }

    /**
     * 
     */
    public function scopeArchivadas($query)
    {
        return $query->where('archivada', true);
    }

    /**
     * Alertas de un usuario específico
     */
    public function scopePorUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Alertas de hoy
     */
    public function scopeDeHoy($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Alertas por nivel
     */
    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    /**
     * Alertas críticas
     */
    public function scopeCriticas($query)
    {
        return $query->where('nivel', self::NIVEL_CRITICO);
    }

    /**
     * Ordenar por urgencia (más urgente primero)
     */
    public function scopeOrdenarPorUrgencia($query)
    {
        return $query->orderByRaw("
            CASE nivel
                WHEN 'critico' THEN 1
                WHEN 'alto' THEN 2
                WHEN 'medio' THEN 3
                WHEN 'info' THEN 4
            END
        ")->orderBy('puntaje_urgencia', 'desc')
          ->orderBy('created_at', 'desc');
    }

    /**
     * Alertas pendientes de notificar
     */
    public function scopePendientesNotificacion($query)
    {
        return $query->where('notificacion_enviada', false)
            ->where('leida', false)
            ->where('archivada', false)  // ✅ Agregado
            ->whereIn('nivel', [self::NIVEL_CRITICO, self::NIVEL_ALTO]);
    }

    // ============================================
    // ACCESSORS - Atributos calculados
    // ============================================

    /**
     * Color según nivel
     */
    public function getColorAttribute(): string
    {
        return match($this->nivel) {
            self::NIVEL_CRITICO => 'danger',
            self::NIVEL_ALTO => 'warning',
            self::NIVEL_MEDIO => 'info',
            self::NIVEL_INFO => 'primary',
            default => 'secondary',
        };
    }

    /**
     * Color hexadecimal
     */
    public function getColorHexAttribute(): string
    {
        return match($this->nivel) {
            self::NIVEL_CRITICO => '#dc3545',
            self::NIVEL_ALTO => '#ffc107',
            self::NIVEL_MEDIO => '#17a2b8',
            self::NIVEL_INFO => '#007bff',
            default => '#6c757d',
        };
    }

    /**
     * Clase CSS de Bootstrap
     */
    public function getClaseBadgeAttribute(): string
    {
        return "badge bg-{$this->color}";
    }

    /**
     * Verifica si está expirada
     */
    public function getEstaExpiradaAttribute(): bool
    {
        if (!$this->fecha_expiracion) {
            return false;
        }
        return $this->fecha_expiracion->isPast();
    }

    /**
     * Verifica si es reciente (últimas 24h)
     */
    public function getEsRecienteAttribute(): bool
    {
        return $this->created_at->isAfter(Carbon::now()->subDay());
    }

    /**
     * Tiempo transcurrido legible
     */
    public function getTiempoTranscurridoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // ============================================
    // MÉTODOS DE INSTANCIA 
    // ============================================

    /**
     *  Marcar como leída
     */
    public function marcarComoLeida(): void
    {
        $this->update([
            'leida' => true,
            'leida_en' => now(),
        ]);
    }

    /**
     *  Archivar alerta
     */
    public function archivar(): void
    {
        $this->update([
            'leida' => true,
            'leida_en' => $this->leida_en ?? now(),
            'archivada' => true,
            'archivada_en' => now(),
        ]);
    }

    /**
     * Marcar notificación como enviada
     */
    public function marcarNotificacionEnviada(): void
    {
        $this->update([
            'notificacion_enviada' => true,
            'fecha_notificacion' => now(),
        ]);
    }

    /**
     * Verificar si debe enviarse notificación
     */
    public function debeNotificar(): bool
    {
        return !$this->notificacion_enviada 
            && !$this->leida
            && !$this->archivada  
            && in_array($this->nivel, [self::NIVEL_CRITICO, self::NIVEL_ALTO]);
    }

    // ============================================
    // MÉTODOS ESTÁTICOS - Consultas complejas
    // ============================================

    /**
     *  Contar alertas activas por usuario
     */
    public static function contarActivasPorUsuario(int $userId): int
    {
        return self::porUsuario($userId)->activas()->count();
    }

    /**
     * Contar alertas críticas por usuario
     */
    public static function contarCriticasPorUsuario(int $userId): int
    {
        return self::porUsuario($userId)->activas()->criticas()->count();
    }

    /**
     * Obtener resumen de alertas de un usuario
     */
    public static function resumenPorUsuario(int $userId): array
    {
        $query = self::porUsuario($userId)->activas();

        return [
            'total' => $query->count(),
            'criticas' => $query->clone()->where('nivel', self::NIVEL_CRITICO)->count(),
            'altas' => $query->clone()->where('nivel', self::NIVEL_ALTO)->count(),
            'medias' => $query->clone()->where('nivel', self::NIVEL_MEDIO)->count(),
            'info' => $query->clone()->where('nivel', self::NIVEL_INFO)->count(),
            'nuevas_hoy' => $query->clone()->deHoy()->count(),
        ];
    }

    /**
     * Limpiar alertas antiguas
     */
    public static function limpiarExpiradas(): int
    {
        return self::where(function($q) {
            // Alertas expiradas
            $q->where('fecha_expiracion', '<', now())
              // O alertas archivadas hace más de 30 días
              ->orWhere(function($subQ) {
                  $subQ->where('archivada', true)
                       ->where('archivada_en', '<', now()->subDays(30));
              });
        })->delete();
    }

    /**
     * Archivar automáticamente alertas leídas antiguas
     */
    public static function archivarLeidasAntiguas(): int
    {
        return self::where('leida', true)
            ->where('archivada', false)
            ->where('leida_en', '<', now()->subDays(7))
            ->update([
                'archivada' => true,
                'archivada_en' => now(),
            ]);
    }
}