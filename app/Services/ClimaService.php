<?php

namespace App\Services;

use App\Models\Parcela;
use App\Models\ClimaHistorial;
use App\Models\Ubicacion;
use Carbon\Carbon;

class ClimaService
{
    protected OpenWeatherService $openWeather;

    public function __construct(OpenWeatherService $openWeather)
    {
        $this->openWeather = $openWeather;
    }

    /**
     * Actualizar clima de una parcela desde OpenWeather API
     */
    public function actualizarClimaParcela(Parcela $parcela): ?ClimaHistorial
    {
        if (!$parcela->ubicacion) {
            return null;
        }

        // Obtener coordenadas
        $lat = $parcela->ubicacion->coordenadaLatitud;
        $lon = $parcela->ubicacion->coordenadaLongitud;

        if (!$lat || !$lon) {
            return null;
        }

        // Obtener clima desde API
        $climaData = $this->openWeather->getCurrentWeatherByCoordinates($lat, $lon);
        
        if (!$climaData) {
            return null;
        }

        // Obtener pronóstico para probabilidad de lluvia
        $forecast = $this->openWeather->getForecastByCoordinates($lat, $lon);
        $probabilidadLluvia = $this->calcularProbabilidadLluvia24h($forecast);

        // Guardar en clima_historial
        return ClimaHistorial::create([
            'parcela_id' => $parcela->id,
            'temperatura' => $climaData['temp'],
            'humedad' => $climaData['humidity'],
            'viento_velocidad' => $climaData['wind_speed'],
            'viento_direccion' => $climaData['wind_direction'],
            'probabilidad_lluvia_24h' => $probabilidadLluvia,
            'descripcion_clima' => $climaData['weather_description'],
            'emoji_clima' => $this->getEmojiClima($climaData['weather_main']),
            'presion_atmosferica' => $climaData['pressure'],
            'nubosidad' => $climaData['clouds'],
            'tipo_actualizacion' => 'api',
            'alerta_helada' => $climaData['temp'] < 10,
            'alerta_calor_extremo' => $climaData['temp'] > 35,
            'fecha_registro' => now(),
        ]);
    }

    /**
     * Actualizar clima de TODAS las parcelas de un usuario
     */
    public function actualizarClimaUsuario(int $userId): int
    {
        $parcelas = Parcela::where('user_id', $userId)
            ->activas()
            ->with('ubicacion')
            ->get();

        $actualizadas = 0;

        foreach ($parcelas as $parcela) {
            if ($this->actualizarClimaParcela($parcela)) {
                $actualizadas++;
            }
        }

        return $actualizadas;
    }

    /**
     * Calcular probabilidad de lluvia en próximas 24h
     */
    private function calcularProbabilidadLluvia24h(?array $forecast): int
    {
        if (!$forecast || empty($forecast['list'])) {
            return 0;
        }

        $next24h = array_slice($forecast['list'], 0, 8); // 8 intervalos de 3h = 24h
        $maxProbabilidad = 0;

        foreach ($next24h as $item) {
            $maxProbabilidad = max($maxProbabilidad, $item['pop']);
        }

        return $maxProbabilidad;
    }

    /**
     * Obtener emoji según condición climática
     */
    private function getEmojiClima(string $condition): string
    {
        return match($condition) {
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
}