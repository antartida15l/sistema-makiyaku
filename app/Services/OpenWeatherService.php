<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class OpenWeatherService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openweathermap.org/data/2.5';
    protected string $geoUrl = 'https://api.openweathermap.org/geo/1.0';

    public function __construct()
    {
        $this->apiKey = config('services.openweather.key');
    }

    /**
     * Obtener clima actual por ciudad 
     */
    public function getCurrentWeather(string $city, string $country = ''): ?array
    {
        $cacheKey = "weather_current_{$city}_{$country}";
        
        return Cache::remember($cacheKey, 600, function () use ($city, $country): ?array {
            try {
                $query = $city;
                if ($country) {
                    $query .= ',' . $country;
                }

                $response = Http::timeout(10)->get("{$this->baseUrl}/weather", [
                    'q' => $query,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'es'
                ]);

                if ($response->successful()) {
                    return $this->formatCurrentWeather($response->json());
                }

                throw new Exception('Error al obtener datos del clima: ' . $response->status());
            } catch (Exception $e) {
                report($e);
                return null;
            }
        });
    }

    /**
     * Obtener clima actual por coordenadas
     */
    public function getCurrentWeatherByCoordinates(float $lat, float $lon): ?array
    {
        $cacheKey = "weather_coord_{$lat}_{$lon}";
        
        return Cache::remember($cacheKey, 600, function () use ($lat, $lon): ?array {
            try {
                $response = Http::timeout(10)->get("{$this->baseUrl}/weather", [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'es'
                ]);

                if ($response->successful()) {
                    return $this->formatCurrentWeather($response->json());
                }

                throw new Exception('Error al obtener datos del clima');
            } catch (Exception $e) {
                report($e);
                return null;
            }
        });
    }

    /**
     * Obtener pronóstico 5 días (cada 3 horas) 
     */
    public function getForecast(string $city, string $country = ''): ?array
    {
        $cacheKey = "forecast_{$city}_{$country}";
        
        return Cache::remember($cacheKey, 1800, function () use ($city, $country): ?array {
            try {
                $query = $city;
                if ($country) {
                    $query .= ',' . $country;
                }

                $response = Http::timeout(10)->get("{$this->baseUrl}/forecast", [
                    'q' => $query,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'es'
                ]);

                if ($response->successful()) {
                    return $this->formatForecast($response->json());
                }

                throw new Exception('Error al obtener pronóstico');
            } catch (Exception $e) {
                report($e);
                return null;
            }
        });
    }

    /**
     * Obtener pronóstico por coordenadas
     */
    public function getForecastByCoordinates(float $lat, float $lon): ?array
    {
        $cacheKey = "forecast_coord_{$lat}_{$lon}";
        
        return Cache::remember($cacheKey, 1800, function () use ($lat, $lon): ?array {
            try {
                $response = Http::timeout(10)->get("{$this->baseUrl}/forecast", [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'es'
                ]);

                if ($response->successful()) {
                    return $this->formatForecast($response->json());
                }

                throw new Exception('Error al obtener pronóstico');
            } catch (Exception $e) {
                report($e);
                return null;
            }
        });
    }

    /**
     * Buscar ciudad por nombre 
     */
    public function searchCity(string $cityName): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->geoUrl}/direct", [
                'q' => $cityName,
                'limit' => 5,
                'appid' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (Exception $e) {
            report($e);
            return [];
        }
    }

    /**
     * Formatear datos del clima actual
     */
    protected function formatCurrentWeather(array $data): array
    {
        return [
            // Información de ubicación
            'name' => $data['name'],
            'country' => $data['sys']['country'] ?? '',
            'lat' => $data['coord']['lat'] ?? null,
            'lon' => $data['coord']['lon'] ?? null,
            
            // Temperaturas
            'temp' => round($data['main']['temp'], 1),
            'feels_like' => round($data['main']['feels_like'], 1),
            'temp_min' => round($data['main']['temp_min'], 1),
            'temp_max' => round($data['main']['temp_max'], 1),
            
            // Humedad y presión
            'humidity' => $data['main']['humidity'],
            'pressure' => $data['main']['pressure'],
            'sea_level' => $data['main']['sea_level'] ?? null,
            'grnd_level' => $data['main']['grnd_level'] ?? null,
            
            // Viento
            'wind_speed' => $data['wind']['speed'],
            'wind_deg' => $data['wind']['deg'] ?? 0,
            'wind_gust' => $data['wind']['gust'] ?? null,
            'wind_direction' => $this->getWindDirection($data['wind']['deg'] ?? 0),
            
            // Nubosidad y visibilidad
            'clouds' => $data['clouds']['all'],
            'visibility' => isset($data['visibility']) ? round($data['visibility'] / 1000, 1) : null,
            
            // Lluvia y nieve
            'rain_1h' => $data['rain']['1h'] ?? 0,
            'rain_3h' => $data['rain']['3h'] ?? 0,
            'snow_1h' => $data['snow']['1h'] ?? 0,
            'snow_3h' => $data['snow']['3h'] ?? 0,
            
            // Descripción del clima
            'weather_id' => $data['weather'][0]['id'],
            'weather_main' => $data['weather'][0]['main'],
            'weather_description' => ucfirst($data['weather'][0]['description']),
            'weather_icon' => $data['weather'][0]['icon'],
            'icon_url' => $this->getWeatherIconUrl($data['weather'][0]['icon']),
            
            // Sol
            'sunrise' => $data['sys']['sunrise'],
            'sunset' => $data['sys']['sunset'],
            'sunrise_formatted' => date('H:i', $data['sys']['sunrise']),
            'sunset_formatted' => date('H:i', $data['sys']['sunset']),
            
            // Timestamps
            'dt' => $data['dt'],
            'timezone' => $data['timezone'] ?? 0,
            'recorded_at' => date('Y-m-d H:i:s', $data['dt']),
        ];
    }

    /**
     * Formatear pronóstico 
     */
    protected function formatForecast(array $data): array
    {
        $forecast = [
            'city' => $data['city']['name'],
            'country' => $data['city']['country'],
            'lat' => $data['city']['coord']['lat'],
            'lon' => $data['city']['coord']['lon'],
            'timezone' => $data['city']['timezone'],
            'sunrise' => $data['city']['sunrise'],
            'sunset' => $data['city']['sunset'],
            'list' => [],
        ];

        foreach ($data['list'] as $item) {
            $forecast['list'][] = [
                'dt' => $item['dt'],
                'datetime' => date('Y-m-d H:i:s', $item['dt']),
                'date_formatted' => date('d/m H:i', $item['dt']),
                
                // Temperaturas
                'temp' => round($item['main']['temp'], 1),
                'feels_like' => round($item['main']['feels_like'], 1),
                'temp_min' => round($item['main']['temp_min'], 1),
                'temp_max' => round($item['main']['temp_max'], 1),
                
                // Humedad y presión
                'humidity' => $item['main']['humidity'],
                'pressure' => $item['main']['pressure'],
                
                // Clima
                'weather_main' => $item['weather'][0]['main'],
                'weather_description' => ucfirst($item['weather'][0]['description']),
                'weather_icon' => $item['weather'][0]['icon'],
                'icon_url' => $this->getWeatherIconUrl($item['weather'][0]['icon']),
                
                // Nubosidad
                'clouds' => $item['clouds']['all'],
                
                // Viento
                'wind_speed' => $item['wind']['speed'],
                'wind_deg' => $item['wind']['deg'] ?? 0,
                'wind_gust' => $item['wind']['gust'] ?? null,
                'wind_direction' => $this->getWindDirection($item['wind']['deg'] ?? 0),
                
                // Probabilidad de precipitación (POP)
                'pop' => isset($item['pop']) ? round($item['pop'] * 100) : 0,
                
                // Lluvia y nieve
                'rain_3h' => $item['rain']['3h'] ?? 0,
                'snow_3h' => $item['snow']['3h'] ?? 0,
                
                // Visibilidad
                'visibility' => isset($item['visibility']) ? round($item['visibility'] / 1000, 1) : null,
                
                // Parte del día
                'pod' => $item['sys']['pod'] ?? 'n', // d=día, n=noche
            ];
        }

        return $forecast;
    }

    /**
     * Formatear datos del clima para guardar en BD
     */
    public function formatWeatherData(array $weatherData): array
    {
        return [
            'temperature' => $weatherData['main']['temp'],
            'feels_like' => $weatherData['main']['feels_like'],
            'humidity' => $weatherData['main']['humidity'],
            'pressure' => $weatherData['main']['pressure'],
            'wind_speed' => $weatherData['wind']['speed'],
            'wind_deg' => $weatherData['wind']['deg'] ?? 0,
            'clouds' => $weatherData['clouds']['all'],
            'weather_main' => $weatherData['weather'][0]['main'],
            'weather_description' => $weatherData['weather'][0]['description'],
            'weather_icon' => $weatherData['weather'][0]['icon'],
            'recorded_at' => now(),
        ];
    }

    /**
     * Obtener URL del ícono del clima
     */
    public function getWeatherIconUrl(string $icon, string $size = '2x'): string
    {
        return "https://openweathermap.org/img/wn/{$icon}@{$size}.png";
    }

    /**
     * Convertir grados a dirección del viento
     */
    protected function getWindDirection(int $degrees): string
    {
        $directions = ['N', 'NE', 'E', 'SE', 'S', 'SO', 'O', 'NO'];
        $index = round($degrees / 45) % 8;
        return $directions[$index];
    }

    /**
     * Obtener lluvia acumulada últimos 7 días 
     */
    public function getRainLast7Days(string $city, string $country = ''): float
    {
        // Esta es una aproximación usando el pronóstico
        $forecast = $this->getForecast($city, $country);
        
        if (!$forecast) {
            return 0;
        }

        $totalRain = 0;
        foreach ($forecast['list'] as $item) {
            $totalRain += $item['rain_3h'];
        }

        return round($totalRain, 2);
    }

    /**
     * Obtener resumen del clima para alertas de riego 
     */
    public function getWeatherSummaryForIrrigation(float $lat, float $lon): array
    {
        $current = $this->getCurrentWeatherByCoordinates($lat, $lon);
        $forecast = $this->getForecastByCoordinates($lat, $lon);

        if (!$current) {
            return [
                'temperature' => null,
                'humidity' => null,
                'rain_forecast_24h' => 0,
                'rain_probability_24h' => 0,
                'conditions' => 'unknown',
            ];
        }

        // Calcular lluvia próximas 24h 
        $rainNext24h = 0;
        $maxProbability = 0;
        
        if ($forecast) {
            $next24h = array_slice($forecast['list'], 0, 8);
            foreach ($next24h as $item) {
                $rainNext24h += $item['rain_3h'];
                $maxProbability = max($maxProbability, $item['pop']);
            }
        }

        return [
            'temperature' => $current['temp'],
            'humidity' => $current['humidity'],
            'rain_last_3h' => $current['rain_3h'],
            'rain_forecast_24h' => round($rainNext24h, 2),
            'rain_probability_24h' => $maxProbability,
            'wind_speed' => $current['wind_speed'],
            'conditions' => $current['weather_main'],
            'description' => $current['weather_description'],
        ];
    }

    /**
     * Limpiar caché de una ciudad específica
     */
    public function clearCityCache(string $city, string $country = ''): void
    {
        Cache::forget("weather_current_{$city}_{$country}");
        Cache::forget("forecast_{$city}_{$country}");
    }

    /**
     * Forzar actualización de datos
     */
    public function forceRefresh(string $city, string $country = ''): ?array
    {
        $this->clearCityCache($city, $country);
        return $this->getCurrentWeather($city, $country);
    }
}