<?php

namespace App\Console\Commands;

use App\Models\Parcela;
use App\Services\RecomendacionService;
use Illuminate\Console\Command;

class GenerarRecomendacionesExistentes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recomendaciones:generar-existentes 
                            {--user_id= : ID del usuario específico}
                            {--force : Regenerar incluso si ya existen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera recomendaciones para todas las parcelas existentes';

    /**
     * Execute the console command.
     */
    public function handle(RecomendacionService $service)
    {
        $this->info(' generación de recomendaciones');
        $this->newLine();

        // Filtrar por usuario si se especifica
        $query = Parcela::with(['cultivo', 'ubicacion', 'climaActual']);
        
        if ($userId = $this->option('user_id')) {
            $query->where('user_id', $userId);
        }

        $parcelas = $query->get();

        if ($parcelas->isEmpty()) {
            $this->warn('  No se encontraron parcelas');
            return 0;
        }

        $this->info("📊 Total de parcelas: {$parcelas->count()}");
        $this->newLine();

        $totalGeneradas = 0;
        $bar = $this->output->createProgressBar($parcelas->count());
        $bar->start();

        foreach ($parcelas as $parcela) {
            try {
                // Si hay force, desactivar recomendaciones existentes
                if ($this->option('force')) {
                    $parcela->recomendaciones()->activas()->update(['activa' => false]);
                }

                $generadas = $service->generarRecomendacionesParcela($parcela);
                $totalGeneradas += $generadas;

                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\n Error en parcela {$parcela->id}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info(" Proceso completado");
        $this->info(" Total de recomendaciones generadas: {$totalGeneradas}");
        $this->newLine();

        // Mostrar resumen por parcela
        $this->table(
            ['Parcela', 'Usuario', 'Cultivo', 'Recomendaciones Activas'],
            $parcelas->map(function ($parcela) {
                return [
                    $parcela->nombre,
                    $parcela->user->name ?? 'N/A',
                    $parcela->cultivo->nombreCultivo ?? 'Sin cultivo',
                    $parcela->recomendaciones()->activas()->count(),
                ];
            })
        );

        return 0;
    }
}