<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\ClimaHistorial;
use App\Models\EventoRiego;
use App\Models\Parcela;
use App\Models\Recomendacion;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Datos de los reportes. Todo sale de lo que el sistema ya guardo (riegos, alertas, clima, ciclos),
 * asi un reporte funciona aunque no haya internet.
 */
class ReporteService
{
    public const TIPOS = [
        'general' => ['Resumen general', 'Agua usada, alertas, clima y estado de cada parcela en un solo informe.'],
        'agua' => ['Consumo de agua', 'Cuántos litros se aplicaron, cuándo y en qué parcela.'],
        'alertas' => ['Historial de alertas', 'Qué avisos se generaron, su nivel y cómo terminaron.'],
        'clima' => ['Clima registrado', 'Temperatura, humedad y lluvia que el sistema guardó para cada parcela.'],
        'ciclo' => ['Estado de los cultivos', 'Avance del ciclo, riego y agua acumulada de cada cultivo.'],
    ];

    /** @return array{desde: Carbon, hasta: Carbon, parcelas: Collection} */
    public function alcance(int $userId, ?string $desde, ?string $hasta, ?int $parcelaId): array
    {
        $hasta = $hasta ? Carbon::parse($hasta)->endOfDay() : Carbon::today()->endOfDay();
        $desde = $desde ? Carbon::parse($desde)->startOfDay() : $hasta->copy()->subDays(29)->startOfDay();

        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        $parcelas = Parcela::withTrashed()->where('user_id', $userId)
            ->when($parcelaId, fn ($q) => $q->where('id', $parcelaId))
            ->with(['cultivo', 'ubicacion'])->orderBy('nombre')->get();

        return compact('desde', 'hasta', 'parcelas');
    }

    public function generar(string $tipo, array $a): array
    {
        return match ($tipo) {
            'agua' => $this->agua($a),
            'alertas' => $this->alertas($a),
            'clima' => $this->clima($a),
            'ciclo' => $this->ciclo($a),
            default => $this->general($a),
        };
    }

    // =========================================================

    public function agua(array $a): array
    {
        $ids = $a['parcelas']->pluck('id');
        $eventos = EventoRiego::whereIn('parcela_id', $ids)->whereBetween('fecha_riego', [$a['desde'], $a['hasta']])->orderBy('fecha_riego')->get();
        $porParcela = $a['parcelas']->map(function (Parcela $p) use ($eventos) {
            $e = $eventos->where('parcela_id', $p->id);
            $litros = (float) $e->sum('cantidad_agua');

            return [
                'parcela' => $p->nombre, 'cultivo' => $p->cultivo?->nombreCultivo ?? '-', 'area' => (float) $p->area, 'riegos' => $e->count(),
                'litros' => $litros, 'litros_m2' => $p->area > 0 ? round($litros / $p->area, 1) : 0,
                'promedio' => $e->count() ? round($litros / $e->count()) : 0,
            ];
        })->filter(fn ($f) => $f['riegos'] > 0 || $a['parcelas']->count() === 1)->values();

        $dias = [];
        for ($f = $a['desde']->copy(); $f->lte($a['hasta']); $f->addDay()) {
            $dias[$f->format('Y-m-d')] = 0;
        }
        foreach ($eventos as $e) {
            $k = $e->fecha_riego->format('Y-m-d');
            if (isset($dias[$k])) {
                $dias[$k] += (float) $e->cantidad_agua;
            }
        }

        return [
            'tipo' => 'agua',
            'totales' => [
                'litros' => (float) $eventos->sum('cantidad_agua'), 'riegos' => $eventos->count(),
                'promedio' => $eventos->count() ? round($eventos->avg('cantidad_agua')) : 0,
                'parcelas' => $porParcela->count(),
            ],
            'por_parcela' => $porParcela,
            'serie' => ['labels' => array_map(fn ($k) => Carbon::parse($k)->format('d/m'), array_keys($dias)), 'datos' => array_map('round', array_values($dias))],
            'detalle' => $eventos->map(fn ($e) => [
                'fecha' => $e->fecha_riego->format('d/m/Y H:i'), 'parcela' => $a['parcelas']->firstWhere('id', $e->parcela_id)?->nombre,
                'litros' => (float) $e->cantidad_agua, 'tipo' => ucfirst($e->tipo_riego), 'notas' => $e->notas ?? '',
            ])->reverse()->values(),
        ];
    }

