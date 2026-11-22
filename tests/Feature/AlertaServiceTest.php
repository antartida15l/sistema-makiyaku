<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Services\AlertaService;
use App\Services\RiegoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

class AlertaServiceTest extends TestCase
{
    use RefreshDatabase, CreaEscenarios;

    private function generar($parcela): array
    {
        return app(AlertaService::class)->generarAlertasParcela($parcela->fresh());
    }

    private function activas($parcela, ?string $grupo = null)
    {
        $q = Alerta::where('parcela_id', $parcela->id)->where('archivada', false);

        return $grupo ? $q->where('grupo', $grupo)->get() : $q->get();
    }

    public function test_sin_necesidad_de_riego_no_hay_alertas(): void
    {
        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDay()]);
        $this->crearClima($parcela);

        $this->generar($parcela);

        $this->assertCount(0, $this->activas($parcela));
    }

    public function test_el_riego_sube_de_nivel_sin_acumular_alertas(): void
    {
        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(5)]); // casi toca (frecuencia 6)
        $this->crearClima($parcela);

        $this->generar($parcela);
        $primera = $this->activas($parcela, 'riego');
        $this->assertCount(1, $primera);
        $this->assertSame('riego_proximo', $primera->first()->tipo);
        $this->assertSame('medio', $primera->first()->nivel);

        // pasan los dias y ahora toca hoy
        $parcela->update(['ultimo_riego' => now()->subDays(6)]);
        $this->generar($parcela);

        $riego = $this->activas($parcela, 'riego');
        $this->assertCount(1, $riego, 'Debe seguir habiendo una sola alerta de riego');
        $this->assertSame($primera->first()->id, $riego->first()->id, 'Se actualiza la misma alerta');
        $this->assertSame('riego_hoy', $riego->first()->tipo);
        $this->assertSame('alto', $riego->first()->nivel);

        // y si se pasa del limite critico
        $parcela->update(['ultimo_riego' => now()->subDays(13)]);
        $this->generar($parcela);

        $riego = $this->activas($parcela, 'riego');
        $this->assertCount(1, $riego);
        $this->assertSame('riego_urgente', $riego->first()->tipo);
        $this->assertSame('critico', $riego->first()->nivel);
        $this->assertSame('critica', $riego->first()->prioridad);
    }

    public function test_nivel_prioridad_y_puntaje_siempre_coinciden(): void
    {
        foreach ([40, 64, 65, 84, 85, 100] as $puntaje) {
            $nivel = Alerta::nivelPorPuntaje($puntaje);
            $esperada = match (true) {
                $puntaje >= 85 => ['critico', 'critica'],
                $puntaje >= 65 => ['alto', 'alta'],
                default => ['medio', 'media'],
            };
            $this->assertSame($esperada, [$nivel, Alerta::prioridadPorNivel($nivel)], "puntaje {$puntaje}");
        }
    }

    public function test_si_va_a_llover_no_se_pide_regar_y_se_avisa_de_la_lluvia(): void
    {
        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(6)]);
        $this->crearClima($parcela, ['probabilidad_lluvia_24h' => 85, 'lluvia_24h' => 12]);

        $this->generar($parcela);

        $this->assertCount(0, $this->activas($parcela, 'riego'));
        $lluvia = $this->activas($parcela, 'lluvia');
        $this->assertCount(1, $lluvia);
        $this->assertSame('info', $lluvia->first()->nivel);
        $this->assertStringContainsString('pospón', mb_strtolower($lluvia->first()->titulo));
    }

    public function test_riego_critico_manda_sobre_la_lluvia_suave_y_no_se_contradicen(): void
    {
        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(14)]);
        $this->crearClima($parcela, ['probabilidad_lluvia_24h' => 80, 'lluvia_24h' => 8]);

        $this->generar($parcela);

        $riego = $this->activas($parcela, 'riego');
        $this->assertCount(1, $riego);
        $this->assertSame('critico', $riego->first()->nivel);
        $this->assertCount(0, $this->activas($parcela, 'lluvia'), 'No se sugiere esperar la lluvia si esta critico');
    }

    public function test_la_cantidad_de_agua_de_la_alerta_es_la_misma_que_calcula_riego_service(): void
    {
        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(7)]);
        $clima = $this->crearClima($parcela);

        $this->generar($parcela);
        $alerta = $this->activas($parcela, 'riego')->first();

        $esperado = app(RiegoService::class)->calcular($parcela->fresh('cultivo', 'ubicacion'), $clima)['litros_total'];

        $this->assertEquals($esperado, $alerta->datos_adicionales['litros_recomendados']);
        $this->assertStringContainsString(number_format($esperado, 0, ',', '.') . ' L', $alerta->mensaje);
        $this->assertStringContainsString('m³', $alerta->mensaje, 'Una parcela grande se expresa en metros cubicos');
    }

    public function test_en_una_parcela_pequena_la_cantidad_se_da_en_baldes(): void
    {
        $parcela = $this->crearParcela(['area' => 20, 'ultimo_riego' => now()->subDays(7)]);
        $this->crearClima($parcela);

        $this->generar($parcela);
        $alerta = $this->activas($parcela, 'riego')->first();

        $this->assertLessThan(1000, $alerta->datos_adicionales['litros_recomendados']);
        $this->assertStringContainsString('baldes de 20 L', $alerta->mensaje);
        $this->assertArrayHasKey('baldes', $alerta->datos_adicionales);
    }

    public function test_helada_segun_la_minima_prevista_no_segun_la_temperatura_actual(): void
    {
        $parcela = $this->crearParcela();
        // Ahora hace 12 C, pero esta noche baja a -4 a nivel de suelo (limite del cultivo: -2)
        $this->crearClima($parcela, [
            'temperatura' => 12,
            'temp_min_prevista' => -2.5,
            'temp_min_suelo' => -4.5,
            'hora_temp_min' => now()->addHours(10),
        ]);

        $this->generar($parcela);

        $helada = $this->activas($parcela, 'helada');
        $this->assertCount(1, $helada);
        $this->assertSame('critico', $helada->first()->nivel);
        $this->assertStringContainsString('hacia las', $helada->first()->mensaje);
    }

    public function test_helada_y_calor_pueden_coexistir_en_sierra_pero_cada_una_es_unica(): void
    {
        $parcela = $this->crearParcela();
        $this->crearClima($parcela, ['temp_min_suelo' => -3, 'temp_min_prevista' => -1, 'temp_max_prevista' => 31]);

        $this->generar($parcela);
        $this->generar($parcela); // segunda corrida: no duplica

        $this->assertCount(1, $this->activas($parcela, 'helada'));
        $this->assertCount(1, $this->activas($parcela, 'calor'));
    }

    public function test_helada_con_margen_no_parpadea_gracias_a_la_histeresis(): void
    {
        $parcela = $this->crearParcela();
        $clima = $this->crearClima($parcela, ['temp_min_suelo' => 0.0, 'temp_min_prevista' => 1.5]); // margen +2 sobre -2
        $this->generar($parcela);
        $this->assertCount(1, $this->activas($parcela, 'helada'));

        // sube a margen 2.8 (> 2 pero <= 3): se mantiene
        $clima->update(['temp_min_suelo' => 0.8]);
        $this->generar($parcela);
        $this->assertCount(1, $this->activas($parcela, 'helada'));

        // supera claramente el limite: se cierra
        $clima->update(['temp_min_suelo' => 4.0]);
        $this->generar($parcela);
        $this->assertCount(0, $this->activas($parcela, 'helada'));
    }

    public function test_solo_se_vuelve_a_avisar_cuando_la_alerta_empeora(): void
    {
        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(6)]);
        $this->crearClima($parcela);

        $nuevas = $this->generar($parcela);
        $this->assertCount(1, $nuevas);

        // El usuario la lee, pero no la archiva
        $alerta = $this->activas($parcela, 'riego')->first();
        $alerta->marcarComoLeida();

        // Misma situacion: no hay aviso nuevo y sigue leida
        $this->assertCount(0, $this->generar($parcela));
        $this->assertTrue($alerta->fresh()->leida);

        // Empeora a critico: vuelve a ser nueva
        $parcela->update(['ultimo_riego' => now()->subDays(14)]);
        $cambios = $this->generar($parcela);
        $this->assertCount(1, $cambios);
        $this->assertFalse($alerta->fresh()->leida);
        $this->assertSame('critico', $alerta->fresh()->nivel);
    }

    public function test_lo_que_el_usuario_descarta_no_reaparece_salvo_que_empeore(): void
    {
        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(6)]);
        $this->crearClima($parcela);
        $this->generar($parcela);

        $this->activas($parcela, 'riego')->first()->archivar(); // por el usuario

        $this->assertCount(0, $this->generar($parcela));
        $this->assertCount(0, $this->activas($parcela, 'riego'));

        $parcela->update(['ultimo_riego' => now()->subDays(14)]); // empeora
        $this->generar($parcela);
        $this->assertCount(1, $this->activas($parcela, 'riego'));
    }

    public function test_registrar_un_riego_cierra_las_alertas_de_riego(): void
    {
        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(8)]);
        $this->crearClima($parcela);
        $this->generar($parcela);
        $this->assertCount(1, $this->activas($parcela, 'riego'));

        $parcela->registrarRiego(1500);
        app(AlertaService::class)->archivarAlertasRiegoPorRiego($parcela->fresh());
        $this->generar($parcela);

        $this->assertCount(0, $this->activas($parcela, 'riego'));
    }

    public function test_con_clima_desactualizado_no_se_inventan_alertas_climaticas(): void
    {
        $parcela = $this->crearParcela();
        $this->crearClima($parcela, [
            'temp_min_suelo' => -6,
            'fecha_registro' => now()->subHours(9),
        ]);

        $this->generar($parcela);

        $this->assertCount(0, $this->activas($parcela, 'helada'));
    }

    public function test_alertas_climaticas_vencidas_se_archivan(): void
    {
        $parcela = $this->crearParcela();
        $clima = $this->crearClima($parcela, ['temp_min_suelo' => -4, 'hora_temp_min' => now()->addHour()]);
        $this->generar($parcela);
        $alerta = $this->activas($parcela, 'helada')->first();

        $alerta->update(['fecha_expiracion' => now()->subMinute()]);
        $clima->update(['temp_min_suelo' => 8, 'temp_min_prevista' => 9]);
        $this->generar($parcela);

        $this->assertCount(0, $this->activas($parcela, 'helada'));
    }

    public function test_las_banderas_del_clima_reflejan_las_alertas(): void
    {
        $parcela = $this->crearParcela();
        $clima = $this->crearClima($parcela, ['temp_min_suelo' => -4]);

        $this->generar($parcela);

        $this->assertTrue($clima->fresh()->alerta_helada);
        $this->assertFalse($clima->fresh()->alerta_calor_extremo);
    }

    public function test_los_mensajes_no_llevan_emojis(): void
    {
        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(14)]);
        $this->crearClima($parcela, ['temp_min_suelo' => -5, 'temp_max_prevista' => 33]);
        $this->generar($parcela);

        foreach ($this->activas($parcela) as $a) {
            $this->assertDoesNotMatchRegularExpression(
                '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u',
                $a->titulo . $a->mensaje . $a->icono,
                "La alerta {$a->tipo} tiene emojis"
            );
        }
    }
}
