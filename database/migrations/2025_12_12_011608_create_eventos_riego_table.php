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
            $table->foreignId('parcela_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->timestamp('fecha_inicio');
            $table->timestamp('fecha_fin')->nullable();
            $table->integer('duracion_minutos')->nullable();
            $table->decimal('litros_utilizados', 10, 2)->nullable();
            $table->enum('tipo', ['manual', 'automatico', 'programado']);
            $table->enum('estado', ['en_proceso', 'completado', 'cancelado'])->default('en_proceso');
            $table->text('notas')->nullable();
            $table->timestamps();
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