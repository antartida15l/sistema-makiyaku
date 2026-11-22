<?php

namespace App\Console\Commands;

use App\Models\Parcela;
use App\Services\AlertaService;
use Illuminate\Console\Command;

class GenerarAlertasCommand extends Command
{
    protected $signature = 'alertas:generar {--parcela= : ID de una parcela especifica}';

    protected $description = 'Recalcula las alertas con el clima ya guardado (sin consultar internet)';

    public function handle(AlertaService $alertas): int
    {
        $consulta = Parcela::activas()->with(['cultivo', 'ubicacion', 'climaActual']);

        if ($id = $this->option('parcela')) {
            $consulta = Parcela::where('id', $id)->with(['cultivo', 'ubicacion', 'climaActual']);
        }

        $total = 0;

        foreach ($consulta->get() as $parcela) {
            $total += count($alertas->generarAlertasParcela($parcela));
        }

        $this->info("Alertas nuevas o agravadas: {$total}.");

        return self::SUCCESS;
    }
}
