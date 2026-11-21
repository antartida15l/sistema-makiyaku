<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Sistema de recomendaciones inteligentes por parcela
     * Analiza clima, riego, cultivo y genera sugerencias automáticas
     */
    public function up(): void
    {
        Schema::create('recomendaciones', function (Blueprint $table) {
            $table->id();
            
            // ============================================
            // RELACIONES
            // ============================================
            $table->foreignId('parcela_id')
                ->constrained('parcelas')
                ->onDelete('cascade');
            
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            // ============================================
            // TIPO Y CATEGORÍA
            // ============================================
            $table->enum('categoria', [
                'riego',           // Recomendaciones de riego
                'clima',           // Alertas y consejos climáticos
                'fertilizacion',   // Nutrición del cultivo
                'plagas',          // Prevención de plagas
                'cosecha',         // Momento óptimo de cosecha
                'mantenimiento',   // Mantenimiento general
                'hidrogel',        // Gestión de hidrogel
                'general'          // Recomendaciones generales
            ])->default('general');
            
            $table->enum('prioridad', [
                'critica',    // Acción inmediata requerida
                'alta',       // Atención en 24-48h
                'media',      // Atención en esta semana
                'baja',       // Informativo
                'info'        // Solo información
            ])->default('media');
            
            // ============================================
            // CONTENIDO
            // ============================================
            $table->string('titulo', 200);
            $table->text('descripcion');
            $table->string('icono', 20)->default('💡')->comment('Emoji representativo');
            
            // Acción sugerida (opcional)
            $table->string('accion_texto', 100)->nullable()->comment('Texto del botón de acción');
            $table->string('accion_url', 255)->nullable()->comment('URL de la acción');
            
            // ============================================
            // DATOS DE ANÁLISIS
            // ============================================
            $table->json('datos_analisis')->nullable()->comment('Datos usados para generar la recomendación');
            
            // Puntaje de relevancia (0-100)
            $table->integer('puntaje_relevancia')->default(50)
                ->comment('Qué tan relevante es esta recomendación');
            
            // Condiciones que activaron esta recomendación
            $table->json('condiciones_activacion')->nullable();
            
            // ============================================
            // SEGUIMIENTO
            // ============================================
            $table->boolean('aplicada')->default(false)
                ->comment('Usuario marcó como aplicada');
            
            $table->timestamp('aplicada_en')->nullable();
            
            $table->boolean('descartada')->default(false)
                ->comment('Usuario la descartó');
            
            $table->timestamp('descartada_en')->nullable();
            
            $table->text('notas_usuario')->nullable()
                ->comment('Notas del usuario sobre esta recomendación');
            
            // ============================================
            // VIGENCIA
            // ============================================
            $table->timestamp('vigente_desde')->useCurrent();
            $table->timestamp('vigente_hasta')->nullable()
                ->comment('Cuándo deja de ser relevante');
            
            $table->boolean('activa')->default(true)
                ->comment('Si sigue siendo relevante');
            
            // ============================================
            // METADATA
            // ============================================
            $table->enum('tipo_generacion', [
                'automatica',     // Generada por el sistema
                'manual',         // Creada manualmente
                'ia'             // Generada por IA (futuro)
            ])->default('automatica');
            
            $table->string('fuente', 100)->nullable()
                ->comment('Qué proceso generó esta recomendación');
            
            $table->timestamps();
            
            // ============================================
            // ÍNDICES
            // ============================================
            $table->index('parcela_id');
            $table->index('user_id');
            $table->index('categoria');
            $table->index('prioridad');
            $table->index(['activa', 'vigente_hasta'], 'idx_vigencia');
            $table->index(['aplicada', 'descartada'], 'idx_estado');
            $table->index('puntaje_relevancia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recomendaciones');
    }
};