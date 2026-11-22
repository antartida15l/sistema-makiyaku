<?php

namespace App\Console\Commands;

use App\Services\AlertaService;
use Illuminate\Console\Command;

class LimpiarAlertasCommand extends Command
{
    protected $signature = 'alertas:limpiar';

    protected $description = 'Archiva alertas vencidas y borra el historial de alertas de mas de 30 dias';

    public function handle(): int
    {
        $vencidas = AlertaService::limpiarAlertasExpiradas();
        $borradas = AlertaService::eliminarHistorialAntiguo();

        $this->info("Archivadas: {$vencidas}. Eliminadas del historial: {$borradas}.");

        return self::SUCCESS;
    }
}
