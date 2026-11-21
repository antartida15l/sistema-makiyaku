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
        Schema::create('eventos_riego', function (Blueprint $table) {
            $table->id();
            
            // Foreign key a parcelas
            $table->foreignId('parcela_id')
                ->constrained('parcelas')
                ->onDelete('cascade');
            
            // Datos del riego
            $table->timestamp('fecha_riego')->useCurrent();
            $table->decimal('cantidad_agua', 10, 2)->comment('Litros de agua utilizados');
            $table->enum('tipo_riego', ['manual', 'automatico', 'goteo', 'aspersion'])->default('manual');
            
            // Observaciones opcionales
            $table->text('notas')->nullable()->comment('Observaciones del agricultor');
            
            $table->timestamps();
            
            // Índices
            $table->index('parcela_id', 'idx_eventos_parcela');
            $table->index('fecha_riego', 'idx_fecha_riego');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventos_riego');
    }
};