<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Preferencias que el usuario puede guardar y sus valores por defecto. */
    public const PREFERENCIAS = [
        'tema' => 'light',      // light | dark
        'sonido' => true,       // sonido de alertas
        'volumen' => 0.6,       // 0 a 1
        'hojas' => true,        // hojas flotantes de fondo
    ];

    protected $fillable = ['name', 'email', 'password', 'preferencias'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferencias' => 'array',
        ];
    }

    // ---------- Relaciones ----------

    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class);
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class);
    }

    public function recomendaciones(): HasMany
    {
        return $this->hasMany(Recomendacion::class);
    }

    // ---------- Preferencias ----------

    public function pref(string $clave, mixed $defecto = null): mixed
    {
        return ($this->preferencias[$clave] ?? null) ?? self::PREFERENCIAS[$clave] ?? $defecto;
    }

    /** Preferencias completas (con valores por defecto) listas para el navegador. */
    public function preferenciasPublicas(): array
    {
        return array_replace(self::PREFERENCIAS, array_intersect_key($this->preferencias ?? [], self::PREFERENCIAS));
    }

    public function guardarPreferencias(array $nuevas): void
    {
        $validas = array_intersect_key($nuevas, self::PREFERENCIAS);

        $this->preferencias = array_replace($this->preferencias ?? [], $validas);
        $this->save();
    }

    public function iniciales(): string
    {
        return mb_strtoupper(mb_substr($this->name ?: 'U', 0, 1));
    }
}
