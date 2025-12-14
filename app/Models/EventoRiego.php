<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoRiego extends Model
{
    use HasFactory;

    protected $table = 'eventos_riego';

    protected $fillable = [
        'parcela_id',
        'user_id',
        'fecha_inicio',
        'fecha_fin',
        'duracion_minutos',
        'litros_utilizados',
        'tipo',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'litros_utilizados' => 'decimal:2',
    ];

    public function parcela()
    {
        return $this->belongsTo(Parcela::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}