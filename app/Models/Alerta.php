<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    use HasFactory;

    protected $fillable = [
        'parcela_id',
        'user_id',
        'tipo',
        'prioridad',
        'titulo',
        'mensaje',
        'leida',
        'fecha_leida',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'fecha_leida' => 'datetime',
    ];

    public function parcela()
    {
        return $this->belongsTo(Parcela::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function marcarComoLeida()
    {
        $this->update([
            'leida' => true,
            'fecha_leida' => now(),
        ]);
    }
}