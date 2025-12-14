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
        Schema::create('lecturas_humedad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained()->onDelete('cascade');
            $table->decimal('humedad', 5, 2);
            $table->decimal('temperatura', 5, 2)->nullable();
            $table->string('sensor_id');
            $table->timestamp('fecha_lectura');
            $table->timestamps();
            
            $table->index(['parcela_id', 'fecha_lectura']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lecturas_humedad');
    }
};