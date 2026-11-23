<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\EventoRiego;
use App\Models\Parcela;
use App\Models\Recomendacion;
use App\Services\ClimaCompletoService;
use App\Services\MonitoreoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private MonitoreoService $monitoreo, private ClimaCompletoService $clima)
    {
    }

    public function index(Request $request)
    {
        $u = $request->user();

        // Sin cron (XAMPP) esto mantiene clima y alertas al dia, como mucho cada 15 minutos.
        $this->monitoreo->refrescarSiHaceFalta($u->id);

        $parcelas = Parcela::where('user_id', $u->id)->activas()
            ->with(['cultivo', 'ubicacion', 'climaActual', 'alertas' => fn ($q) => $q->where('archivada', false)])->get();

        $ids = $parcelas->pluck('id');
        $hoy = Carbon::today();

        // Agua: ultimos 14 dias y comparacion con los 14 anteriores
        $eventos = EventoRiego::whereIn('parcela_id', $ids)->where('fecha_riego', '>=', $hoy->copy()->subDays(27))->get();
        $serieAgua = ['labels' => [], 'datos' => []];
        for ($i = 13; $i >= 0; $i--) {
            $f = $hoy->copy()->subDays($i);
            $serieAgua['labels'][] = $f->format('d/m');
            $serieAgua['datos'][] = round($eventos->filter(fn ($e) => $e->fecha_riego->isSameDay($f))->sum('cantidad_agua'));
        }
        $agua14 = array_sum($serieAgua['datos']);
        $aguaAnt = round($eventos->filter(fn ($e) => $e->fecha_riego->lt($hoy->copy()->subDays(13)))->sum('cantidad_agua'));

        $activas = Alerta::porUsuario($u->id)->activas();
        $alertas = (clone $activas)->with('parcela:id,nombre')->ordenarPorUrgencia()->limit(5)->get();

        // Registro del campo: primero lo mas urgente
        $orden = ['critico' => 0, 'alto' => 1, 'medio' => 2];
        $registro = $parcelas->sortBy(function (Parcela $p) use ($orden) {
            $a = $p->alertas->where('grupo', Alerta::GRUPO_RIEGO)->first();

            return $a ? ($orden[$a->nivel] ?? 3) : 4 + $p->nivel_agua / 100;
        })->values()->take(8);

        $distribucion = $parcelas->groupBy(fn ($p) => $p->cultivo?->nombreCultivo ?? 'Sin cultivo')
            ->map(fn ($g, $nombre) => ['nombre' => $nombre, 'valor' => round((float) $g->sum('area'))])->sortByDesc('valor')->values();

        // Actividad reciente: riegos y alertas mezclados por fecha
        $actividad = collect()
            ->merge(EventoRiego::whereIn('parcela_id', $ids)->with('parcela:id,nombre')->latest('fecha_riego')->limit(5)->get()->map(fn ($e) => [
                'cuando' => $e->fecha_riego, 'icono' => 'agua', 'nivel' => 'ok', 'texto' => 'Riego en ' . ($e->parcela->nombre ?? 'parcela') . ': ' . number_format($e->cantidad_agua, 0, ',', '.') . ' L']))
            ->merge(Alerta::porUsuario($u->id)->with('parcela:id,nombre')->latest()->limit(5)->get()->map(fn ($a) => [
                'cuando' => $a->created_at, 'icono' => $a->icono, 'nivel' => $a->nivel, 'texto' => $a->titulo . ' en ' . ($a->parcela->nombre ?? 'parcela')]))
            ->sortByDesc('cuando')->take(6)->values();

        $conClima = $parcelas->first(fn ($p) => $p->climaActual);

        return view('dashboard', [
            'parcelas' => $parcelas, 'registro' => $registro,
            'kpis' => [
                'parcelas' => $parcelas->count(), 'area' => (float) $parcelas->sum('area'),
                'riego_hoy' => $parcelas->filter(fn ($p) => $p->alertas->where('grupo', Alerta::GRUPO_RIEGO)->whereIn('nivel', ['critico', 'alto'])->isNotEmpty())->count(),
                'alertas' => Alerta::contarActivasPorUsuario($u->id), 'criticas' => Alerta::contarCriticasPorUsuario($u->id),
                'agua14' => $agua14, 'agua_cambio' => $aguaAnt > 0 ? round(($agua14 - $aguaAnt) / $aguaAnt * 100) : null,
            ],
            'serieAgua' => $serieAgua, 'distribucion' => $distribucion, 'alertas' => $alertas, 'actividad' => $actividad,
            'recomendaciones' => Recomendacion::where('user_id', $u->id)->activas()->with('parcela:id,nombre')->ordenarPorRelevancia()->limit(3)->get(),
            'parcelaClima' => $conClima,
            'saludo' => match (true) { now()->hour < 12 => 'Buenos días', now()->hour < 19 => 'Buenas tardes', default => 'Buenas noches' },
        ]);
    }

    /** Pronostico de 48 horas de una parcela para el grafico del panel. */
    public function pronostico(Request $request)
    {
        $p = Parcela::where('user_id', $request->user()->id)->with('ubicacion')->find($request->parcela_id);

        if (!$p || !$p->tiene_gps) {
            return response()->json(['success' => false, 'message' => 'La parcela no tiene ubicación.']);
        }

        $d = $this->clima->paraCoordenadas($p->latitud_efectiva, $p->longitud_efectiva, $p->nombre, null, false);

        if (!$d) {
            return response()->json(['success' => false, 'message' => 'No se pudo obtener el pronóstico.']);
        }

        $h = array_slice($d['horas'], 0, 48);
        $z = $d['lugar']['zona'];

        $hoy = collect($d['dias'])->firstWhere('es_hoy', true) ?? [];
        $suelo = collect($d['agro']['humedad_suelo'] ?? [])->take(3)->pluck('humedad')->filter(fn ($v) => $v !== null);

        return response()->json(['success' => true, 'parcela' => $p->nombre, 'agro' => [
            'et0' => $hoy['et0_fao_evapotranspiration'] ?? null,
            'suelo' => $suelo->count() ? round($suelo->avg(), 1) : null,
            'radiacion' => isset($hoy['shortwave_radiation_sum']) ? round($hoy['shortwave_radiation_sum'] / 3.6, 1) : null,
            'prob' => $d['agro']['prob_lluvia_24h'] ?? null,
            'lluvia' => $d['agro']['lluvia_24h'] ?? null,
        ], 'data' => [
            'labels' => array_map(fn ($x) => Carbon::createFromTimestamp($x['ts'], $z)->format('H:i'), $h),
            'titulos' => array_map(fn ($x) => Carbon::createFromTimestamp($x['ts'], $z)->translatedFormat('D j, H:i'), $h),
            'temp' => array_column($h, 'temperature_2m'), 'lluvia' => array_column($h, 'precipitation'), 'prob' => array_column($h, 'precipitation_probability'),
        ]]);
    }
}
