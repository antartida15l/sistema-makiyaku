<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - clima_historial: datos agronomicos de Open-Meteo (ET0, lluvia real,
 *   minima/maxima prevista, temperatura estimada a nivel de suelo).
 * - alertas: una sola alerta activa por parcela y grupo (riego, helada,
 *   calor, lluvia, ciclo, hidrogel). El tipo deja de ser ENUM para poder
 *   agregar tipos nuevos (invernadero) sin migrar de nuevo.
 */
return new class extends Migration
{
    private const GRUPOS = [
        'riego_urgente' => 'riego',
        'riego_hoy' => 'riego',
        'riego_proximo' => 'riego',
        'helada' => 'helada',
        'calor_extremo' => 'calor',
        'lluvia_proxima' => 'lluvia',
        'renovar_hidrogel' => 'hidrogel',
        'fin_ciclo_proximo' => 'ciclo',
        'ciclo_completado' => 'ciclo',
    ];

    private const ICONOS = [
        'riego' => 'agua',
        'helada' => 'helada',
        'calor' => 'calor',
        'lluvia' => 'lluvia',
        'hidrogel' => 'hidrogel',
        'ciclo' => 'ciclo',
    ];

    public function up(): void
    {
        Schema::table('clima_historial', function (Blueprint $table) {
            $table->decimal('et0_mm', 5, 2)->nullable()->after('lluvia_24h')
                ->comment('Evapotranspiracion de referencia del dia (mm)');
            $table->decimal('lluvia_72h_mm', 6, 1)->default(0)->after('et0_mm')
                ->comment('Lluvia real de los ultimos 3 dias (mm)');
            $table->decimal('temp_min_prevista', 5, 1)->nullable()->after('lluvia_72h_mm')
                ->comment('Minima prevista en las proximas 24 h');
            $table->decimal('temp_min_suelo', 5, 1)->nullable()->after('temp_min_prevista')
                ->comment('Minima estimada a nivel de suelo (helada radiativa)');
            $table->decimal('temp_max_prevista', 5, 1)->nullable()->after('temp_min_suelo')
                ->comment('Maxima prevista en las proximas 24 h');
            $table->timestamp('hora_temp_min')->nullable()->after('temp_max_prevista');
            $table->timestamp('lluvia_inicio')->nullable()->after('hora_temp_min');
            $table->timestamp('lluvia_fin')->nullable()->after('lluvia_inicio');
            $table->integer('elevacion')->nullable()->after('lluvia_fin')->comment('msnm');
            $table->string('fuente', 20)->default('open-meteo')->after('elevacion');
            $table->boolean('desactualizado')->default(false)->after('fuente');
        });

        Schema::table('clima_historial', function (Blueprint $table) {
            $table->string('icono_codigo', 20)->nullable()->change();
        });

        Schema::table('alertas', function (Blueprint $table) {
            $table->string('grupo', 20)->default('riego')->after('parcela_id');
            $table->timestamp('actualizada_en')->nullable()->after('fecha_notificacion');
            $table->string('archivada_por', 15)->nullable()->after('archivada_en')
                ->comment('usuario | sistema | riego');
            $table->index(['parcela_id', 'grupo', 'archivada'], 'idx_alerta_parcela_grupo');
        });

        Schema::table('alertas', function (Blueprint $table) {
            $table->string('tipo', 40)->default('riego_hoy')->change();
            $table->string('icono', 20)->default('alerta')->change();
        });

        // Codigos de icono de OpenWeather (01d, 10n...) a los iconos locales nuevos.
        $iconos = [
            '01' => 'sol', '02' => 'parcial', '03' => 'parcial', '04' => 'nublado',
            '09' => 'llovizna', '10' => 'lluvia', '11' => 'tormenta', '13' => 'nieve', '50' => 'niebla',
        ];
        foreach ($iconos as $codigo => $nuevo) {
            DB::table('clima_historial')->where('icono_codigo', 'like', $codigo . '%')->update(['icono_codigo' => $nuevo]);
        }

        // Las recomendaciones tambien dejan de usar emojis como icono.
        Schema::table('recomendaciones', function (Blueprint $table) {
            $table->string('icono', 20)->default('consejo')->change();
        });

        DB::table('recomendaciones')->update(['icono' => 'consejo']);
        DB::table('recomendaciones')->where('categoria', 'riego')->update(['icono' => 'agua']);
        DB::table('recomendaciones')->where('categoria', 'hidrogel')->update(['icono' => 'hidrogel']);
        DB::table('recomendaciones')->where('categoria', 'cosecha')->update(['icono' => 'ciclo']);

        $this->migrarAlertasExistentes();
    }

    private function migrarAlertasExistentes(): void
    {
        foreach (self::GRUPOS as $tipo => $grupo) {
            DB::table('alertas')->where('tipo', $tipo)->update([
                'grupo' => $grupo,
                'icono' => self::ICONOS[$grupo],
            ]);
        }

        // Dejar una sola alerta activa por parcela y grupo (la mas urgente).
        $activas = DB::table('alertas')
            ->where('archivada', false)
            ->orderByDesc('puntaje_urgencia')
            ->orderByDesc('id')
            ->get(['id', 'parcela_id', 'grupo']);

        $vistas = [];
        $duplicadas = [];

        foreach ($activas as $a) {
            $clave = $a->parcela_id . ':' . $a->grupo;
            if (isset($vistas[$clave])) {
                $duplicadas[] = $a->id;
            } else {
                $vistas[$clave] = true;
            }
        }

        foreach (array_chunk($duplicadas, 200) as $lote) {
            DB::table('alertas')->whereIn('id', $lote)->update([
                'leida' => true,
                'leida_en' => now(),
                'archivada' => true,
                'archivada_en' => now(),
                'archivada_por' => 'sistema',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->dropIndex('idx_alerta_parcela_grupo');
            $table->dropColumn(['grupo', 'actualizada_en', 'archivada_por']);
        });

        Schema::table('clima_historial', function (Blueprint $table) {
            $table->dropColumn([
                'et0_mm', 'lluvia_72h_mm', 'temp_min_prevista', 'temp_min_suelo',
                'temp_max_prevista', 'hora_temp_min', 'lluvia_inicio', 'lluvia_fin',
                'elevacion', 'fuente', 'desactualizado',
            ]);
        });
    }
};
