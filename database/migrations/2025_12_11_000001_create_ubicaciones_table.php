<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->id('idUbicacion');
            $table->string('distrito', 100);
            $table->string('provincia', 100);
            $table->string('region', 100);
            $table->decimal('coordenadaLatitud', 10, 8)->nullable()->comment('Latitud GPS (opcional)');
            $table->decimal('coordenadaLongitud', 11, 8)->nullable()->comment('Longitud GPS (opcional)');
            $table->timestamps();
            
            // Índice compuesto para búsquedas por ubicación completa
            $table->index(['distrito', 'provincia', 'region'], 'idx_ubicacion_completa');
            $table->index('region', 'idx_region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};