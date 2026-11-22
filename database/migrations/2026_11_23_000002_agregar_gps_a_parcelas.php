<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El GPS vivia en la "ubicacion" (distrito), que se comparte entre usuarios: dos parcelas del
 * mismo distrito tenian el mismo clima aunque estuvieran a kilometros. Ahora cada parcela
 * guarda sus propias coordenadas (las del distrito quedan como respaldo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcelas', function (Blueprint $table) {
            $table->decimal('latitud', 10, 7)->nullable()->after('idCultivo');
            $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
            $table->integer('altitud')->nullable()->after('longitud')->comment('msnm');
        });

        // Copia las coordenadas del distrito a las parcelas que ya existian.
        DB::table('parcelas')->orderBy('id')->each(function ($p) {
            $u = DB::table('ubicaciones')->where('idUbicacion', $p->idUbicacion)->first();
            if ($u && $u->coordenadaLatitud !== null && $u->coordenadaLongitud !== null) {
                DB::table('parcelas')->where('id', $p->id)->update(['latitud' => $u->coordenadaLatitud, 'longitud' => $u->coordenadaLongitud]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('parcelas', function (Blueprint $table) {
            $table->dropColumn(['latitud', 'longitud', 'altitud']);
        });
    }
};
