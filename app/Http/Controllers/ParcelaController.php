<?php

namespace App\Http\Controllers;

use App\Models\Parcela;
use App\Models\Cultivo;
use App\Models\Ubicacion;
use App\Models\Alerta;
use App\Services\GeocodingService;
use App\Services\AlertaService;
use App\Services\RiegoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ParcelaController extends Controller
{
    use AuthorizesRequests;

    protected GeocodingService $geocodingService;
    protected AlertaService $alertaService;
    protected RiegoService $riegoService;

    public function __construct(
        GeocodingService $geocodingService,
        AlertaService $alertaService,
        RiegoService $riegoService
    ) {
        $this->geocodingService = $geocodingService;
        $this->alertaService = $alertaService;
        $this->riegoService = $riegoService;
    }

    /**
     * Muestra listado de parcelas del usuario CON CLIMA.
     */
    public function index()
    {
        $parcelas = Parcela::where('user_id', Auth::id())
            ->with(['cultivo', 'ubicacion', 'climaActual'])
            ->orderByRaw("
                CASE 
                    WHEN proximo_riego_estimado < ? THEN 1
                    WHEN proximo_riego_estimado = ? THEN 2
                    ELSE 3
                END
            ", [Carbon::today()->toDateString(), Carbon::today()->toDateString()])
            ->orderBy('proximo_riego_estimado', 'asc')
            ->paginate(12);

        // Los contadores salen de la misma tabla de alertas que ve el usuario,
        // asi nunca se contradicen con la pantalla de alertas.
        $activas = Alerta::porUsuario(Auth::id())->activas();

        $alertas = [
            'criticas' => (clone $activas)->where('grupo', Alerta::GRUPO_RIEGO)
                ->where('nivel', Alerta::NIVEL_CRITICO)->count(),
            'hoy' => (clone $activas)->where('grupo', Alerta::GRUPO_RIEGO)
                ->where('nivel', Alerta::NIVEL_ALTO)->count(),
            'climaticas' => (clone $activas)->whereIn('grupo', [
                Alerta::GRUPO_HELADA, Alerta::GRUPO_CALOR, Alerta::GRUPO_LLUVIA,
            ])->count(),
        ];

        return view('parcelas.index', compact('parcelas', 'alertas'));
    }

    /**
     * Muestra formulario de creación.
     */
    public function create()
    {
        $cultivos = Cultivo::orderBy('nombreCultivo')->get();
        
        return view('parcelas.create', compact('cultivos'));
    }

    /**
     *  Almacena una nueva parcela Y GENERA ALERTAS
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombreParcela' => 'required|string|max:255',
            'area' => 'required|numeric|min:0.01',
            'idCultivo' => 'required|exists:cultivos,idCultivo',
            'region' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'distrito' => 'required|string|max:100',
            'coordenadaLatitud' => 'nullable|numeric|between:-90,90',
            'coordenadaLongitud' => 'nullable|numeric|between:-180,180',
            'fecha_siembra' => 'nullable|date|before_or_equal:today',
            'ultimo_riego' => 'nullable|date|before_or_equal:today',
            'tieneHidrogelAplicado' => 'nullable|boolean',
            'hidrogel_fecha_aplicacion' => 'nullable|date|before_or_equal:today|required_if:tieneHidrogelAplicado,true',
            'hidrogel_cantidad_kg' => 'nullable|numeric|min:0|required_if:tieneHidrogelAplicado,true',
            'notas_usuario' => 'nullable|string|max:1000',
        ]);

        // Geocodificación automática
        $coordenadas = [
            'lat' => $validated['coordenadaLatitud'] ?? null,
            'lon' => $validated['coordenadaLongitud'] ?? null,
        ];

        if (!$coordenadas['lat'] || !$coordenadas['lon']) {
            try {
                $geoResult = $this->geocodingService->geocodificarUbicacionPeru(
                    $validated['distrito'],
                    $validated['provincia'],
                    $validated['region']
                );

                if ($geoResult && $geoResult['confianza'] >= 0.5) {
                    $coordenadas['lat'] = $geoResult['lat'];
                    $coordenadas['lon'] = $geoResult['lon'];
                    
                    session()->flash('geocodificacion', [
                        'success' => true,
                        'mensaje' => "Coordenadas encontradas automáticamente (confianza: " . round($geoResult['confianza'] * 100) . "%)",
                    ]);
                }
            } catch (\Exception $e) {
                logger()->warning("Error en geocodificación: {$e->getMessage()}");
            }
        }

        // Crear o buscar ubicación
        $ubicacion = Ubicacion::firstOrCreate([
            'region' => $validated['region'],
            'provincia' => $validated['provincia'],
            'distrito' => $validated['distrito'],
        ], [
            'coordenadaLatitud' => $coordenadas['lat'],
            'coordenadaLongitud' => $coordenadas['lon'],
        ]);

        if (!$ubicacion->tieneCoordenadasGPS() && $coordenadas['lat'] && $coordenadas['lon']) {
            $ubicacion->update([
                'coordenadaLatitud' => $coordenadas['lat'],
                'coordenadaLongitud' => $coordenadas['lon'],
            ]);
        }

        // Obtener datos del cultivo
        $cultivo = Cultivo::find($validated['idCultivo']);

        // Calcular valores automáticos
        $frecuenciaRiego = $this->calcularFrecuenciaRiego($cultivo, $request->has('tieneHidrogelAplicado'));

        $ultimoRiego = ($validated['ultimo_riego'] ?? null) 
            ? Carbon::parse(($validated['ultimo_riego'] ?? null)) 
            : Carbon::today()->subDays($frecuenciaRiego);
        
        $proximoRiego = $ultimoRiego->copy()->addDays($frecuenciaRiego);

        $diasDesdeSiembra = ($validated['fecha_siembra'] ?? null) 
            ? Carbon::parse(($validated['fecha_siembra'] ?? null))->diffInDays(Carbon::today())
            : 0;

        // Crear la parcela
        $parcela = Parcela::create([
            'user_id' => Auth::id(),
            'nombre' => $validated['nombreParcela'],
            'idUbicacion' => $ubicacion->idUbicacion,
            'idCultivo' => $validated['idCultivo'],
            'estado' => 'activa',
            'area' => $validated['area'],
            'ubicacion' => "{$validated['distrito']}, {$validated['provincia']}, {$validated['region']}",
            'tipo_cultivo' => $cultivo->nombreCultivo,
            'humedad_minima' => $cultivo->humedadOptimaMin,
            'humedad_maxima' => $cultivo->humedadOptimaMax,
            'ultimo_riego' => $ultimoRiego,
            'frecuencia_riego_dias' => $frecuenciaRiego,
            'litros_por_riego' => null,
            'proximo_riego_estimado' => $proximoRiego,
            'fecha_siembra' => $validated['fecha_siembra'] ?? null,
            'dias_desde_siembra' => $diasDesdeSiembra,
            'tieneHidrogelAplicado' => $request->has('tieneHidrogelAplicado'),
            'hidrogel_fecha_aplicacion' => $validated['hidrogel_fecha_aplicacion'] ?? null,
            'hidrogel_cantidad_kg' => $validated['hidrogel_cantidad_kg'] ?? null,
            'notas_usuario' => $validated['notas_usuario'] ?? null,
        ]);

        // Litros por riego con la formula unica del sistema
        $parcela->load('cultivo');
        $parcela->update(['litros_por_riego' => $this->riegoService->litrosPorRiego($parcela)]);

        // Si ya regó, crear el evento de riego
        if (($validated['ultimo_riego'] ?? null)) {
            $litrosIniciales = $parcela->litros_por_riego ?: ($parcela->area * 5);
            
            $parcela->eventosRiego()->create([
                'fecha_riego' => $ultimoRiego,
                'cantidad_agua' => $litrosIniciales,
                'tipo_riego' => 'manual',
                'notas' => 'Riego inicial registrado al crear la parcela',
            ]);
        }

        // Obtener clima inicial SI HAY COORDENADAS
        if ($ubicacion->tieneCoordenadasGPS()) {
            try {
                Artisan::call('parcelas:actualizar-clima', [
                    '--parcela' => $parcela->id,
                    '--force' => true
                ]);
            } catch (\Exception $e) {
                logger()->warning("No se pudo obtener clima inicial para parcela {$parcela->id}: {$e->getMessage()}");
            }
        }

        // GENERAR ALERTAS PARA LA NUEVA PARCELA
        try {
            $alertasCreadas = $this->alertaService->generarAlertasParcela($parcela);
            
            if (count($alertasCreadas) > 0) {
                session()->flash('alertas_nuevas', count($alertasCreadas));
                session()->flash('alertas_criticas', collect($alertasCreadas)->where('nivel', 'critico')->count());
            }
        } catch (\Exception $e) {
            logger()->error("Error generando alertas para parcela {$parcela->id}: {$e->getMessage()}");
        }

        $mensaje = 'Parcela creada exitosamente. Próximo riego: ' . $proximoRiego->format('d/m/Y');
        
        if ($ubicacion->tieneCoordenadasGPS()) {
            $mensaje .= 'Datos climáticos obtenidos.';
        }

        return redirect()->route('parcelas.index')->with('success', $mensaje);
    }

    /**
     * Muestra detalles de una parcela.
     */
    public function show(Parcela $parcela)
    {
        $this->authorize('view', $parcela);

        if ($parcela->fecha_siembra) {
            $parcela->actualizarDiasSiembra();
        }

        $parcela->load([
            'cultivo',
            'ubicacion',
            'climaActual',
            'eventosRiego' => function($q) {
                $q->latest()->limit(20);
            }
        ]);

        $estadisticasClima = null;
        if (method_exists($parcela, 'obtenerEstadisticasClima')) {
            $estadisticasClima = $parcela->obtenerEstadisticasClima(7);
        }

        return view('parcelas.show', compact('parcela', 'estadisticasClima'));
    }

    /**
     * Muestra formulario de edición.
     */
    public function edit(Parcela $parcela)
    {
        $this->authorize('update', $parcela);

        $cultivos = Cultivo::orderBy('nombreCultivo')->get();

        return view('parcelas.edit', compact('parcela', 'cultivos'));
    }

    /**
     *  Actualiza una parcela Y REGENERA ALERTAS
     */
    public function update(Request $request, Parcela $parcela)
    {
        $this->authorize('update', $parcela);

        $validated = $request->validate([
            'nombreParcela' => 'required|string|max:255',
            'area' => 'required|numeric|min:0.01',
            'idCultivo' => 'required|exists:cultivos,idCultivo',
            'estado' => 'required|in:activa,inactiva,mantenimiento',
            'region' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'distrito' => 'required|string|max:100',
            'coordenadaLatitud' => 'nullable|numeric|between:-90,90',
            'coordenadaLongitud' => 'nullable|numeric|between:-180,180',
            'fecha_siembra' => 'nullable|date|before_or_equal:today',
            'tieneHidrogelAplicado' => 'nullable|boolean',
            'hidrogel_fecha_aplicacion' => 'nullable|date|before_or_equal:today',
            'hidrogel_cantidad_kg' => 'nullable|numeric|min:0',
            'notas_usuario' => 'nullable|string|max:1000',
        ]);

        $ubicacionCambio = (
            $parcela->ubicacion->distrito !== $validated['distrito'] ||
            $parcela->ubicacion->provincia !== $validated['provincia'] ||
            $parcela->ubicacion->region !== $validated['region']
        );

        $coordenadas = [
            'lat' => $validated['coordenadaLatitud'] ?? null,
            'lon' => $validated['coordenadaLongitud'] ?? null,
        ];

        if ($ubicacionCambio && (!$coordenadas['lat'] || !$coordenadas['lon'])) {
            try {
                $geoResult = $this->geocodingService->geocodificarUbicacionPeru(
                    $validated['distrito'],
                    $validated['provincia'],
                    $validated['region']
                );

                if ($geoResult && $geoResult['confianza'] >= 0.5) {
                    $coordenadas['lat'] = $geoResult['lat'];
                    $coordenadas['lon'] = $geoResult['lon'];
                }
            } catch (\Exception $e) {
                logger()->warning("Error en geocodificación: {$e->getMessage()}");
            }
        }

        $ubicacion = Ubicacion::firstOrCreate([
            'region' => $validated['region'],
            'provincia' => $validated['provincia'],
            'distrito' => $validated['distrito'],
        ], [
            'coordenadaLatitud' => $coordenadas['lat'],
            'coordenadaLongitud' => $coordenadas['lon'],
        ]);

        if (!$ubicacion->tieneCoordenadasGPS() && $coordenadas['lat'] && $coordenadas['lon']) {
            $ubicacion->update([
                'coordenadaLatitud' => $coordenadas['lat'],
                'coordenadaLongitud' => $coordenadas['lon'],
            ]);
        }

        $cultivo = Cultivo::find($validated['idCultivo']);

        $recalcular = (
            $parcela->idCultivo != $validated['idCultivo'] || 
            $parcela->area != $validated['area'] ||
            $parcela->tieneHidrogelAplicado != $request->has('tieneHidrogelAplicado')
        );

        if ($recalcular) {
            $frecuenciaRiego = $this->calcularFrecuenciaRiego($cultivo, $request->has('tieneHidrogelAplicado'));
            $litrosPorRiego = null; // se recalcula justo despues de guardar
        } else {
            $litrosPorRiego = $parcela->litros_por_riego;
            $frecuenciaRiego = $parcela->frecuencia_riego_dias;
        }

        $diasDesdeSiembra = ($validated['fecha_siembra'] ?? null) 
            ? Carbon::parse(($validated['fecha_siembra'] ?? null))->diffInDays(Carbon::today())
            : 0;

        $parcela->update([
            'nombre' => $validated['nombreParcela'],
            'idUbicacion' => $ubicacion->idUbicacion,
            'idCultivo' => $validated['idCultivo'],
            'area' => $validated['area'],
            'estado' => $validated['estado'],
            'ubicacion' => "{$validated['distrito']}, {$validated['provincia']}, {$validated['region']}",
            'tipo_cultivo' => $cultivo->nombreCultivo,
            'litros_por_riego' => $litrosPorRiego,
            'frecuencia_riego_dias' => $frecuenciaRiego,
            'fecha_siembra' => $validated['fecha_siembra'] ?? null,
            'dias_desde_siembra' => $diasDesdeSiembra,
            'tieneHidrogelAplicado' => $request->has('tieneHidrogelAplicado'),
            'hidrogel_fecha_aplicacion' => $validated['hidrogel_fecha_aplicacion'] ?? null,
            'hidrogel_cantidad_kg' => $validated['hidrogel_cantidad_kg'] ?? null,
            'notas_usuario' => $validated['notas_usuario'] ?? null,
        ]);

        if ($litrosPorRiego === null) {
            $parcela->load('cultivo');
            $parcela->update(['litros_por_riego' => $this->riegoService->litrosPorRiego($parcela)]);
        }

        // Si cambió ubicación, actualizar clima
        if ($ubicacionCambio && $ubicacion->tieneCoordenadasGPS()) {
            try {
                Artisan::call('parcelas:actualizar-clima', [
                    '--parcela' => $parcela->id,
                    '--force' => true
                ]);
            } catch (\Exception $e) {
                logger()->warning("Error actualizando clima: {$e->getMessage()}");
            }
        }

        // REGENERAR ALERTAS DESPUÉS DE ACTUALIZAR
        try {
            $alertasCreadas = $this->alertaService->generarAlertasParcela($parcela);
            
            if (count($alertasCreadas) > 0) {
                session()->flash('alertas_nuevas', count($alertasCreadas));
            }
        } catch (\Exception $e) {
            logger()->error("Error regenerando alertas para parcela {$parcela->id}: {$e->getMessage()}");
        }

        return redirect()->route('parcelas.show', $parcela)
            ->with('success', ' Parcela actualizada exitosamente');
    }

    /**
     * Elimina una parcela.
     */
    public function destroy(Parcela $parcela)
    {
        $this->authorize('delete', $parcela);

        $parcela->delete();

        return redirect()->route('parcelas.index')
            ->with('success', ' Parcela eliminada exitosamente');
    }

    /**
     * Registra un riego Y ARCHIVA ALERTAS
     */
    public function registrarRiego(Request $request, Parcela $parcela)
    {
        $this->authorize('update', $parcela);

        $validated = $request->validate([
            'cantidad_agua' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:500',
        ]);

        // Si no indica cantidad se registra la recomendada hoy (misma cifra que la alerta).
        $parcela->loadMissing('cultivo');
        $litrosUsados = $validated['cantidad_agua']
            ?? ($this->riegoService->calcular($parcela, $parcela->climaActual)['litros_total'] ?: $parcela->litros_por_riego);

        // 1. Registrar el riego
        $parcela->registrarRiego($litrosUsados);

        // 2. Actualizar notas si existen
        if (!empty($validated['notas'])) {
            $parcela->eventosRiego()->latest()->first()->update([
                'notas' => $validated['notas']
            ]);
        }

        // ARCHIVAR ALERTAS DE RIEGO DE ESTA PARCELA
        try {
            $this->alertaService->archivarAlertasRiegoPorRiego($parcela);
        } catch (\Exception $e) {
            logger()->error("Error archivando alertas de riego: {$e->getMessage()}");
        }

        return redirect()->route('parcelas.show', $parcela)
            ->with('success', " Riego registrado. Próximo riego: {$parcela->proximo_riego_estimado->format('d/m/Y')}. Las alertas de riego han sido archivadas.");
    }

    /**
     * Actualiza el clima de una parcela manualmente
     */
    public function actualizarClima(Parcela $parcela)
    {
        $this->authorize('update', $parcela);

        $parcela->load('ubicacion');

        if (!$parcela->ubicacion) {
            return redirect()->back()->with('error', ' Esta parcela no tiene ubicación configurada.');
        }

        if (!$parcela->ubicacion->tieneCoordenadasGPS()) {
            return redirect()->back()->with('error', ' Esta parcela no tiene coordenadas GPS. Edítala para agregarlas.');
        }

        try {
            Artisan::call('parcelas:actualizar-clima', [
                '--parcela' => $parcela->id,
                '--force' => true
            ]);

            $parcela->load('climaActual');

            if ($parcela->climaActual) {
                $mensaje = "Clima actualizado: {$parcela->climaActual->temperatura}°C, {$parcela->climaActual->descripcion}";
                
                if ($parcela->climaActual->tiene_alertas) {
                    $mensaje .= "  Con {$parcela->climaActual->cantidad_alertas} alerta(s) activa(s)";
                }

                return redirect()->back()->with('success', $mensaje);
            }

            return redirect()->back()->with('error', 'No se pudo obtener datos del clima');

        } catch (\Exception $e) {
            logger()->error("Error actualizando clima parcela {$parcela->id}: " . $e->getMessage());
            return redirect()->back()->with('error', ' Error al actualizar clima: ' . $e->getMessage());
        }
    }

    /**
     * ENDPOINT AJAX: Geocodificar ubicación en tiempo real
     */
    public function geocodificarAjax(Request $request)
    {
        $validated = $request->validate([
            'distrito' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'region' => 'required|string|max:100',
        ]);

        try {
            $resultado = $this->geocodingService->geocodificarUbicacionPeru(
                $validated['distrito'],
                $validated['provincia'],
                $validated['region']
            );

            if ($resultado && $resultado['confianza'] >= 0.5) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'latitud' => $resultado['lat'],
                        'longitud' => $resultado['lon'],
                        'confianza' => round($resultado['confianza'] * 100),
                        'display_name' => $resultado['display_name'],
                    ],
                    'mensaje' => " Coordenadas encontradas (confianza: " . round($resultado['confianza'] * 100) . "%)"
                ]);
            }

            return response()->json([
                'success' => false,
                'mensaje' => ' No se encontraron coordenadas precisas para esta ubicación'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => ' Error al buscar coordenadas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcula la frecuencia de riego según el cultivo.
     */
    private function calcularFrecuenciaRiego(Cultivo $cultivo, bool $tieneHidrogel): int
    {
        return max(1, $cultivo->calcularFrecuenciaRiego($tieneHidrogel));
    }
}