<?php

namespace App\Console\Commands;

use App\Models\Ubicacion;
use App\Services\GeocodingService;
use Illuminate\Console\Command;

class GeocodificarUbicaciones extends Command
{
    /**
     * @var string
     */
    protected $signature = 'ubicaciones:geocodificar 
                            {--ubicacion= : ID de ubicación específica}
                            {--force : Forzar re-geocodificación incluso si ya tiene coordenadas}
                            {--test : Modo prueba (no guarda cambios)}';

    /**
     * @var string
     */
    protected $description = 'Geocodifica ubicaciones sin coordenadas usando Nominatim (OpenStreetMap)';

    protected GeocodingService $geocodingService;

    /**
     */
    public function __construct(GeocodingService $geocodingService)
    {
        parent::__construct();
        $this->geocodingService = $geocodingService;
    }

    /**
     */
    public function handle()
    {
        $this->info(' Iniciando geocodificación de ubicaciones...');
        $this->newLine();

        // Obtener ubicaciones a procesar
        $query = Ubicacion::query();

        if ($ubicacionId = $this->option('ubicacion')) {
            $query->where('idUbicacion', $ubicacionId);
        } elseif (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('coordenadaLatitud')
                  ->orWhereNull('coordenadaLongitud');
            });
        }

        $ubicaciones = $query->get();

        if ($ubicaciones->isEmpty()) {
            $this->warn(' No hay ubicaciones para procesar');
            return Command::SUCCESS;
        }

        $this->info(" Ubicaciones a procesar: {$ubicaciones->count()}");
        $this->newLine();

        $bar = $this->output->createProgressBar($ubicaciones->count());
        $bar->start();

        $resultados = [
            'exitosas' => 0,
            'fallidas' => 0,
            'omitidas' => 0,
        ];

        foreach ($ubicaciones as $ubicacion) {
            $bar->advance();

            // Verificar si ya tiene coordenadas y no es modo force
            if (!$this->option('force') && $ubicacion->tieneCoordenadasGPS()) {
                $resultados['omitidas']++;
                continue;
            }

            try {
                $coords = $this->geocodingService->geocodificarUbicacionPeru(
                    $ubicacion->distrito,
                    $ubicacion->provincia,
                    $ubicacion->region
                );

                if ($coords && $coords['confianza'] >= 0.5) {
                    if (!$this->option('test')) {
                        $ubicacion->update([
                            'coordenadaLatitud' => $coords['lat'],
                            'coordenadaLongitud' => $coords['lon'],
                        ]);
                    }

                    $resultados['exitosas']++;
                    
                    $this->newLine();
                    $this->line(" {$ubicacion->obtenerDireccionCompleta()}");
                    $this->line("   → Coords: {$coords['lat']}, {$coords['lon']} (confianza: " . round($coords['confianza'] * 100) . "%)");
                } else {
                    $resultados['fallidas']++;
                    
                    $this->newLine();
                    $this->error(" {$ubicacion->obtenerDireccionCompleta()}");
                    $this->line("   → No se encontraron coordenadas confiables");
                }

                // Respetar rate limit de Nominatim (1 request por segundo)
                sleep(1);

            } catch (\Exception $e) {
                $resultados['fallidas']++;
                
                $this->newLine();
                $this->error(" Error en {$ubicacion->obtenerDireccionCompleta()}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Resumen final
        $this->info('═══════════════════════════════════════');
        $this->info(' RESUMEN DE GEOCODIFICACIÓN');
        $this->info('═══════════════════════════════════════');
        $this->line(" Exitosas:  {$resultados['exitosas']}");
        $this->line(" Fallidas:  {$resultados['fallidas']}");
        $this->line("Omitidas:  {$resultados['omitidas']}");
        $this->info('═══════════════════════════════════════');

        if ($this->option('test')) {
            $this->warn('  MODO PRUEBA: No se guardaron cambios');
        }

        return Command::SUCCESS;
    }
}