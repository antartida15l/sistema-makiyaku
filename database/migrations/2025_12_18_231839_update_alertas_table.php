<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
            
            $table->foreignId('parcela_id')
                ->constrained('parcelas')
                ->onDelete('cascade');
            
            // Tipo de alerta (ENUM con TODOS los valores)
            $table->enum('tipo', [
                'riego_urgente',
                'riego_hoy',
                'riego_proximo',
                'helada',
                'calor_extremo',
                'lluvia_proxima',
                'renovar_hidrogel',
                'fin_ciclo_proximo',
                'ciclo_completado',
            ])->default('riego_hoy');
            
            // Nivel y prioridad
            $table->enum('nivel', ['info', 'medio', 'alto', 'critico'])->default('medio');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->default('media');
            
            // Visualización
            $table->string('icono', 10)->default('🔔');
            $table->integer('puntaje_urgencia')->default(0)->comment('Puntaje 0-100');
            
            // Contenido
            $table->string('titulo', 255);
            $table->text('mensaje');
            $table->string('accion_url', 255)->nullable();
            $table->string('accion_texto', 50)->nullable();
            
            // Datos adicionales
            $table->json('datos_adicionales')->nullable();
            
            // Estado
            $table->boolean('leida')->default(false);
            $table->timestamp('leida_en')->nullable();
            $table->timestamp('fecha_expiracion')->nullable();
            $table->boolean('notificacion_enviada')->default(false);
            $table->timestamp('fecha_notificacion')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('user_id');
            $table->index('parcela_id');
            $table->index(['leida', 'prioridad']);
            $table->index('tipo');
            $table->index(['nivel', 'puntaje_urgencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};