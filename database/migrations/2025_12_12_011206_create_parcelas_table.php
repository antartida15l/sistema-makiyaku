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
        Schema::create('parcelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nombre');
            $table->string('ubicacion')->nullable();
            $table->decimal('area', 10, 2); // en hectáreas
            $table->string('tipo_cultivo');
            $table->enum('estado', ['activa', 'inactiva', 'mantenimiento'])->default('activa');
            $table->decimal('humedad_actual', 5, 2)->nullable();
            $table->decimal('humedad_minima', 5, 2)->default(30.00);
            $table->decimal('humedad_maxima', 5, 2)->default(80.00);
            $table->string('esp32_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
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