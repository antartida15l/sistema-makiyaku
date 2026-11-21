<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla para almacenar historial climático de cada parcela
     */
    public function up(): void
    {
        Schema::create('clima_historial', function (Blueprint $table) {
            $table->id();
            
            // Relación con parcela
            $table->foreignId('parcela_id')
                ->constrained('parcelas')
                ->onDelete('cascade');
            
            // ============================================
            // DATOS DE UBICACIÓN (cachear para no consultar API)
            // ============================================
            $table->string('ciudad', 100)->nullable();
            $table->string('distrito', 100)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lon', 10, 7)->nullable();
            
            // ============================================
            // TEMPERATURAS (°C)
            // ============================================
            $table->decimal('temperatura', 5, 2)->comment('Temperatura actual');
            $table->decimal('sensacion_termica', 5, 2)->nullable();
            $table->decimal('temp_minima', 5, 2)->nullable();
            $table->decimal('temp_maxima', 5, 2)->nullable();
            
            // ============================================
            // HUMEDAD Y PRESIÓN
            // ============================================
            $table->integer('humedad')->comment('Humedad relativa %');
            $table->integer('presion')->nullable()->comment('Presión atmosférica hPa');
            
            // ============================================
            // VIENTO
            // ============================================
            $table->decimal('viento_velocidad', 5, 2)->nullable()->comment('m/s');
            $table->integer('viento_direccion')->nullable()->comment('Grados');
            $table->string('viento_direccion_texto', 10)->nullable()->comment('N, NE, E, etc.');
            $table->decimal('viento_rafaga', 5, 2)->nullable()->comment('Ráfaga máxima m/s');
            
            // ============================================
            // PRECIPITACIONES
            // ============================================
            $table->decimal('lluvia_1h', 8, 2)->default(0)->comment('Lluvia última hora (mm)');
            $table->decimal('lluvia_3h', 8, 2)->default(0)->comment('Lluvia últimas 3h (mm)');
            $table->decimal('lluvia_24h', 8, 2)->default(0)->nullable()->comment('Pronóstico 24h (mm)');
            $table->integer('probabilidad_lluvia_24h')->default(0)->comment('% probabilidad próximas 24h');
            
            // ============================================
            // CONDICIONES GENERALES
            // ============================================
            $table->string('condicion_principal', 50)->comment('Clear, Rain, Clouds, etc.');
            $table->string('descripcion', 100)->comment('Descripción en español');
            $table->string('icono_codigo', 10)->nullable()->comment('Código del ícono');
            $table->integer('nubosidad')->default(0)->comment('% cobertura de nubes');
            $table->decimal('visibilidad', 5, 1)->nullable()->comment('Visibilidad en km');
            
            // ============================================
            // SOL
            // ============================================
            $table->time('amanecer')->nullable();
            $table->time('atardecer')->nullable();
            
            // ============================================
            // ALERTAS AUTOMÁTICAS PARA RIEGO
            // ============================================
            $table->boolean('alerta_helada')->default(false)
                ->comment('Temp < temp crítica del cultivo');
            
            $table->boolean('alerta_calor_extremo')->default(false)
                ->comment('Temp > temp crítica máxima');
            
            $table->boolean('alerta_lluvia_proxima')->default(false)
                ->comment('Alta probabilidad de lluvia próximas 24h');
            
            $table->boolean('necesita_riego_urgente')->default(false)
                ->comment('Condiciones indican necesidad de riego inmediato');
            
            // ============================================
            // METADATA
            // ============================================
            $table->timestamp('fecha_registro')->useCurrent()
                ->comment('Cuándo se registró este clima');
            
            $table->enum('tipo_actualizacion', ['automatica', 'manual'])
                ->default('automatica')
                ->comment('Si fue actualización programada o manual');
            
            $table->text('notas')->nullable()
                ->comment('Observaciones adicionales');
            
            $table->timestamps();
            
            // ============================================
            // ÍNDICES PARA CONSULTAS RÁPIDAS
            // ============================================
            $table->index('parcela_id');
            $table->index('fecha_registro');
            $table->index(['parcela_id', 'fecha_registro'], 'idx_parcela_fecha');
            $table->index('tipo_actualizacion');
            
            // Para consultas de alertas
            $table->index('alerta_helada');
            $table->index('alerta_lluvia_proxima');
            $table->index('necesita_riego_urgente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clima_historial');
    }
};