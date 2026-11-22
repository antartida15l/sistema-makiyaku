<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParcelaRequest;
use App\Models\Alerta;
use App\Models\Cultivo;
use App\Models\Parcela;
use App\Models\Ubicacion;
use App\Services\AlertaService;
use App\Services\ClimaCompletoService;
use App\Services\GeocodingService;
use App\Services\MonitoreoService;
use App\Services\RecomendacionService;
use App\Services\RiegoService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParcelaController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private GeocodingService $geocoding,
        private AlertaService $alertas,
        private RiegoService $riego,
        private MonitoreoService $monitoreo,
        private ClimaCompletoService $climaCompleto,
        private RecomendacionService $recomendaciones,
    ) {
    }

    // =========================================================
    // LISTA
    // =========================================================

    public function index(Request $request)
    {
        $orden = $request->input('orden', 'urgencia');
        $hoy = Carbon::today()->toDateString();

        $consulta = Parcela::where('user_id', Auth::id())
            ->with(['cultivo', 'ubicacion', 'climaActual', 'alertas' => fn ($q) => $q->where('archivada', false)])
            ->when($request->filled('q'), fn ($q) => $q->where('nombre', 'like', '%' . $request->q . '%'))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('cultivo'), fn ($q) => $q->where('idCultivo', $request->cultivo));

        match ($orden) {
            'nombre' => $consulta->orderBy('nombre'),
            'area' => $consulta->orderByDesc('area'),
            'reciente' => $consulta->latest(),
            default => $consulta->orderByRaw('CASE WHEN proximo_riego_estimado IS NULL THEN 4 WHEN proximo_riego_estimado < ? THEN 1 WHEN proximo_riego_estimado = ? THEN 2 ELSE 3 END', [$hoy, $hoy])
                ->orderBy('proximo_riego_estimado'),
        };

        $parcelas = $consulta->paginate(12)->withQueryString();

        $activas = Alerta::porUsuario(Auth::id())->activas();
        $resumen = [
            'total' => Parcela::where('user_id', Auth::id())->count(),
            'area' => (float) Parcela::where('user_id', Auth::id())->activas()->sum('area'),
            'riego_urgente' => (clone $activas)->where('grupo', Alerta::GRUPO_RIEGO)->whereIn('nivel', [Alerta::NIVEL_CRITICO, Alerta::NIVEL_ALTO])->count(),
            'clima' => (clone $activas)->whereIn('grupo', [Alerta::GRUPO_HELADA, Alerta::GRUPO_CALOR, Alerta::GRUPO_LLUVIA])->count(),
            'papelera' => Parcela::onlyTrashed()->where('user_id', Auth::id())->count(),
        ];

        return view('parcelas.index', [
            'parcelas' => $parcelas,
            'resumen' => $resumen,
            'cultivos' => Cultivo::whereIn('idCultivo', Parcela::where('user_id', Auth::id())->pluck('idCultivo'))->orderBy('nombreCultivo')->get(),
            'filtros' => $request->only(['q', 'estado', 'cultivo', 'orden']),
        ]);
    }

    // =========================================================
    // CREAR / EDITAR
    // =========================================================

    public function create()
    {
        return view('parcelas.create', ['cultivos' => Cultivo::orderBy('nombreCultivo')->get(), 'parcela' => new Parcela()]);
    }

    public function store(ParcelaRequest $request)
    {
        $datos = $request->validated();
        [$lat, $lon, $avisoGeo] = $this->resolverCoordenadas($datos);
        $ubicacion = $this->ubicacionPara($datos, $lat, $lon);
        $cultivo = Cultivo::findOrFail($datos['idCultivo']);

        $frecuencia = max(1, $cultivo->calcularFrecuenciaRiego((bool) $datos['tieneHidrogelAplicado']));
        $ultimoRiego = !empty($datos['ultimo_riego']) ? Carbon::parse($datos['ultimo_riego']) : Carbon::today()->subDays($frecuencia);

        $parcela = Parcela::create([
            'user_id' => Auth::id(),
            'nombre' => $datos['nombreParcela'],
            'idUbicacion' => $ubicacion->idUbicacion,
            'idCultivo' => $cultivo->idCultivo,
            'latitud' => $lat, 'longitud' => $lon,
            'estado' => 'activa',
            'area' => $datos['area'],
            'tipo_cultivo' => $cultivo->nombreCultivo,
            'humedad_minima' => $cultivo->humedadOptimaMin,
            'humedad_maxima' => $cultivo->humedadOptimaMax,
            'ultimo_riego' => $ultimoRiego,
            'frecuencia_riego_dias' => $frecuencia,
            'proximo_riego_estimado' => $ultimoRiego->copy()->addDays($frecuencia),
            'fecha_siembra' => $datos['fecha_siembra'] ?? null,
            'dias_desde_siembra' => !empty($datos['fecha_siembra']) ? Carbon::parse($datos['fecha_siembra'])->diffInDays(Carbon::today()) : 0,
            'tieneHidrogelAplicado' => $datos['tieneHidrogelAplicado'],
            'hidrogel_fecha_aplicacion' => $datos['hidrogel_fecha_aplicacion'] ?? null,
            'hidrogel_cantidad_kg' => $datos['hidrogel_cantidad_kg'] ?? null,
            'notas_usuario' => $datos['notas_usuario'] ?? null,
        ]);

        $parcela->load('cultivo');
        $parcela->update(['litros_por_riego' => $this->riego->litrosPorRiego($parcela)]);

        if (!empty($datos['ultimo_riego'])) {
            $parcela->eventosRiego()->create([
                'fecha_riego' => $ultimoRiego, 'cantidad_agua' => $parcela->litros_por_riego ?: $parcela->area * 5,
                'tipo_riego' => 'manual', 'notas' => 'Riego inicial registrado al crear la parcela',
            ]);
        }

        $mensaje = "Parcela \"{$parcela->nombre}\" creada. Próximo riego: {$parcela->proximo_riego_estimado->format('d/m/Y')}.";

        if ($parcela->tiene_gps) {
            try {
                $this->monitoreo->monitorearParcela($parcela, true);
                $mensaje .= ' Ya tiene clima y alertas.';
            } catch (\Throwable $e) {
                logger()->warning("Sin clima inicial para la parcela {$parcela->id}: {$e->getMessage()}");
            }
        } else {
            $mensaje .= ' Agrega las coordenadas GPS para ver su clima.';
        }

        return redirect()->route('parcelas.show', $parcela)->with('success', $mensaje)->with('warning', $avisoGeo);
    }

    public function edit(Parcela $parcela)
    {
        $this->authorize('update', $parcela);

        return view('parcelas.edit', ['parcela' => $parcela->load(['cultivo', 'ubicacion']), 'cultivos' => Cultivo::orderBy('nombreCultivo')->get()]);
    }

    public function update(ParcelaRequest $request, Parcela $parcela)
    {
        $this->authorize('update', $parcela);

        $datos = $request->validated();
        $parcela->loadMissing('ubicacion');

        $cambioLugar = $parcela->ubicacion?->distrito !== $datos['distrito'] || $parcela->ubicacion?->provincia !== $datos['provincia']
            || $parcela->ubicacion?->region !== $datos['region']
            || (isset($datos['latitud']) && (float) $datos['latitud'] !== (float) $parcela->latitud)
            || (isset($datos['longitud']) && (float) $datos['longitud'] !== (float) $parcela->longitud);

        [$lat, $lon] = $cambioLugar ? $this->resolverCoordenadas($datos) : [$parcela->latitud, $parcela->longitud];
        $ubicacion = $this->ubicacionPara($datos, $lat, $lon);
        $cultivo = Cultivo::findOrFail($datos['idCultivo']);

        $parcela->update([
            'nombre' => $datos['nombreParcela'], 'idUbicacion' => $ubicacion->idUbicacion, 'idCultivo' => $cultivo->idCultivo,
            'latitud' => $lat, 'longitud' => $lon,
            'area' => $datos['area'], 'estado' => $datos['estado'], 'tipo_cultivo' => $cultivo->nombreCultivo,
            'fecha_siembra' => $datos['fecha_siembra'] ?? null,
            'dias_desde_siembra' => !empty($datos['fecha_siembra']) ? Carbon::parse($datos['fecha_siembra'])->diffInDays(Carbon::today()) : 0,
            'tieneHidrogelAplicado' => $datos['tieneHidrogelAplicado'],
            'hidrogel_fecha_aplicacion' => $datos['hidrogel_fecha_aplicacion'] ?? null,
            'hidrogel_cantidad_kg' => $datos['hidrogel_cantidad_kg'] ?? null,
            'notas_usuario' => $datos['notas_usuario'] ?? null,
        ]);

        $parcela->load('cultivo');
        $frecuencia = $this->riego->frecuenciaDias($parcela, $parcela->climaActual);
        $parcela->update(['frecuencia_riego_dias' => $frecuencia, 'litros_por_riego' => $this->riego->litrosPorRiego($parcela, $parcela->climaActual)]);
        $parcela->recalcularProximoRiego();

        try {
            $this->monitoreo->monitorearParcela($parcela, $cambioLugar);
        } catch (\Throwable $e) {
            logger()->warning("No se pudo actualizar el clima de la parcela {$parcela->id}: {$e->getMessage()}");
        }

        return redirect()->route('parcelas.show', $parcela)->with('success', 'Cambios guardados.');
    }

    // =========================================================
    // FICHA
    // =========================================================

    public function show(Parcela $parcela)
    {
        $this->authorize('view', $parcela);

        if ($parcela->fecha_siembra) {
            $parcela->actualizarDiasSiembra();
        }

        $parcela->load(['cultivo', 'ubicacion', 'climaActual', 'alertas' => fn ($q) => $q->activas()->ordenarPorUrgencia()]);

        $clima = $parcela->climaActual;
        $frecuencia = $this->riego->frecuenciaDias($parcela, $clima);
        $calculo = $this->riego->calcular($parcela, $clima);
        $diasSinRegar = $this->riego->diasSinRegar($parcela);
        $proximo = $parcela->ultimo_riego ? Carbon::parse($parcela->ultimo_riego)->addDays($frecuencia) : null;

        // Riegos de los ultimos 45 dias (para el grafico) y los 12 mas recientes (para la tabla)
        $riegos = $parcela->eventosRiego()->where('fecha_riego', '>=', now()->subDays(45))->orderBy('fecha_riego')->get();
        $porDia = $riegos->groupBy(fn ($e) => $e->fecha_riego->format('Y-m-d'))->map(fn ($g) => round($g->sum('cantidad_agua')));
        $serieRiego = ['labels' => [], 'datos' => []];
        for ($i = 44; $i >= 0; $i--) {
            $f = Carbon::today()->subDays($i);
            $serieRiego['labels'][] = $f->format('d/m');
            $serieRiego['datos'][] = $porDia[$f->format('Y-m-d')] ?? 0;
        }

        // Pronostico (solo bloques principales: la ficha no necesita aire ni historial)
        $pronostico = null;
        if ($parcela->tiene_gps && $parcela->estado === 'activa') {
            try {
                $pronostico = $this->climaCompleto->paraCoordenadas($parcela->latitud_efectiva, $parcela->longitud_efectiva, $parcela->nombre, $parcela->ubicacion?->region, false);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->recomendaciones->generarRecomendacionesParcela($parcela);

        return view('parcelas.show', [
            'parcela' => $parcela,
            'plan' => ['frecuencia' => $frecuencia, 'calculo' => $calculo, 'dias_sin_regar' => $diasSinRegar, 'proximo' => $proximo, 'texto' => $this->riego->textoCantidad($calculo)],
            'serieRiego' => $serieRiego,
            'riegos' => $parcela->eventosRiego()->latest('fecha_riego')->limit(12)->get(),
            'totalAgua30' => (float) $parcela->eventosRiego()->where('fecha_riego', '>=', now()->subDays(30))->sum('cantidad_agua'),
            'historialClima' => $parcela->climaHistorial()->where('fecha_registro', '>=', now()->subDays(7))->orderBy('fecha_registro')->get(['fecha_registro', 'temperatura', 'humedad', 'lluvia_72h_mm']),
            'pronostico' => $pronostico,
            'recomendaciones' => $parcela->recomendaciones()->activas()->ordenarPorRelevancia()->limit(4)->get(),
        ]);
    }

    // =========================================================
    // ACCIONES
    // =========================================================

    public function registrarRiego(Request $request, Parcela $parcela)
    {
        $this->authorize('update', $parcela);

        $datos = $request->validate([
            'cantidad_agua' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'fecha' => ['nullable', 'date', 'before_or_equal:today'],
            'tipo_riego' => ['nullable', 'in:manual,goteo,aspersion'],
            'notas' => ['nullable', 'string', 'max:500'],
        ], [
            'cantidad_agua.numeric' => 'La cantidad de agua debe ser un número.',
            'fecha.before_or_equal' => 'La fecha del riego no puede ser futura.',
        ]);

        $parcela->loadMissing('cultivo');
        $litros = isset($datos['cantidad_agua']) && $datos['cantidad_agua'] !== null && $datos['cantidad_agua'] !== ''
            ? (float) $datos['cantidad_agua']
            : ($this->riego->calcular($parcela, $parcela->climaActual)['litros_total'] ?: (float) $parcela->litros_por_riego);

        $fecha = !empty($datos['fecha']) ? Carbon::parse($datos['fecha'])->setTimeFrom(now()) : null;
        $parcela->registrarRiego($litros, $datos['tipo_riego'] ?? 'manual', $datos['notas'] ?? null, $fecha);

        $this->alertas->archivarAlertasRiegoPorRiego($parcela);
        $this->alertas->generarAlertasParcela($parcela->fresh());

        $parcela->refresh();

        return redirect()->route('parcelas.show', $parcela)
            ->with('success', 'Riego registrado: ' . number_format($litros, 0, ',', '.') . ' L. Próximo riego: ' . $parcela->proximo_riego_estimado->format('d/m/Y') . '.');
    }

    public function actualizarClima(Parcela $parcela)
    {
        $this->authorize('update', $parcela);

        if (!$parcela->tiene_gps) {
            return back()->with('error', 'Esta parcela no tiene coordenadas GPS. Edítala para agregarlas.');
        }

        try {
            $this->monitoreo->monitorearParcela($parcela, true, 'manual');
            $c = $parcela->fresh('climaActual')->climaActual;

            return back()->with('success', $c ? "Clima actualizado: {$c->temperatura} °C, " . mb_strtolower($c->descripcion) . '.' : 'No se pudo obtener el clima. Revisa tu conexión.');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No se pudo actualizar el clima. Intenta de nuevo en unos minutos.');
        }
    }

    public function destroy(Parcela $parcela)
    {
        $this->authorize('delete', $parcela);
        $nombre = $parcela->nombre;
        $parcela->delete();

        return redirect()->route('parcelas.index')->with('success', "\"{$nombre}\" se movió a la papelera. Puedes restaurarla cuando quieras.");
    }

    public function trashed()
    {
        $parcelas = Parcela::onlyTrashed()->where('user_id', Auth::id())->with('cultivo')->latest('deleted_at')->get();

        return view('parcelas.papelera', compact('parcelas'));
    }

    public function restore(int $id)
    {
        $parcela = Parcela::onlyTrashed()->where('user_id', Auth::id())->findOrFail($id);
        $this->authorize('restore', $parcela);
        $parcela->restore();

        return redirect()->route('parcelas.show', $parcela)->with('success', "\"{$parcela->nombre}\" restaurada.");
    }

    public function forceDestroy(int $id)
    {
        $parcela = Parcela::onlyTrashed()->where('user_id', Auth::id())->findOrFail($id);
        $this->authorize('forceDelete', $parcela);
        $nombre = $parcela->nombre;
        $parcela->forceDelete();

        return redirect()->route('parcelas.trashed')->with('success', "\"{$nombre}\" se eliminó para siempre.");
    }

    public function geocodificarAjax(Request $request)
    {
        $v = $request->validate(['distrito' => 'required|string|max:100', 'provincia' => 'required|string|max:100', 'region' => 'required|string|max:100']);

        try {
            $r = $this->geocoding->geocodificarUbicacionPeru($v['distrito'], $v['provincia'], $v['region']);

            if ($r && $r['confianza'] >= 0.5) {
                return response()->json(['success' => true, 'data' => [
                    'latitud' => $r['lat'], 'longitud' => $r['lon'], 'confianza' => round($r['confianza'] * 100), 'display_name' => $r['display_name'],
                ], 'mensaje' => 'Coordenadas encontradas (confianza ' . round($r['confianza'] * 100) . '%).']);
            }

            return response()->json(['success' => false, 'mensaje' => 'No encontramos coordenadas precisas. Escríbelas a mano o usa el GPS del teléfono.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'mensaje' => 'No se pudo buscar las coordenadas: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================
    // AUXILIARES
    // =========================================================

    /** @return array{0: ?float, 1: ?float, 2: ?string} latitud, longitud y un aviso si se adivinaron */
    private function resolverCoordenadas(array $d): array
    {
        if (!empty($d['latitud']) && !empty($d['longitud'])) {
            return [round((float) $d['latitud'], 7), round((float) $d['longitud'], 7), null];
        }

        try {
            $g = $this->geocoding->geocodificarUbicacionPeru($d['distrito'], $d['provincia'], $d['region']);

            if ($g && $g['confianza'] >= 0.5) {
                return [round($g['lat'], 7), round($g['lon'], 7), 'Usamos las coordenadas del centro del distrito. Para un clima más preciso, agrega el GPS exacto de la parcela.'];
            }
        } catch (\Throwable $e) {
            logger()->warning('Geocodificacion fallida: ' . $e->getMessage());
        }

        return [null, null, 'No pudimos ubicar la parcela. Agrega las coordenadas GPS para ver su clima.'];
    }

    private function ubicacionPara(array $d, ?float $lat, ?float $lon): Ubicacion
    {
        $u = Ubicacion::firstOrCreate(
            ['region' => $d['region'], 'provincia' => $d['provincia'], 'distrito' => $d['distrito']],
            ['coordenadaLatitud' => $lat, 'coordenadaLongitud' => $lon],
        );

        if (!$u->tieneCoordenadasGPS() && $lat !== null && $lon !== null) {
            $u->update(['coordenadaLatitud' => $lat, 'coordenadaLongitud' => $lon]);
        }

        return $u;
    }
}
