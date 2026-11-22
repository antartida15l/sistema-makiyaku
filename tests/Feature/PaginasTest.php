<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Services\AlertaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

/**
 * Recorre las pantallas principales para detectar errores de vistas y
 * controladores despues de cambiar el servicio de clima y el motor de alertas.
 */
class PaginasTest extends TestCase
{
    use RefreshDatabase, CreaEscenarios;

    private const EMOJI = '/(?![\x{2630}\x{2715}])[\x{1F000}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u';

    private function escenario(): array
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->respuestaOpenMeteo([
                'noche' => -3.0, 'nubes' => 0, 'viento' => 0.5, 'lluvia_pron' => 0,
            ])),
            'geocoding-api.open-meteo.com/*' => Http::response(['results' => [[
                'name' => 'Puno', 'latitude' => -15.84, 'longitude' => -70.02, 'country_code' => 'PE', 'country' => 'Peru', 'admin1' => 'Puno',
            ]]]),
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(8)]);
        $this->crearClima($parcela, ['temp_min_suelo' => -4.5, 'temp_min_prevista' => -2.5, 'hora_temp_min' => now()->addHours(9)]);
        app(AlertaService::class)->generarAlertasParcela($parcela->fresh());

        return [$parcela, $parcela->user];
    }

    public function test_pantallas_principales_cargan_y_no_tienen_emojis(): void
    {
        [$parcela, $user] = $this->escenario();
        $this->actingAs($user);

        $rutas = [
            '/dashboard',
            '/alertas',
            '/alertas/archivadas',
            '/parcelas',
            "/parcelas/{$parcela->id}",
            "/parcelas/{$parcela->id}/edit",
            '/parcelas/create',
            "/parcelas/{$parcela->id}/recomendaciones",
            '/cultivos',
            '/cultivos/create',
            '/clima?lat=-14.88&lon=-70.58',
            '/clima?ciudad=Puno',
        ];

        foreach ($rutas as $ruta) {
            $r = $this->get($ruta);
            $r->assertOk();
            preg_match(self::EMOJI, $r->getContent(), $m, PREG_OFFSET_CAPTURE);
            $contexto = $m ? mb_substr(substr($r->getContent(), max(0, $m[0][1] - 60), 140), 0, 140) : '';
            $this->assertEmpty($m, "Hay emojis en {$ruta}: ...{$contexto}...");
        }
    }

    public function test_pantalla_de_alertas_muestra_la_cantidad_de_agua(): void
    {
        [$parcela, $user] = $this->escenario();

        $this->actingAs($user)->get('/alertas')
            ->assertOk()
            ->assertSee('m3')
            ->assertSee('por m2')
            ->assertSee('Riesgo de helada');
    }

    public function test_endpoints_ajax_de_alertas(): void
    {
        [$parcela, $user] = $this->escenario();
        $this->actingAs($user);

        $this->getJson('/alertas/contador')->assertOk()->assertJsonPath('success', true);

        $recientes = $this->getJson('/alertas/recientes')->assertOk()->assertJsonPath('success', true);
        $this->assertGreaterThan(0, $recientes->json('total'));
        $this->assertSame('agua', collect($recientes->json('alertas'))->firstWhere('grupo', 'riego')['icono']);

        $id = Alerta::where('parcela_id', $parcela->id)->where('grupo', 'riego')->value('id');
        $this->postJson("/alertas/{$id}/marcar-leida")->assertOk();
        $this->assertSame('usuario', Alerta::find($id)->archivada_por);

        $this->post('/alertas/refrescar')->assertRedirect('/alertas');
    }

    public function test_endpoints_ajax_del_dashboard(): void
    {
        [$parcela, $user] = $this->escenario();
        $this->actingAs($user);

        $this->getJson('/dashboard/humedad-data')->assertOk();
        $this->getJson('/dashboard/comparativa-clima')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/dashboard/pronostico?parcela_id=' . $parcela->id)->assertOk()->assertJsonPath('success', true);
    }

    public function test_registrar_riego_usa_la_cantidad_recomendada_y_cierra_alertas(): void
    {
        [$parcela, $user] = $this->escenario();
        $this->actingAs($user);

        $this->post("/parcelas/{$parcela->id}/riego", [])->assertRedirect();

        $evento = $parcela->eventosRiego()->latest('id')->first();
        $this->assertGreaterThan(0, (float) $evento->cantidad_agua);
        $this->assertSame(0, Alerta::where('parcela_id', $parcela->id)->where('grupo', 'riego')->where('archivada', false)->count());
    }

    public function test_actualizar_clima_manual_trae_datos_y_alertas(): void
    {
        [$parcela, $user] = $this->escenario();

        $this->actingAs($user)->post("/parcelas/{$parcela->id}/actualizar-clima")->assertRedirect();

        $this->assertGreaterThanOrEqual(2, $parcela->climaHistorial()->count());
    }

    public function test_crear_parcela_calcula_frecuencia_y_litros_con_campos_reales_del_cultivo(): void
    {
        [$parcela, $user] = $this->escenario();
        $cultivo = $this->crearCultivo();

        $this->actingAs($user)->post('/parcelas', [
            'nombreParcela' => 'Lote Nuevo',
            'idCultivo' => $cultivo->idCultivo,
            'area' => 200,
            'region' => 'Sierra', 'provincia' => 'Melgar', 'distrito' => 'Ayaviri',
            'coordenadaLatitud' => -14.88, 'coordenadaLongitud' => -70.58,
        ])->assertRedirect('/parcelas');

        $nueva = \App\Models\Parcela::where('nombre', 'Lote Nuevo')->first();
        $this->assertNotNull($nueva);
        $this->assertBetween($nueva->frecuencia_riego_dias, 5, 8);
        $this->assertGreaterThan(0, (float) $nueva->litros_por_riego);
        $this->assertEquals($cultivo->humedadOptimaMin, $nueva->humedad_minima);
    }

    private function assertBetween($valor, $min, $max): void
    {
        $this->assertGreaterThanOrEqual($min, $valor);
        $this->assertLessThanOrEqual($max, $valor);
    }
}
