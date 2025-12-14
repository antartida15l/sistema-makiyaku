<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LecturaHumedad extends Model
{
    use HasFactory;

    protected $table = 'lecturas_humedad';

    protected $fillable = [
        'parcela_id',
        'humedad',
        'temperatura',
        'sensor_id',
        'fecha_lectura',
    ];

    protected $casts = [
        'humedad' => 'decimal:2',
        'temperatura' => 'decimal:2',
        'fecha_lectura' => 'datetime',
    ];

    public function parcela()
    {
        return $this->belongsTo(Parcela::class);
    }
}