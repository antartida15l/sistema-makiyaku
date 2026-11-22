<?php

namespace App\Console\Commands;

use App\Models\Parcela;
use App\Services\MonitoreoService;
use Illuminate\Console\Command;

class ActualizarClimaParcelasCommand extends Command
{
    protected $signature = 'parcelas:actualizar-clima
                            {--parcela= : ID de una parcela especifica}
                            {--force : Pedir datos nuevos aunque el ultimo registro sea reciente}
                            {--sin-alertas : Solo actualizar clima, sin recalcular alertas}';

    protected $description = 'Actualiza el clima (Open-Meteo) de las parcelas activas y recalcula sus alertas';

    public function handle(MonitoreoService $monitoreo): int
    {
        $consulta = Parcela::activas()->with(['ubicacion', 'cultivo']);

        if ($id = $this->option('parcela')) {
            $consulta = Parcela::where('id', $id)->with(['ubicacion', 'cultivo']);
        }

        $parcelas = $consulta->get();

        if ($parcelas->isEmpty()) {
            $this->warn('No hay parcelas activas para actualizar.');

            return self::SUCCESS;
        }

        $this->info("Procesando {$parcelas->count()} parcela(s)...");

        $ok = 0;
        $fallidas = 0;
        $alertasNuevas = 0;

        foreach ($parcelas as $parcela) {
            if (!$parcela->tiene_gps) {
                $this->line("  Parcela #{$parcela->id} ({$parcela->nombre}): sin coordenadas, se omite.");
                $fallidas++;

                continue;
            }

            try {
                if ($this->option('sin-alertas')) {
                    $registro = app(\App\Services\ClimaService::class)
                        ->actualizarClimaParcela($parcela, (bool) $this->option('force'));
                } else {
                    $alertasNuevas += $monitoreo->monitorearParcela($parcela, (bool) $this->option('force'));
                    $registro = $parcela->fresh('climaActual')->climaActual;
                }

                if ($registro) {
                    $this->line("  Parcela #{$parcela->id} ({$parcela->nombre}): {$registro->temperatura} C, {$registro->descripcion}.");
                    $ok++;
                } else {
                    $this->warn("  Parcela #{$parcela->id} ({$parcela->nombre}): sin datos nuevos (revisa tu conexion).");
                    $fallidas++;
                }
            } catch (\Throwable $e) {
                $this->error("  Parcela #{$parcela->id}: {$e->getMessage()}");
                logger()->error("Error actualizando clima de la parcela {$parcela->id}: {$e->getMessage()}");
                $fallidas++;
            }
        }

        $this->newLine();
        $this->info("Listo. Actualizadas: {$ok}. Con problemas: {$fallidas}. Alertas nuevas o agravadas: {$alertasNuevas}.");

        return self::SUCCESS;
    }
}
