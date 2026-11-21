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
        Schema::table('alertas', function (Blueprint $table) {
            // Agregar campos de archivado
            $table->boolean('archivada')->default(false)->after('leida_en');
            $table->timestamp('archivada_en')->nullable()->after('archivada');
            
            // Agregar índice para mejorar rendimiento
            $table->index(['archivada', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->dropIndex(['archivada', 'user_id']);
            $table->dropColumn(['archivada', 'archivada_en']);
        });
    }
};