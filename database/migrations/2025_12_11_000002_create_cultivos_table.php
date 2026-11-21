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
        Schema::create('cultivos', function (Blueprint $table) {
            $table->id('idCultivo');
            $table->string('nombreCultivo', 100);
            $table->string('nombreCientifico', 150)->nullable();
            $table->enum('tipo', ['Hortalizas', 'Cereales', 'Frutales', 'Legumbres', 'Tubérculos', 'Aromáticas', 'Oleaginosas', 'Otros'])->default('Hortalizas');
            $table->enum('region', ['Costa Norte', 'Costa Central y Sur', 'Sierra', 'Selva'])->nullable();
            
            // DATOS DE RIEGO
            $table->integer('frecuenciaRiegoMin')->comment('Días mínimos entre riegos');
            $table->integer('frecuenciaRiegoMax')->comment('Días máximos entre riegos');
            $table->decimal('litrosPorM2Dia', 5, 2)->comment('Litros de agua por m²/día');
            $table->decimal('humedadOptimaMin', 5, 2)->comment('Humedad óptima mínima del suelo (%)');
            $table->decimal('humedadOptimaMax', 5, 2)->comment('Humedad óptima máxima del suelo (%)');
            $table->enum('sensibilidadSequia', ['Muy Baja', 'Baja', 'Media', 'Media-Alta', 'Alta', 'Muy Alta']);
            $table->integer('diasCriticosSinAgua')->comment('Días críticos sin agua antes de daño');
            $table->string('etapaMayorConsumo', 255)->comment('Etapa fenológica de mayor consumo de agua');
            $table->integer('diasEtapaConsumoMin')->nullable()->comment('Inicio etapa mayor consumo (días)');
            $table->integer('diasEtapaConsumoMax')->nullable()->comment('Fin etapa mayor consumo (días)');
            $table->decimal('reduccionConHidrogel', 5, 2)->nullable()->comment('% de reducción de agua con hidrogel');
            
            // DATOS DE CLIMA CRÍTICO
            $table->decimal('tempCriticaMinima', 5, 2)->comment('Temperatura crítica mínima (°C)');
            $table->decimal('tempCriticaMaxima', 5, 2)->comment('Temperatura crítica máxima (°C)');
            $table->enum('sensibilidadHeladas', ['Muy Baja', 'Baja', 'Media', 'Media-Alta', 'Alta', 'Muy Alta']);
            $table->string('efectoTempMinima', 255)->nullable()->comment('Descripción del daño por temp. mínima');
            $table->string('efectoTempMaxima', 255)->nullable()->comment('Descripción del daño por temp. máxima');
            
            // DATOS GENERALES
            $table->integer('cicloProductivo')->nullable()->comment('Días del ciclo de producción');
            $table->text('descripcion')->nullable();
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            
            // Índices para búsquedas frecuentes
            $table->index('nombreCultivo');
            $table->index('tipo');
            $table->index('region');
            $table->index('sensibilidadSequia');
            $table->index('sensibilidadHeladas');
            $table->unique('nombreCultivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cultivos');
    }
};