    public function alertas(array $a): array
    {
        $alertas = Alerta::whereIn('parcela_id', $a['parcelas']->pluck('id'))->whereBetween('created_at', [$a['desde'], $a['hasta']])->orderByDesc('created_at')->get();

        $porNivel = collect(['critico', 'alto', 'medio', 'info'])->map(fn ($n) => ['nivel' => $n, 'total' => $alertas->where('nivel', $n)->count()])->all();
        $porGrupo = $alertas->groupBy('grupo')->map(fn ($g, $k) => ['grupo' => $k, 'total' => $g->count()])->values()->all();

        return [
            'tipo' => 'alertas',
            'totales' => ['total' => $alertas->count(), 'criticas' => $alertas->where('nivel', 'critico')->count(), 'activas' => $alertas->where('archivada', false)->count(), 'archivadas' => $alertas->where('archivada', true)->count()],
            'por_nivel' => $porNivel,
            'por_grupo' => $porGrupo,
            'detalle' => $alertas->map(fn ($x) => [
                'fecha' => $x->created_at->format('d/m/Y H:i'), 'parcela' => $a['parcelas']->firstWhere('id', $x->parcela_id)?->nombre, 'nivel' => $x->nivel,
                'titulo' => $x->titulo, 'mensaje' => $x->mensaje, 'estado' => $x->archivada ? 'Archivada' : 'Activa',
                'cierre' => $x->archivada ? match ($x->archivada_por) { 'usuario' => 'Por el usuario', 'riego' => 'Al registrar un riego', default => 'Ya no aplicaba' } : '',
            ])->values(),
            'por_dia' => $this->contarPorDia($alertas, $a),
        ];
    }

    public function clima(array $a): array
    {
        $registros = ClimaHistorial::whereIn('parcela_id', $a['parcelas']->pluck('id'))->whereBetween('fecha_registro', [$a['desde'], $a['hasta']])->orderBy('fecha_registro')->get();

        $porParcela = $a['parcelas']->map(function (Parcela $p) use ($registros) {
            $r = $registros->where('parcela_id', $p->id);
            if ($r->isEmpty()) {
                return null;
            }

            return [
                'parcela' => $p->nombre, 'registros' => $r->count(), 'temp_media' => round($r->avg('temperatura'), 1), 'temp_min' => round($r->min('temperatura'), 1),
                'temp_max' => round($r->max('temperatura'), 1), 'humedad_media' => round($r->avg('humedad')), 'lluvia_max_72h' => round($r->max('lluvia_72h_mm'), 1),
                'et0_media' => round($r->avg('et0_mm'), 1), 'horas_helada' => $r->filter(fn ($x) => $x->temp_min_suelo !== null && $x->temp_min_suelo <= 0)->count(),
            ];
        })->filter()->values();

        $porDia = $registros->groupBy(fn ($r) => $r->fecha_registro->format('Y-m-d'))->map(fn ($g) => [
            'max' => round($g->max('temperatura'), 1), 'min' => round($g->min('temperatura'), 1), 'media' => round($g->avg('temperatura'), 1),
        ]);

        return [
            'tipo' => 'clima',
            'por_parcela' => $porParcela,
            'totales' => ['registros' => $registros->count(), 'temp_media' => $registros->count() ? round($registros->avg('temperatura'), 1) : null, 'dias' => $porDia->count()],
            'serie' => [
                'labels' => $porDia->keys()->map(fn ($k) => Carbon::parse($k)->format('d/m'))->all(),
                'max' => $porDia->pluck('max')->all(), 'min' => $porDia->pluck('min')->all(), 'media' => $porDia->pluck('media')->all(),
            ],
            'detalle' => $porDia->map(fn ($v, $k) => ['fecha' => Carbon::parse($k)->format('d/m/Y')] + $v)->values(),
        ];
    }

