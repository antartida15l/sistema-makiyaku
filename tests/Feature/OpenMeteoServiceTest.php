<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Models\ClimaHistorial;
use App\Services\ClimaService;
use App\Services\MonitoreoService;
use App\Services\OpenMeteoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

class OpenMeteoServiceTest extends TestCase
{
    use RefreshDatabase, CreaEscenarios;

    public function test_normaliza_el_clima_actual_al_formato_del_sistema(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->respuestaOpenMeteo(['temp' => 13.4]))]);

        $c = app(OpenMeteoService::class)->getCurrentWeatherByCoordinates(-14.88, -70.58, 'Ayaviri');

        $this->assertSame('Ayaviri', $c['name']);
        $this->assertEquals(13.4, $c['temp']);
        $this->assertSame(52, $c['humidity']);
        $this->assertSame('Clear', $c['weather_main']);
        $this->assertSame('Mayormente despejado', $c['weather_description']);
        $this->assertSame('S', $c['wind_direction']); // 180 grados
        $this->assertStringEndsWith('img/clima/sol.svg', $c['icon_url']);
        $this->assertSame(3928, $c['elevation']);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $c['sunrise_formatted']);
    }

    public function test_el_pronostico_viene_en_bloques_de_3_horas_alineados(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->respuestaOpenMeteo())]);

        $f = app(OpenMeteoService::class)->getForecastByCoordinates(-14.88, -70.58);

        $this->assertGreaterThanOrEqual(28, count($f['list']));
        foreach (array_slice($f['list'], 0, 10) as $b) {
            $this->assertSame(0, (int) \Carbon\Carbon::createFromTimestamp($b['dt'], 'America/Lima')->format('G') % 3, 'cada bloque empieza en hora multiplo de 3');
            $this->assertArrayHasKey('pop', $b);
            $this->assertArrayHasKey('rain_3h', $b);
            $this->assertArrayHasKey('icon_url', $b);
        }
    }

    public function test_calcula_datos_agricolas_helada_lluvia_y_et0(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->respuestaOpenMeteo([
            'noche' => -3.0, 'dia' => 18.0, 'lluvia_pron' => 16.0, 'prob' => 85, 'lluvia_pasada' => 6.0, 'nubes' => 5, 'viento' => 1.0,
        ]))]);

        $p = app(OpenMeteoService::class)->getPaqueteAgricola(-14.88, -70.58);
        $r = $p['resumen'];

        $this->assertEqualsWithDelta(-3.0, $r['temp_min_24h'], 0.6);
        // noche despejada y sin viento: el suelo esta 2 C mas frio que el aire
        $this->assertEqualsWithDelta($r['temp_min_24h'] - 2.0, $r['temp_min_suelo'], 0.05);
        $this->assertEqualsWithDelta(16.0, $r['lluvia_24h_mm'], 0.5);
        $this->assertSame(85, $r['prob_lluvia_24h']);
        $this->assertEqualsWithDelta(6.0, $r['lluvia_pasada_72h_mm'], 0.6);
        $this->assertEquals(4.2, $r['et0_hoy_mm']);
        $this->assertNotNull($r['lluvia_inicio_ts']);
    }

    public function test_sin_internet_devuelve_el_ultimo_dato_marcado_como_desactualizado(): void
    {
        // primera consulta bien; despues la red falla
        Http::fake(['api.open-meteo.com/*' => Http::sequence()
            ->push($this->respuestaOpenMeteo(['temp' => 9.0]))
            ->whenEmpty(Http::response('', 500))]);
        $servicio = app(OpenMeteoService::class);
        $servicio->getCurrentWeatherByCoordinates(-14.88, -70.58);

        // se vence la cache corta pero queda el respaldo
        Cache::forget('om:bundle:-14.88:-70.58');

        $c = $servicio->getCurrentWeatherByCoordinates(-14.88, -70.58);

        $this->assertNotNull($c);
        $this->assertTrue($c['desactualizado']);
        $this->assertEquals(9.0, $c['temp']);
    }

    public function test_sin_datos_previos_y_sin_internet_devuelve_null(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response('', 500)]);

        $this->assertNull(app(OpenMeteoService::class)->getCurrentWeatherByCoordinates(10.1, 10.1));
    }

    public function test_busca_lugares_con_el_geocodificador_de_open_meteo(): void
    {
        Http::fake(['geocoding-api.open-meteo.com/*' => Http::response(['results' => [[
            'name' => 'Puno', 'latitude' => -15.84, 'longitude' => -70.02, 'country_code' => 'PE',
            'country' => 'Peru', 'admin1' => 'Puno', 'elevation' => 3830,
        ]]])]);

        $r = app(OpenMeteoService::class)->buscarLugares('Puno', 5, 'PE');

        $this->assertSame('Puno, Puno, Peru', $r[0]['display_name']);
        $this->assertEquals(-15.84, $r[0]['lat']);
    }

    public function test_clima_service_guarda_el_registro_con_los_campos_correctos(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->respuestaOpenMeteo(['noche' => -1.0]))]);
        $parcela = $this->crearParcela();

        $registro = app(ClimaService::class)->actualizarClimaParcela($parcela, true);

        $this->assertInstanceOf(ClimaHistorial::class, $registro);
        $this->assertSame('open-meteo', $registro->fuente);
        $this->assertNotNull($registro->temp_min_prevista);
        $this->assertNotNull($registro->hora_temp_min);
        $this->assertEquals(4.2, (float) $registro->et0_mm);
        $this->assertSame('Mayormente despejado', $registro->descripcion);
        $this->assertSame('sol', $registro->icono_codigo);
    }

    public function test_no_se_vuelve_a_consultar_si_el_registro_es_reciente(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->respuestaOpenMeteo())]);
        $parcela = $this->crearParcela();
        $servicio = app(ClimaService::class);

        $servicio->actualizarClimaParcela($parcela, true);
        $servicio->actualizarClimaParcela($parcela);
        $servicio->actualizarClimaParcela($parcela);

        $this->assertSame(1, ClimaHistorial::where('parcela_id', $parcela->id)->count());
    }

    public function test_monitoreo_actualiza_clima_y_genera_alertas_en_un_solo_paso(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->respuestaOpenMeteo([
            'noche' => -4.0, 'nubes' => 0, 'viento' => 0.5,
        ]))]);
        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(7)]);

        $nuevas = app(MonitoreoService::class)->monitorearParcela($parcela, true);

        $this->assertGreaterThanOrEqual(2, $nuevas); // helada + riego
        $grupos = Alerta::where('parcela_id', $parcela->id)->pluck('grupo')->all();
        $this->assertContains('helada', $grupos);
        $this->assertContains('riego', $grupos);
    }

    public function test_el_comando_de_clima_funciona_de_punta_a_punta(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->respuestaOpenMeteo())]);
        $parcela = $this->crearParcela();

        $this->artisan('parcelas:actualizar-clima', ['--parcela' => $parcela->id, '--force' => true])
            ->assertSuccessful();

        $this->assertSame(1, ClimaHistorial::where('parcela_id', $parcela->id)->count());
    }

    public function test_refresco_perezoso_solo_corre_una_vez_cada_15_minutos(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->respuestaOpenMeteo())]);
        $parcela = $this->crearParcela();
        $monitoreo = app(MonitoreoService::class);

        $monitoreo->refrescarSiHaceFalta($parcela->user_id);
        $monitoreo->refrescarSiHaceFalta($parcela->user_id);

        Http::assertSentCount(1);
    }
}
