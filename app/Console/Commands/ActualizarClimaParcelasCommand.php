<?php

namespace App\Console\Commands;

use App\Models\Parcela;
use App\Models\ClimaHistorial;
use App\Services\OpenWeatherService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ActualizarClimaParcelasCommand extends Command
{
   
    protected $signature = 'parcelas:actualizar-clima 
                            {--parcela= : ID de parcela específica (opcional)}
                            {--force : Forzar actualización aunque ya exista registro hoy}';

    protected $description = 'Actualiza el clima de todas las parcelas activas automáticamente';

    protected OpenWeatherService $weatherService;

    public function handle(OpenWeatherService $weatherService): int
    {
        $this->weatherService = $weatherService;

        $this->info(' actualización de clima para parcelas...');
        $this->newLine();

        // Obtener parcelas a procesar
        $parcelas = $this->obtenerParcelas();

        if ($parcelas->isEmpty()) {
            $this->warn(' No hay parcelas activas para actualizar.');
            return Command::SUCCESS;
        }

        $this->info(" Procesando {$parcelas->count()} parcela(s)...");
        $this->newLine();

        $exitosas = 0;
        $fallidas = 0;
        $omitidas = 0;

        foreach ($parcelas as $parcela) {
            $resultado = $this->actualizarClimaParcela($parcela);

            if ($resultado === 'exitosa') {
                $exitosas++;
            } elseif ($resultado === 'fallida') {
                $fallidas++;
            } else {
                $omitidas++;
            }
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info(' ACTUALIZACIÓN COMPLETADA');
        $this->info('═══════════════════════════════════════');
        $this->line("✅ Exitosas: {$exitosas}");
        
        if ($omitidas > 0) {
            $this->line("⏭️  Omitidas: {$omitidas} (ya actualizadas hoy)");
        }
        
        if ($fallidas > 0) {
            $this->error("❌ Fallidas: {$fallidas}");
        }

        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Obtiene las parcelas a procesar según los parámetros
     */
    protected function obtenerParcelas()
    {
        $parcelaId = $this->option('parcela');

        if ($parcelaId) {
            return Parcela::where('id', $parcelaId)
                ->with(['ubicacion', 'cultivo'])
                ->get();
        }

        return Parcela::activas()
            ->with(['ubicacion', 'cultivo'])
            ->get();
    }

    /**
     * Actualiza el clima de una parcela específica 
     */
    protected function actualizarClimaParcela(Parcela $parcela): string
    {
        try {
            // Verificar si ya tiene registro de hoy
            if (!$this->option('force')) {
                $registroHoy = ClimaHistorial::where('parcela_id', $parcela->id)
                    ->whereDate('fecha_registro', Carbon::today())
                    ->exists();

                if ($registroHoy) {
                    $this->line("⏭️  Parcela #{$parcela->id} ({$parcela->nombre}) - Ya actualizada hoy");
                    return 'omitida';
                }
            }

            // CARGAR UBICACIÓN EXPLÍCITAMENTE
            $parcela->load('ubicacion');
            
            // Obtener ubicación
            if (!$parcela->ubicacion) {
                $this->error("❌ Parcela #{$parcela->id} - Sin ubicación configurada");
                return 'fallida';
            }

            $ubicacion = $parcela->ubicacion;
            
            // Verificar coordenadas GPS
            if (!$ubicacion->tieneCoordenadasGPS()) {
                $this->error("❌ Parcela #{$parcela->id} - Sin coordenadas GPS");
                return 'fallida';
            }

            $distrito = $ubicacion->distrito;
            $provincia = $ubicacion->provincia;
            $pais = 'PE'; // Perú

            $this->line(" Consultando clima para: {$distrito}, {$provincia}...");

            // Consultar API de OpenWeather usando las coordenadas GPS directamente
            $climaActual = $this->weatherService->getCurrentWeatherByCoordinates(
                $ubicacion->coordenadaLatitud,
                $ubicacion->coordenadaLongitud
            );

            if (!$climaActual) {
                $this->error(" No se pudo obtener datos de clima para {$distrito}");
                return 'fallida';
            }

            // Obtener pronóstico para lluvia próximas 24h
            $pronostico = $this->weatherService->getWeatherSummaryForIrrigation(
                $climaActual['lat'] ?? 0,
                $climaActual['lon'] ?? 0
            );

            // Detectar alertas según el cultivo
            $alertas = $this->detectarAlertas($parcela, $climaActual, $pronostico);

            // Guardar registro en historial
            $registro = ClimaHistorial::create([
                'parcela_id' => $parcela->id,
                'ciudad' => $climaActual['name'],
                'distrito' => $distrito,
                'lat' => $climaActual['lat'],
                'lon' => $climaActual['lon'],
                
                // Temperaturas
                'temperatura' => $climaActual['temp'],
                'sensacion_termica' => $climaActual['feels_like'],
                'temp_minima' => $climaActual['temp_min'],
                'temp_maxima' => $climaActual['temp_max'],
                
                // Humedad y presión
                'humedad' => $climaActual['humidity'],
                'presion' => $climaActual['pressure'],
                
                // Viento
                'viento_velocidad' => $climaActual['wind_speed'],
                'viento_direccion' => $climaActual['wind_deg'],
                'viento_direccion_texto' => $climaActual['wind_direction'],
                'viento_rafaga' => $climaActual['wind_gust'],
                
                // Precipitaciones
                'lluvia_1h' => $climaActual['rain_1h'],
                'lluvia_3h' => $climaActual['rain_3h'],
                'lluvia_24h' => $pronostico['rain_forecast_24h'] ?? 0,
                'probabilidad_lluvia_24h' => $pronostico['rain_probability_24h'] ?? 0,
                
                // Condiciones
                'condicion_principal' => $climaActual['weather_main'],
                'descripcion' => $climaActual['weather_description'],
                'icono_codigo' => $climaActual['weather_icon'],
                'nubosidad' => $climaActual['clouds'],
                'visibilidad' => $climaActual['visibility'],
                
                // Sol
                'amanecer' => $climaActual['sunrise_formatted'],
                'atardecer' => $climaActual['sunset_formatted'],
                
                // Alertas
                'alerta_helada' => $alertas['helada'],
                'alerta_calor_extremo' => $alertas['calor_extremo'],
                'alerta_lluvia_proxima' => $alertas['lluvia_proxima'],
                'necesita_riego_urgente' => $alertas['riego_urgente'],
                
                'fecha_registro' => now(),
                'tipo_actualizacion' => 'automatica',
            ]);

            // Mostrar resultado con alertas si las hay
            $emoji = $this->getEmojiClima($climaActual['weather_main']);
            $alertasTexto = $this->getAlertasTexto($alertas);
            
            $this->info("✅ Parcela #{$parcela->id} ({$parcela->nombre}) - {$emoji} {$climaActual['temp']}°C {$alertasTexto}");

            return 'exitosa';

        } catch (\Exception $e) {
            $this->error(" Error en parcela #{$parcela->id}: {$e->getMessage()}");
            logger()->error("Error actualizando clima parcela {$parcela->id}: " . $e->getMessage());
            return 'fallida';
        }
    }
    /**
     * Detecta alertas según condiciones climáticas y cultivo
     */
    protected function detectarAlertas(Parcela $parcela, array $clima, array $pronostico): array
    {
        $cultivo = $parcela->cultivo;
        
        $alertas = [
            'helada' => false,
            'calor_extremo' => false,
            'lluvia_proxima' => false,
            'riego_urgente' => false,
        ];

        if (!$cultivo) {
            return $alertas;
        }

        // 1. Alerta de helada
        if ($clima['temp'] <= $cultivo->tempCriticaMinima) {
            $alertas['helada'] = true;
        }

        // 2. Alerta de calor extremo
        if ($clima['temp'] >= $cultivo->tempCriticaMaxima) {
            $alertas['calor_extremo'] = true;
        }

        // 3. Alerta de lluvia próxima (>50% probabilidad)
        if (($pronostico['rain_probability_24h'] ?? 0) > 50) {
            $alertas['lluvia_proxima'] = true;
        }

        // 4. Riego urgente (varios factores)
        $necesitaRiego = false;

        // Si hace calor y baja humedad
        if ($clima['temp'] > 28 && $clima['humidity'] < 40) {
            $necesitaRiego = true;
        }

        // Si tiene riego atrasado y no va a llover
        if ($parcela->riego_atrasado && ($pronostico['rain_probability_24h'] ?? 0) < 30) {
            $necesitaRiego = true;
        }

        // Si viento fuerte (>8 m/s) y baja humedad
        if (($clima['wind_speed'] ?? 0) > 8 && $clima['humidity'] < 50) {
            $necesitaRiego = true;
        }

        $alertas['riego_urgente'] = $necesitaRiego;

        return $alertas;
    }

    /**
     * Obtiene emoji según condición climática
     */
    protected function getEmojiClima(string $condicion): string
    {
        return match($condicion) {
            'Clear' => '☀️',
            'Clouds' => '☁️',
            'Rain' => '🌧️',
            'Drizzle' => '🌦️',
            'Thunderstorm' => '⛈️',
            'Snow' => '❄️',
            'Mist', 'Fog' => '🌫️',
            default => '🌤️',
        };
    }

    /**
     * Genera texto con alertas activas
     */
    protected function getAlertasTexto(array $alertas): string
    {
        $textos = [];

        if ($alertas['helada']) {
            $textos[] = '❄️ HELADA';
        }

        if ($alertas['calor_extremo']) {
            $textos[] = '🔥 CALOR';
        }

        if ($alertas['lluvia_proxima']) {
            $textos[] = '🌧️ LLUVIA';
        }

        if ($alertas['riego_urgente']) {
            $textos[] = '💧 RIEGO';
        }

        return $textos ? '(' . implode(' ', $textos) . ')' : '';
    }
}