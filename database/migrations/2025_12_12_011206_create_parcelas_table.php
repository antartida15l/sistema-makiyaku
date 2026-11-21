<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla de parcelas para sistema híbrido (Reglas + IA opcional)
     */
    public function up(): void
    {
        Schema::create('parcelas', function (Blueprint $table) {
            // ============================================
            // IDENTIFICACIÓN Y PROPIEDAD
            // ============================================
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nombre', 255);
            
            // ============================================
            // UBICACIÓN Y CULTIVO (Foreign Keys)
            // ============================================
            $table->foreignId('idUbicacion')
                ->nullable()
                ->constrained('ubicaciones', 'idUbicacion')
                ->onDelete('set null');
            
            $table->foreignId('idCultivo')
                ->nullable()
                ->constrained('cultivos', 'idCultivo')
                ->onDelete('set null');
            
            // ============================================
            // CARACTERÍSTICAS FÍSICAS
            // ============================================
            $table->decimal('area', 10, 2)->comment('Área en metros cuadrados (m²)');
            $table->enum('estado', ['activa', 'inactiva', 'mantenimiento'])->default('activa');
            
            // ============================================
            // SISTEMA DE RIEGO (Reglas Automáticas)
            // ============================================
            $table->timestamp('ultimo_riego')->nullable()
                ->comment('Última vez que se regó (manual o registrado)');
            
            $table->integer('frecuencia_riego_dias')->default(7)
                ->comment('Cada cuántos días regar (calculado por reglas)');
            
            $table->decimal('litros_por_riego', 10, 2)->nullable()
                ->comment('Litros necesarios por riego (auto-calculado)');
            
            $table->date('proximo_riego_estimado')->nullable()
                ->comment('Fecha estimada del próximo riego');
            
            // ============================================
            // TRACKING DEL CULTIVO
            // ============================================
            $table->date('fecha_siembra')->nullable()
                ->comment('Cuándo se sembró este cultivo');
            
            $table->integer('dias_desde_siembra')->default(0)
                ->comment('Días transcurridos desde la siembra');
            
            // ============================================
            // GESTIÓN DE HIDROGEL
            // ============================================
            $table->boolean('tieneHidrogelAplicado')->default(false)
                ->comment('Si la parcela tiene hidrogel aplicado');
            
            $table->date('hidrogel_fecha_aplicacion')->nullable()
                ->comment('Cuándo se aplicó el hidrogel');
            
            $table->decimal('hidrogel_cantidad_kg', 8, 2)->nullable()
                ->comment('Kilogramos de hidrogel aplicados');
            
            // ============================================
            // DATOS PARA ANÁLISIS IA (Contexto)
            // ============================================
            $table->text('notas_usuario')->nullable()
                ->comment('Observaciones del agricultor');
            
            $table->json('condiciones_especiales')->nullable()
                ->comment('Pendiente, exposición solar, tipo suelo, etc.');
            
            // ============================================
            // CAMPOS LEGACY (Compatibilidad temporal)
            // ============================================
            $table->string('ubicacion')->nullable()
                ->comment('LEGACY: Texto plano de ubicación');
            
            $table->string('tipo_cultivo')->nullable()
                ->comment('LEGACY: Nombre del cultivo en texto');
            
            $table->decimal('humedad_minima', 5, 2)->nullable()
                ->comment('LEGACY: Del sistema de sensores');
            
            $table->decimal('humedad_maxima', 5, 2)->nullable()
                ->comment('LEGACY: Del sistema de sensores');
            
            $table->timestamps();
            $table->softDeletes();
            
            // ============================================
            // ÍNDICES PARA CONSULTAS RÁPIDAS
            // ============================================
            $table->index('user_id', 'idx_user_parcelas');
            $table->index('estado', 'idx_estado');
            $table->index('ultimo_riego', 'idx_ultimo_riego');
            $table->index('proximo_riego_estimado', 'idx_proximo_riego');
            $table->index('idCultivo', 'idx_cultivo');
            $table->index('idUbicacion', 'idx_ubicacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcelas');
    }
};