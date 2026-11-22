<?php

namespace Tests\Feature;

use App\Services\RiegoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreaEscenarios;
use Tests\TestCase;

class RiegoServiceTest extends TestCase
{
    use RefreshDatabase, CreaEscenarios;

    public function test_litros_se_calculan_por_area_y_consumo_del_cultivo(): void
    {
        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(6)]);
        $clima = $this->crearClima($parcela, ['et0_mm' => 4.0]); // igual a la referencia de Sierra

        $r = app(RiegoService::class)->calcular($parcela->fresh('cultivo', 'ubicacion'), $clima);

        // 3 L/m2/dia x 6 dias = 18 L/m2 x 100 m2
        $this->assertEquals(1800, $r['litros_total']);
        $this->assertEquals(18.0, $r['litros_m2']);
        $this->assertEquals(90.0, $r['baldes']);
    }

    public function test_la_cantidad_tiene_tope_aunque_pasen_muchos_dias(): void
    {
        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(40)]);
        $clima = $this->crearClima($parcela, ['et0_mm' => 4.0]);

        $r = app(RiegoService::class)->calcular($parcela->fresh('cultivo', 'ubicacion'), $clima);

        // frecuencia (6) + 2 dias como maximo = 8 dias x 3 L = 24 L/m2
        $this->assertLessThanOrEqual(24.0, $r['litros_m2']);
        $this->assertEquals(2400, $r['litros_total']);
    }

    public function test_la_lluvia_reciente_y_el_hidrogel_reducen_el_agua(): void
    {
        $servicio = app(RiegoService::class);
        $parcela = $this->crearParcela(['ultimo_riego' => now()->subDays(6)]);
        $parcela = $parcela->fresh('cultivo', 'ubicacion');

        $seco = $servicio->calcular($parcela, $this->crearClima($parcela, ['et0_mm' => 4.0]))['litros_total'];
        $lluvia = $servicio->calcular($parcela, $this->crearClima($parcela, ['et0_mm' => 4.0, 'lluvia_72h_mm' => 8]))['litros_total'];

        $parcela->update(['tieneHidrogelAplicado' => true]);
        $hidrogel = $servicio->calcular($parcela->fresh('cultivo', 'ubicacion'), $this->crearClima($parcela, ['et0_mm' => 4.0]))['litros_total'];

        $this->assertLessThan($seco, $lluvia);
        $this->assertLessThan($seco, $hidrogel);
    }

    public function test_dia_muy_seco_sube_el_consumo_y_dia_humedo_lo_baja(): void
    {
        $servicio = app(RiegoService::class);
        $parcela = $this->crearParcela()->fresh('cultivo', 'ubicacion');

        $normal = $servicio->consumoDiarioPorM2($parcela, $this->crearClima($parcela, ['et0_mm' => 4.0]));
        $seco = $servicio->consumoDiarioPorM2($parcela, $this->crearClima($parcela, ['et0_mm' => 5.6]));
        $fresco = $servicio->consumoDiarioPorM2($parcela, $this->crearClima($parcela, ['et0_mm' => 2.0]));

        $this->assertEquals(3.0, $normal);
        $this->assertGreaterThan($normal, $seco);
        $this->assertLessThan($normal, $fresco);
        $this->assertEquals(4.2, $seco);   // limite 1.4
        $this->assertEquals(2.1, $fresco); // limite 0.7
    }

    public function test_texto_de_cantidad_es_claro_para_el_agricultor(): void
    {
        $texto = app(RiegoService::class)->textoCantidad([
            'litros_total' => 180, 'litros_m2' => 9.0, 'baldes' => 9.0,
        ]);

        $this->assertSame('180 L en total (9 L por m2, unos 9 baldes de 20 L)', $texto);

        // parcela grande: se expresa en metros cubicos, no en cientos de baldes
        $grande = app(RiegoService::class)->textoCantidad([
            'litros_total' => 14610, 'litros_m2' => 36.5, 'baldes' => 730.5,
        ]);

        $this->assertSame('14,6 m3 (14.610 L) en total (36,5 L por m2)', $grande);
    }
}
