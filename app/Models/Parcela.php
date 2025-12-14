<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\LecturaHumedad;

class Parcela extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'nombre',
        'ubicacion',
        'area',
        'tipo_cultivo',
        'estado',
        'humedad_actual',
        'humedad_minima',
        'humedad_maxima',
        'esp32_id',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'humedad_actual' => 'decimal:2',
        'humedad_minima' => 'decimal:2',
        'humedad_maxima' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lecturas()
    {
        return $this->hasMany(LecturaHumedad::class);
    }

    public function eventosRiego()
    {
        return $this->hasMany(EventoRiego::class);
    }

    public function alertas()
    {
        return $this->hasMany(Alerta::class);
    }

    public function getNecesitaRiegoAttribute()
    {
        return $this->humedad_actual < $this->humedad_minima;
    }

    public function getEstadoHumedadAttribute()
    {
        if ($this->humedad_actual < $this->humedad_minima) {
            return 'baja';
        } elseif ($this->humedad_actual > $this->humedad_maxima) {
            return 'alta';
        }
        return 'optima';
    }
}