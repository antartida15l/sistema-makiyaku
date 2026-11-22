<?php

namespace App\Services;

use App\Models\Parcela;
use Illuminate\Support\Facades\Cache;

/**
 * Orquesta "actualizar clima y luego recalcular alertas".
 *
 * Se usa desde el scheduler (cada 30 minutos) y, para quien trabaja en local
 * sin tener el scheduler corriendo, desde el panel y la pagina de alertas con
 * un limite de una vez cada 15 minutos por usuario.
 */
class MonitoreoService
{
    public const MINUTOS_REFRESCO_EN_PAGINA = 15;

    public function __construct(
        private ClimaService $clima,
        private AlertaService $alertas,
    ) {
    }

    /**
     * Actualiza clima y alertas de una parcela.
     *
     * @return int alertas nuevas o que subieron de nivel
     */
    public function monitorearParcela(Parcela $parcela, bool $forzarClima = false, string $tipo = 'automatica'): int
    {
        $this->clima->actualizarClimaParcela($parcela, $forzarClima, $tipo);

        return count($this->alertas->generarAlertasParcela($parcela));
    }

    public function monitorearUsuario(int $userId, bool $forzarClima = false): int
    {
        $total = 0;

        Parcela::where('user_id', $userId)
            ->activas()
            ->with(['ubicacion', 'cultivo'])
            ->get()
            ->each(function (Parcela $p) use ($forzarClima, &$total) {
                $total += $this->monitorearParcela($p, $forzarClima);
            });

        return $total;
    }

    /**
     * Refresco "perezoso" para entornos sin cron: como mucho cada 15 minutos.
     */
    public function refrescarSiHaceFalta(int $userId): void
    {
        $clave = 'monitoreo:usuario:' . $userId;

        if (!Cache::add($clave, now()->timestamp, now()->addMinutes(self::MINUTOS_REFRESCO_EN_PAGINA))) {
            return;
        }

        try {
            $this->monitorearUsuario($userId);
        } catch (\Throwable $e) {
            // Nunca romper la pagina por un fallo de red.
            report($e);
        }
    }

    public function olvidarRefresco(int $userId): void
    {
        Cache::forget('monitoreo:usuario:' . $userId);
    }
}
