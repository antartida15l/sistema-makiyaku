<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ubicacion extends Model
{
    use SoftDeletes;

    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected $table = 'ubicaciones';

    /**
     * Clave primaria de la tabla.
     */
    protected $primaryKey = 'idUbicacion';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'distrito',
        'provincia',
        'region',
        'coordenadaLatitud',
        'coordenadaLongitud',
    ];

    /**
     * Atributos que deben ser casteados a tipos nativos.
     */
    protected $casts = [
        'coordenadaLatitud' => 'decimal:8',
        'coordenadaLongitud' => 'decimal:8',
    ];

    /**
     * Atributos de fecha para SoftDeletes
     */
    protected $dates = ['deleted_at'];

    // ============================================
    // RELACIONES
    // ============================================

    /**
     * Relación: Una ubicación puede tener múltiples parcelas.
     * 
     * @return HasMany
     */
    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class, 'idUbicacion', 'idUbicacion');
    }

    // ============================================
    // MÉTODOS AUXILIARES
    // ============================================

    /**
     * Retorna dirección completa formateada.
     * 
     * @return string
     */
    public function obtenerDireccionCompleta(): string
    {
        return "{$this->distrito}, {$this->provincia}, {$this->region}";
    }

    /**
     * Retorna representación en texto de la ubicación.
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->obtenerDireccionCompleta();
    }

    /**
     * Valida que todos los campos obligatorios estén completos.
     * 
     * @return bool
     */
    public function esValida(): bool
    {
        return !empty($this->distrito) && 
               !empty($this->provincia) && 
               !empty($this->region);
    }

    /**
     * Verifica si tiene coordenadas GPS registradas.
     * 
     * @return bool
     */
    public function tieneCoordenadasGPS(): bool
    {
        return !is_null($this->coordenadaLatitud) && 
               !is_null($this->coordenadaLongitud);
    }

    /**
     * Verifica si la ubicación está siendo usada por parcelas activas.
     * 
     * @return bool
     */
    public function estaEnUso(): bool
    {
        return $this->parcelas()->count() > 0;
    }

    /**
     * Cuenta cuántas parcelas usan esta ubicación.
     * 
     * @return int
     */
    public function contarParcelasActivas(): int
    {
        return $this->parcelas()->count();
    }

    /**
     * Obtiene las coordenadas como array.
     * 
     * @return array|null
     */
    public function obtenerCoordenadas(): ?array
    {
        if (!$this->tieneCoordenadasGPS()) {
            return null;
        }

        return [
            'latitud' => (float) $this->coordenadaLatitud,
            'longitud' => (float) $this->coordenadaLongitud,
        ];
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope: Buscar ubicaciones por región.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $region
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Scope: Buscar ubicaciones por provincia.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $provincia
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorProvincia($query, string $provincia)
    {
        return $query->where('provincia', $provincia);
    }

    /**
     * Scope: Buscar ubicaciones por distrito.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $distrito
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorDistrito($query, string $distrito)
    {
        return $query->where('distrito', $distrito);
    }

    /**
     * Scope: Buscar ubicaciones con coordenadas GPS.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConGPS($query)
    {
        return $query->whereNotNull('coordenadaLatitud')
                    ->whereNotNull('coordenadaLongitud');
    }

    /**
     * Scope: Buscar ubicaciones sin coordenadas GPS.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSinGPS($query)
    {
        return $query->whereNull('coordenadaLatitud')
                    ->orWhereNull('coordenadaLongitud');
    }

    /**
     * Scope: Ubicaciones en uso (con parcelas activas).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnUso($query)
    {
        return $query->has('parcelas');
    }

    /**
     * Scope: Ubicaciones sin uso (sin parcelas).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSinUso($query)
    {
        return $query->doesntHave('parcelas');
    }

    /**
     * Scope: Buscar ubicaciones huérfanas (sin parcelas, incluyendo eliminadas).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHuerfanas($query)
    {
        return $query->whereDoesntHave('parcelas', function($q) {
            $q->withTrashed();
        });
    }

    // ============================================
    // MÉTODOS ESTÁTICOS AUXILIARES
    // ============================================

    /**
     * Busca o crea una ubicación basada en distrito, provincia y región.
     * 
     * @param string $distrito
     * @param string $provincia
     * @param string $region
     * @param float|null $latitud
     * @param float|null $longitud
     * @return self
     */
    public static function buscarOCrear(
        string $distrito, 
        string $provincia, 
        string $region,
        ?float $latitud = null,
        ?float $longitud = null
    ): self {
        return self::firstOrCreate(
            [
                'distrito' => $distrito,
                'provincia' => $provincia,
                'region' => $region,
            ],
            [
                'coordenadaLatitud' => $latitud,
                'coordenadaLongitud' => $longitud,
            ]
        );
    }

    /**
     * Limpia ubicaciones huérfanas (sin parcelas asociadas).
     * 
     * @return int Número de ubicaciones eliminadas
     */
    public static function limpiarHuerfanas(): int
    {
        $huerfanas = self::huerfanas()->get();
        $count = $huerfanas->count();

        foreach ($huerfanas as $ubicacion) {
            $ubicacion->delete();
        }

        return $count;
    }

    /**
     * Obtiene estadísticas de ubicaciones.
     * 
     * @return array
     */
    public static function obtenerEstadisticas(): array
    {
        return [
            'total' => self::count(),
            'con_gps' => self::conGPS()->count(),
            'sin_gps' => self::sinGPS()->count(),
            'en_uso' => self::enUso()->count(),
            'huerfanas' => self::huerfanas()->count(),
            'eliminadas' => self::onlyTrashed()->count(),
        ];
    }
}