    public function ciclo(array $a): array
    {
        $filas = $a['parcelas']->map(function (Parcela $p) use ($a) {
            $ciclo = (int) ($p->cultivo?->cicloProductivo ?? 0);
            $dias = $p->fecha_siembra ? (int) Carbon::parse($p->fecha_siembra)->diffInDays(Carbon::today()) : null;
            $agua = (float) EventoRiego::where('parcela_id', $p->id)->sum('cantidad_agua');

            return [
                'parcela' => $p->nombre, 'cultivo' => $p->cultivo?->nombreCultivo ?? '-', 'area' => (float) $p->area, 'estado' => $p->deleted_at ? 'En papelera' : ucfirst($p->estado),
                'siembra' => $p->fecha_siembra?->format('d/m/Y') ?? '-', 'dias' => $dias, 'ciclo' => $ciclo ?: null,
                'avance' => ($dias !== null && $ciclo) ? min(100, (int) round($dias / $ciclo * 100)) : null,
                'ultimo_riego' => $p->ultimo_riego?->format('d/m/Y') ?? '-', 'agua_total' => $agua, 'agua_m2' => $p->area > 0 ? round($agua / $p->area, 1) : 0,
            ];
        });

        return ['tipo' => 'ciclo', 'filas' => $filas, 'totales' => ['parcelas' => $filas->count(), 'area' => $filas->sum('area'), 'agua' => $filas->sum('agua_total')]];
    }

    public function general(array $a): array
    {
        $agua = $this->agua($a);
        $alertas = $this->alertas($a);
        $clima = $this->clima($a);
        $ciclo = $this->ciclo($a);
        $recs = Recomendacion::whereIn('parcela_id', $a['parcelas']->pluck('id'))->whereBetween('created_at', [$a['desde'], $a['hasta']])->get();

        return [
            'tipo' => 'general', 'agua' => $agua, 'alertas' => $alertas, 'clima' => $clima, 'ciclo' => $ciclo,
            'recomendaciones' => ['total' => $recs->count(), 'aplicadas' => $recs->where('aplicada', true)->count(), 'descartadas' => $recs->where('descartada', true)->count()],
        ];
    }

    // =========================================================
    // CSV
    // =========================================================

    /** @return array{0: array<int,string>, 1: array<int, array>} encabezados y filas */
    public function filasCsv(string $tipo, array $reporte): array
    {
        return match ($tipo) {
            'agua' => [['Fecha', 'Parcela', 'Litros', 'Tipo de riego', 'Notas'], $reporte['detalle']->map(fn ($f) => [$f['fecha'], $f['parcela'], $f['litros'], $f['tipo'], $f['notas']])->all()],
            'alertas' => [['Fecha', 'Parcela', 'Nivel', 'Titulo', 'Mensaje', 'Estado', 'Cierre'], $reporte['detalle']->map(fn ($f) => [$f['fecha'], $f['parcela'], $f['nivel'], $f['titulo'], $f['mensaje'], $f['estado'], $f['cierre']])->all()],
            'clima' => [['Fecha', 'Temp. maxima', 'Temp. minima', 'Temp. media'], $reporte['detalle']->map(fn ($f) => [$f['fecha'], $f['max'], $f['min'], $f['media']])->all()],
            'ciclo' => [['Parcela', 'Cultivo', 'Area m2', 'Estado', 'Siembra', 'Dias', 'Ciclo', 'Avance %', 'Ultimo riego', 'Agua total L', 'Agua L/m2'], $reporte['filas']->map(fn ($f) => [$f['parcela'], $f['cultivo'], $f['area'], $f['estado'], $f['siembra'], $f['dias'], $f['ciclo'], $f['avance'], $f['ultimo_riego'], $f['agua_total'], $f['agua_m2']])->all()],
            default => [['Parcela', 'Cultivo', 'Riegos', 'Litros', 'Litros por m2'], $reporte['agua']['por_parcela']->map(fn ($f) => [$f['parcela'], $f['cultivo'], $f['riegos'], $f['litros'], $f['litros_m2']])->all()],
        };
    }

    private function contarPorDia($alertas, array $a): array
    {
        $dias = [];
        for ($f = $a['desde']->copy(); $f->lte($a['hasta']); $f->addDay()) {
            $dias[$f->format('Y-m-d')] = 0;
        }
        foreach ($alertas as $x) {
            $k = $x->created_at->format('Y-m-d');
            if (isset($dias[$k])) {
                $dias[$k]++;
            }
        }

        return ['labels' => array_map(fn ($k) => Carbon::parse($k)->format('d/m'), array_keys($dias)), 'datos' => array_values($dias)];
    }
}
