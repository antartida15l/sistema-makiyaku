<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class GeocodingService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openweathermap.org/geo/1.0';
    protected string $nominatimUrl = 'https://nominatim.openstreetmap.org';

    public function __construct()
    {
        $this->apiKey = config('services.openweather.key');
    }

    /**
     * Buscar ciudades por nombre con autocompletado
     * Retorna máximo 10 resultados
     */
    public function searchCities(string $query, int $limit = 10): array
    {
        // No buscar si el query es muy corto
        if (strlen($query) < 2) {
            return [];
        }

        $cacheKey = "geocoding_search_" . md5(strtolower($query) . $limit);
        
        return Cache::remember($cacheKey, 3600, function () use ($query, $limit) {
            try {
                $response = Http::timeout(5)->get("{$this->baseUrl}/direct", [
                    'q' => $query,
                    'limit' => $limit,
                    'appid' => $this->apiKey
                ]);

                if ($response->successful()) {
                    $results = $response->json();
                    
                    // Formatear resultados para el frontend
                    return array_map(function ($city) {
                        return [
                            'name' => $city['name'],
                            'country' => $city['country'] ?? '',
                            'state' => $city['state'] ?? '',
                            'lat' => $city['lat'],
                            'lon' => $city['lon'],
                            'display_name' => $this->formatCityName($city),
                        ];
                    }, $results);
                }

                return [];
            } catch (Exception $e) {
                report($e);
                return [];
            }
        });
    }

    /**
     * GEOCODIFICACIÓN AUTOMÁTICA PARA PERÚ
     * Busca coordenadas por distrito/provincia/región usando Nominatim (GRATUITO)
     */
    public function geocodificarUbicacionPeru(string $distrito, string $provincia, string $region): ?array
    {
        $cacheKey = "geocode_peru_" . md5(strtolower("{$distrito}_{$provincia}_{$region}"));
        
        return Cache::remember($cacheKey, 86400, function () use ($distrito, $provincia, $region) {
            try {
                // Construir query optimizado para Perú
                $queries = [
                    // Intento 1: Distrito + Provincia + Región + País
                    "{$distrito}, {$provincia}, {$region}, Peru",
                    // Intento 2: Solo Distrito + Región + País
                    "{$distrito}, {$region}, Peru",
                    // Intento 3: Solo Distrito + País
                    "{$distrito}, Peru",
                ];

                foreach ($queries as $query) {
                    $response = Http::timeout(8)
                        ->withHeaders([
                            'User-Agent' => 'AgroRiegoApp/1.0 (Laravel)',
                        ])
                        ->get("{$this->nominatimUrl}/search", [
                            'q' => $query,
                            'format' => 'json',
                            'countrycodes' => 'pe', // Solo Perú
                            'limit' => 1,
                            'addressdetails' => 1,
                        ]);

                    if ($response->successful()) {
                        $results = $response->json();
                        
                        if (!empty($results)) {
                            $result = $results[0];
                            
                            return [
                                'lat' => (float) $result['lat'],
                                'lon' => (float) $result['lon'],
                                'display_name' => $result['display_name'],
                                'tipo' => $result['type'] ?? 'unknown',
                                'clase' => $result['class'] ?? 'unknown',
                                'confianza' => $this->calcularConfianza($result, $distrito, $provincia, $region),
                            ];
                        }
                    }

                    // Esperar 1 segundo entre requests para respetar rate limit de Nominatim
                    sleep(1);
                }

                return null;
            } catch (Exception $e) {
                report($e);
                return null;
            }
        });
    }

    /**
     *  Geocodificar TODAS las ubicaciones de la base de datos que no tengan coordenadas
     */
    public function geocodificarUbicacionesSinCoordenadas(): array
    {
        $ubicaciones = \App\Models\Ubicacion::whereNull('coordenadaLatitud')
            ->orWhereNull('coordenadaLongitud')
            ->get();

        $resultados = [
            'total' => $ubicaciones->count(),
            'exitosas' => 0,
            'fallidas' => 0,
            'detalles' => [],
        ];

        foreach ($ubicaciones as $ubicacion) {
            $coords = $this->geocodificarUbicacionPeru(
                $ubicacion->distrito,
                $ubicacion->provincia,
                $ubicacion->region
            );

            if ($coords && $coords['confianza'] >= 0.5) {
                $ubicacion->update([
                    'coordenadaLatitud' => $coords['lat'],
                    'coordenadaLongitud' => $coords['lon'],
                ]);

                $resultados['exitosas']++;
                $resultados['detalles'][] = [
                    'ubicacion' => $ubicacion->obtenerDireccionCompleta(),
                    'coords' => "{$coords['lat']}, {$coords['lon']}",
                    'confianza' => $coords['confianza'],
                    'status' => 'success',
                ];
            } else {
                $resultados['fallidas']++;
                $resultados['detalles'][] = [
                    'ubicacion' => $ubicacion->obtenerDireccionCompleta(),
                    'status' => 'failed',
                    'mensaje' => 'No se encontraron coordenadas confiables',
                ];
            }

            // Respetar rate limit de Nominatim (máx 1 request por segundo)
            sleep(1);
        }

        return $resultados;
    }

    /**
     * Calcula nivel de confianza del resultado (0.0 a 1.0)
     */
    protected function calcularConfianza(array $result, string $distrito, string $provincia, string $region): float
    {
        $confianza = 0.5; // Base

        $displayName = strtolower($result['display_name'] ?? '');
        
        // Aumentar confianza si coincide el distrito
        if (stripos($displayName, strtolower($distrito)) !== false) {
            $confianza += 0.3;
        }

        // Aumentar si coincide provincia
        if (stripos($displayName, strtolower($provincia)) !== false) {
            $confianza += 0.1;
        }

        // Aumentar si coincide región
        if (stripos($displayName, strtolower($region)) !== false) {
            $confianza += 0.1;
        }

        return min($confianza, 1.0);
    }

    /**
     * Obtener coordenadas de una ciudad específica
     */
    public function getCityCoordinates(string $cityName, string $country = ''): ?array
    {
        $query = $country ? "{$cityName},{$country}" : $cityName;
        $results = $this->searchCities($query, 1);
        
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Buscar ciudades por código postal
     */
    public function searchByZipCode(string $zipCode, string $country = 'PE'): ?array
    {
        $cacheKey = "geocoding_zip_{$zipCode}_{$country}";
        
        return Cache::remember($cacheKey, 7200, function () use ($zipCode, $country) {
            try {
                $query = $country ? "{$zipCode},{$country}" : $zipCode;
                
                $response = Http::timeout(5)->get("{$this->baseUrl}/zip", [
                    'zip' => $query,
                    'appid' => $this->apiKey
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'name' => $data['name'],
                        'country' => $data['country'],
                        'lat' => $data['lat'],
                        'lon' => $data['lon'],
                        'display_name' => "{$data['name']}, {$data['country']}",
                    ];
                }

                return null;
            } catch (Exception $e) {
                report($e);
                return null;
            }
        });
    }

    /**
     * Geocodificación inversa: obtener ciudad por coordenadas
     */
    public function reverseGeocode(float $lat, float $lon, int $limit = 1): array
    {
        $cacheKey = "geocoding_reverse_{$lat}_{$lon}";
        
        return Cache::remember($cacheKey, 7200, function () use ($lat, $lon, $limit) {
            try {
                $response = Http::timeout(5)->get("{$this->baseUrl}/reverse", [
                    'lat' => $lat,
                    'lon' => $lon,
                    'limit' => $limit,
                    'appid' => $this->apiKey
                ]);

                if ($response->successful()) {
                    $results = $response->json();
                    
                    return array_map(function ($city) {
                        return [
                            'name' => $city['name'],
                            'country' => $city['country'] ?? '',
                            'state' => $city['state'] ?? '',
                            'lat' => $city['lat'],
                            'lon' => $city['lon'],
                            'display_name' => $this->formatCityName($city),
                        ];
                    }, $results);
                }

                return [];
            } catch (Exception $e) {
                report($e);
                return [];
            }
        });
    }

    /**
     * Obtener ciudades principales de Perú 
     */
    public function getCiudadesPrincipalesPeru(): array
    {
        return [
            ['name' => 'Lima', 'state' => 'Lima', 'country' => 'PE', 'lat' => -12.0464, 'lon' => -77.0428],
            ['name' => 'Arequipa', 'state' => 'Arequipa', 'country' => 'PE', 'lat' => -16.4090, 'lon' => -71.5375],
            ['name' => 'Cusco', 'state' => 'Cusco', 'country' => 'PE', 'lat' => -13.5319, 'lon' => -71.9675],
            ['name' => 'Puno', 'state' => 'Puno', 'country' => 'PE', 'lat' => -15.8402, 'lon' => -70.0219],
            ['name' => 'Juliaca', 'state' => 'Puno', 'country' => 'PE', 'lat' => -15.5000, 'lon' => -70.1333],
            ['name' => 'Ayaviri', 'state' => 'Puno', 'country' => 'PE', 'lat' => -14.8833, 'lon' => -70.5833],
            ['name' => 'Trujillo', 'state' => 'La Libertad', 'country' => 'PE', 'lat' => -8.1116, 'lon' => -79.0288],
            ['name' => 'Chiclayo', 'state' => 'Lambayeque', 'country' => 'PE', 'lat' => -6.7714, 'lon' => -79.8410],
            ['name' => 'Huancayo', 'state' => 'Junín', 'country' => 'PE', 'lat' => -12.0658, 'lon' => -75.2049],
            ['name' => 'Piura', 'state' => 'Piura', 'country' => 'PE', 'lat' => -5.1945, 'lon' => -80.6328],
            ['name' => 'Iquitos', 'state' => 'Loreto', 'country' => 'PE', 'lat' => -3.7437, 'lon' => -73.2516],
            ['name' => 'Tacna', 'state' => 'Tacna', 'country' => 'PE', 'lat' => -18.0147, 'lon' => -70.2536],
            ['name' => 'Ayacucho', 'state' => 'Ayacucho', 'country' => 'PE', 'lat' => -13.1631, 'lon' => -74.2236],
            ['name' => 'Cajamarca', 'state' => 'Cajamarca', 'country' => 'PE', 'lat' => -7.1612, 'lon' => -78.5126],
            ['name' => 'Pucallpa', 'state' => 'Ucayali', 'country' => 'PE', 'lat' => -8.3791, 'lon' => -74.5539],
            ['name' => 'Chimbote', 'state' => 'Áncash', 'country' => 'PE', 'lat' => -9.0853, 'lon' => -78.5783],
        ];
    }

    /**
     * Formatear nombre completo de ciudad
     */
    protected function formatCityName(array $city): string
    {
        $parts = [$city['name']];
        
        if (!empty($city['state'])) {
            $parts[] = $city['state'];
        }
        
        if (!empty($city['country'])) {
            $parts[] = $city['country'];
        }
        
        return implode(', ', $parts);
    }

    /**
     * Validar si una ciudad existe
     */
    public function validateCity(string $cityName, string $country = ''): bool
    {
        $results = $this->searchCities($cityName, 1);
        
        if (empty($results)) {
            return false;
        }

        // Si se especificó país, verificar que coincida
        if ($country) {
            return strtoupper($results[0]['country']) === strtoupper($country);
        }

        return true;
    }
}