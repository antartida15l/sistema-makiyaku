<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Models\Parcela;
use App\Services\AlertaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreaEscenarios;
use Tests\Support\OpenMeteoFalso;
use Tests\TestCase;

/**
 * Recorre las pantallas para detectar errores de vistas y controladores.
 */
class PaginasTest extends TestCase
{
    use RefreshDatabase, CreaEscenarios;

    private const EMOJI = '/(?![\x{2630}\x{2715}])[\x{1F000}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u';

    private function escenario(): array
    {
        OpenMeteoFalso::activar();

        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(8), 'latitud' => -14.88, 'longitud' => -70.58]);
        $this->crearClima($parcela, ['temp_min_suelo' => -4.5, 'temp_min_prevista' => -2.5, 'hora_temp_min' => now()->addHours(9)]);
        app(AlertaService::class)->generarAlertasParcela($parcela->fresh());

        return [$parcela, $parcela->user];
    }

    public function test_pantallas_principales_cargan_y_no_tienen_emojis(): void
    {
        [$parcela, $user] = $this->escenario();
        $this->actingAs($user);

        $rutas = [
            '/dashboard', '/alertas', '/alertas/archivadas', '/parcelas', "/parcelas/{$parcela->id}", "/parcelas/{$parcela->id}/edit",
            '/parcelas/create', '/parcelas/papelera/ver', "/parcelas/{$parcela->id}/recomendaciones", '/recomendaciones', '/cultivos',
            "/cultivos/{$parcela->idCultivo}", "/cultivos/{$parcela->idCultivo}/edit", '/cultivos/create',
            '/clima', "/clima?parcela={$parcela->id}", '/clima?ciudad=Puno', '/clima?lat=-14.88&lon=-70.58',
            '/reportes', '/profile',
        ];

        foreach ($rutas as $ruta) {
            $r = $this->get($ruta);
            $r->assertOk();
            preg_match(self::EMOJI, $r->getContent(), $m, PREG_OFFSET_CAPTURE);
            $contexto = $m ? mb_substr(substr($r->getContent(), max(0, $m[0][1] - 60), 140), 0, 140) : '';
            $this->assertEmpty($m, "Hay emojis en {$ruta}: ...{$contexto}...");
        }
    }

    public function test_paginas_publicas_y_de_autenticacion(): void
    {
        foreach (['/', '/login', '/register', '/forgot-password'] as $ruta) {
            $this->get($ruta)->assertOk();
        }
    }

    public function test_la_presentacion_aparece_al_entrar_y_se_puede_apagar(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee('id="intro"', false);
        $this->get('/dashboard')->assertOk()->assertDontSee('id="intro"', false); // solo una vez por entrada

        $this->actingAs($user)->postJson('/preferencias', ['intro' => false])->assertOk();
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->get('/dashboard')->assertDontSee('id="intro"', false);
    }

    public function test_preferencias_se_guardan_y_validan(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)->postJson('/preferencias', ['tema' => 'dark', 'sonido' => false, 'volumen' => 0.3])->assertOk()->assertJsonPath('preferencias.tema', 'dark');
        $this->assertSame('dark', $user->fresh()->pref('tema'));
        $this->assertFalse($user->fresh()->pref('sonido'));
        $this->postJson('/preferencias', ['tema' => 'rosa'])->assertStatus(422);
        $this->get('/dashboard')->assertSee('data-theme="dark"', false);
    }

    public function test_pantalla_de_alertas_muestra_la_cantidad_de_agua(): void
    {
        [$parcela, $user] = $this->escenario();

        $this->actingAs($user)->get('/alertas')->assertOk()->assertSee('Agua recomendada')->assertSee('Riesgo de helada');
    }

    public function test_endpoints_ajax_de_alertas_y_dashboard(): void
    {
        [$parcela, $user] = $this->escenario();
        $this->actingAs($user);

        $this->getJson('/alertas/contador')->assertOk()->assertJsonPath('success', true);
        $recientes = $this->getJson('/alertas/recientes')->assertOk();
        $this->assertGreaterThan(0, $recientes->json('total'));
        $this->assertSame('agua', collect($recientes->json('alertas'))->firstWhere('grupo', 'riego')['icono']);

        $id = Alerta::where('parcela_id', $parcela->id)->where('grupo', 'riego')->value('id');
        $this->postJson("/alertas/{$id}/marcar-leida")->assertOk();
        $this->assertSame('usuario', Alerta::find($id)->archivada_por);
        $this->post('/alertas/refrescar')->assertRedirect('/alertas');

        $p = $this->getJson('/dashboard/pronostico?parcela_id=' . $parcela->id)->assertOk()->assertJsonPath('success', true);
        $this->assertCount(48, $p->json('data.temp'));
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

    public function test_registrar_riego_con_fecha_pasada_no_pisa_uno_mas_reciente(): void
    {
        [$parcela, $user] = $this->escenario();
        $parcela->update(['ultimo_riego' => now()->subDay()]);

        $this->actingAs($user)->post("/parcelas/{$parcela->id}/riego", ['cantidad_agua' => 500, 'fecha' => now()->subDays(10)->toDateString()])->assertRedirect();

        $this->assertTrue($parcela->fresh()->ultimo_riego->isSameDay(now()->subDay()));
        $this->assertSame(1, $parcela->eventosRiego()->count());
    }

    public function test_actualizar_clima_manual_trae_datos_y_alertas(): void
    {
        [$parcela, $user] = $this->escenario();

        $this->actingAs($user)->post("/parcelas/{$parcela->id}/actualizar-clima")->assertRedirect();

        $this->assertGreaterThanOrEqual(2, $parcela->climaHistorial()->count());
    }

    public function test_crear_parcela_guarda_gps_propio_y_calcula_con_campos_reales(): void
    {
        [$parcela, $user] = $this->escenario();
        $cultivo = $this->crearCultivo();

        $this->actingAs($user)->post('/parcelas', [
            'nombreParcela' => 'Lote Nuevo', 'idCultivo' => $cultivo->idCultivo, 'area' => 200,
            'region' => 'Puno', 'provincia' => 'Melgar', 'distrito' => 'Ayaviri', 'latitud' => '-14,8901', 'longitud' => '-70.5702',
        ])->assertRedirect();

        $nueva = Parcela::where('nombre', 'Lote Nuevo')->firstOrFail();
        $this->assertEqualsWithDelta(-14.8901, $nueva->latitud, 0.0001);
        $this->assertTrue($nueva->gps_propio);
        $this->assertGreaterThanOrEqual(5, $nueva->frecuencia_riego_dias);
        $this->assertGreaterThan(0, (float) $nueva->litros_por_riego);
        $this->assertEquals($cultivo->humedadOptimaMin, $nueva->humedad_minima);
        $this->assertGreaterThan(0, $nueva->climaHistorial()->count(), 'Debe traer el clima al crearla');
    }

    public function test_validacion_de_parcela_en_espanol(): void
    {
        [$parcela, $user] = $this->escenario();

        $this->actingAs($user)->post('/parcelas', ['area' => -3])->assertSessionHasErrors(['nombreParcela', 'area', 'idCultivo', 'region']);
        $r = $this->actingAs($user)->post('/parcelas', ['area' => 0])->baseResponse->getSession()->get('errors')->first('area');
        $this->assertStringContainsString('mayor que cero', $r);
    }

    public function test_papelera_restaura_y_elimina(): void
    {
        [$parcela, $user] = $this->escenario();
        $this->actingAs($user);

        $this->delete("/parcelas/{$parcela->id}")->assertRedirect('/parcelas');
        $this->assertSoftDeleted($parcela);
        $this->get('/parcelas/papelera/ver')->assertOk()->assertSee($parcela->nombre);
        $this->patch("/parcelas/{$parcela->id}/restaurar")->assertRedirect();
        $this->assertNotSoftDeleted($parcela);
    }

    public function test_no_se_puede_ver_la_parcela_de_otro_usuario(): void
    {
        [$parcela] = $this->escenario();
        $otro = \App\Models\User::factory()->create();

        $this->actingAs($otro)->get("/parcelas/{$parcela->id}")->assertForbidden();
    }

    public function test_reportes_en_pantalla_y_csv(): void
    {
        [$parcela, $user] = $this->escenario();
        $parcela->registrarRiego(1200);
        $this->actingAs($user);

        foreach (['general', 'agua', 'alertas', 'clima', 'ciclo'] as $tipo) {
            $this->get("/reportes/ver?tipo={$tipo}")->assertOk();
        }

        $csv = $this->get('/reportes/csv?tipo=agua');
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('1200', $csv->streamedContent());
        $this->get('/reportes/ver?tipo=nada')->assertSessionHasErrors('tipo');
    }

    public function test_recomendaciones_se_generan_y_se_cierran(): void
    {
        [$parcela, $user] = $this->escenario();
        $this->actingAs($user)->get('/recomendaciones')->assertOk()->assertSee('Proteger el cultivo del frío');

        $rec = \App\Models\Recomendacion::where('parcela_id', $parcela->id)->activas()->firstOrFail();
        $this->post("/recomendaciones/{$rec->id}/aplicar")->assertRedirect();
        $this->assertTrue($rec->fresh()->aplicada);
    }

    public function test_cultivos_filtros_y_borrado_protegido(): void
    {
        [$parcela, $user] = $this->escenario();
        $this->actingAs($user);

        $this->get('/cultivos?q=papa')->assertOk();
        $this->get('/cultivos?q=zzzz')->assertOk()->assertSee('Ningún cultivo coincide');
        $this->delete("/cultivos/{$parcela->idCultivo}")->assertRedirect()->assertSessionHas('error');
    }

    public function test_clima_completo_trae_todas_las_secciones(): void
    {
        [$parcela, $user] = $this->escenario();

        $r = $this->actingAs($user)->get("/clima?parcela={$parcela->id}")->assertOk();
        foreach (['Por hora', '14 días', 'Agricultura', 'Atmósfera', 'Historial', 'Calidad del aire', 'Humedad del suelo', 'Mejor hora para regar', 'Balance de agua'] as $t) {
            $r->assertSee($t);
        }

        $json = $this->getJson('/clima/datos?lat=-14.88&lon=-70.58')->assertOk();
        $this->assertCount(72, $json->json('horas'));
        $this->assertCount(17, $json->json('dias'));
        $this->assertNotNull($json->json('aire.aqi'));
        $this->assertNotNull($json->json('historial.lluvia_30d'));
    }

    public function test_clima_sin_internet_muestra_aviso_amable(): void
    {
        Http::fake(['*' => Http::response('', 500)]);
        $parcela = $this->crearParcela(['latitud' => -14.88, 'longitud' => -70.58]);

        $this->actingAs($parcela->user)->get("/clima?parcela={$parcela->id}")->assertOk()->assertSee('No pudimos obtener el clima');
    }
}
