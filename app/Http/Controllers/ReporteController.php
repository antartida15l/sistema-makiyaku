<?php

namespace App\Http\Controllers;

use App\Models\Parcela;
use App\Services\ReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function __construct(private ReporteService $reportes)
    {
    }

    public function index(Request $request)
    {
        return view('reportes.index', [
            'tipos' => ReporteService::TIPOS,
            'parcelas' => Parcela::withTrashed()->where('user_id', Auth::id())->orderBy('nombre')->get(['id', 'nombre']),
            'desde' => now()->subDays(29)->toDateString(),
            'hasta' => now()->toDateString(),
        ]);
    }

    public function ver(Request $request)
    {
        [$tipo, $alcance, $reporte] = $this->preparar($request);

        return view('reportes.ver', [
            'tipo' => $tipo, 'titulo' => ReporteService::TIPOS[$tipo][0], 'reporte' => $reporte,
            'desde' => $alcance['desde'], 'hasta' => $alcance['hasta'], 'parcelas' => $alcance['parcelas'],
            'parcelaFiltro' => $request->filled('parcela') ? $alcance['parcelas']->first() : null,
            'generado' => now(),
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        [$tipo, $alcance, $reporte] = $this->preparar($request);
        [$cabecera, $filas] = $this->reportes->filasCsv($tipo, $reporte);
        $nombre = 'makiyaku-' . $tipo . '-' . $alcance['desde']->format('Ymd') . '-' . $alcance['hasta']->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($cabecera, $filas) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");           // BOM para que Excel respete las tildes
            fputcsv($f, $cabecera, ';');
            foreach ($filas as $fila) {
                fputcsv($f, $fila, ';');
            }
            fclose($f);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function preparar(Request $request): array
    {
        $v = $request->validate([
            'tipo' => ['required', 'in:' . implode(',', array_keys(ReporteService::TIPOS))],
            'parcela' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date'], 'hasta' => ['nullable', 'date'],
        ]);

        $alcance = $this->reportes->alcance(Auth::id(), $v['desde'] ?? null, $v['hasta'] ?? null, $v['parcela'] ?? null);

        return [$v['tipo'], $alcance, $this->reportes->generar($v['tipo'], $alcance)];
    }
}
