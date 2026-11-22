<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\Parcela;
use App\Models\Recomendacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Recomendaciones por parcela.
 *
 * Se construyen a partir de las MISMAS alertas activas y del MISMO calculo de
 * agua (RiegoService), asi una recomendacion nunca contradice a una alerta ni
 * muestra otra cantidad de litros. Cada tipo de recomendacion tiene una clave
 * estable (campo `fuente`): se actualiza en lugar de duplicarse y, cuando ya
 * no aplica, se retira sola.
 */
class RecomendacionService
{
    /** Horas que se respeta una recomendacion aplicada o descartada. */
    private const ENFRIAMIENTO_HORAS = 12;

    public function __construct(private RiegoService $riego)
    {
    }

    /**
     * Genera o actualiza las recomendaciones de una parcela.
     *
     * @return int recomendaciones nuevas creadas
     */
    public function generarRecomendacionesParcela(Parcela $parcela): int
    {
        try {
            $parcela->load(['cultivo', 'ubicacion', 'climaActual']);

            if (!$parcela->cultivo) {
                return 0;
            }

            $alertas = Alerta::where('parcela_id', $parcela->id)
                ->activas()
                ->get()
                ->keyBy('grupo');

            $candidatas = array_filter([
                $this->recomendacionRiego($parcela, $alertas),
                $this->recomendacionHelada($parcela, $alertas),
                $this->recomendacionCalor($parcela, $alertas),
                $this->recomendacionLluvia($parcela, $alertas),
                $this->recomendacionHorario($parcela, $alertas),
                $this->recomendacionCosecha($parcela),
                $this->recomendacionHidrogel($parcela),
                $this->recomendacionFertilizacion($parcela),
            ]);

            $creadas = 0;
            $vigentes = [];

            foreach ($candidatas as $c) {
                $vigentes[] = $c['fuente'];
                $creadas += $this->guardar($parcela, $c) ? 1 : 0;
            }

            // Lo que ya no aplica se retira (sin tocar lo que el usuario ya cerro).
            Recomendacion::where('parcela_id', $parcela->id)
                ->where('tipo_generacion', 'automatica')
                ->where('activa', true)
                ->where('aplicada', false)
                ->where('descartada', false)
                ->whereNotIn('fuente', $vigentes ?: ['-'])
                ->update(['activa' => false]);

            return $creadas;
        } catch (\Throwable $e) {
            Log::error("Error generando recomendaciones de la parcela {$parcela->id}: " . $e->getMessage());

            return 0;
        }
    }

    // =========================================================
    // RECOMENDACIONES
    // =========================================================

    private function recomendacionRiego(Parcela $parcela, $alertas): ?array
    {
        /** @var Alerta|null $alerta */
        $alerta = $alertas->get(Alerta::GRUPO_RIEGO);
        $accion = [
            'accion_texto' => 'Registrar riego',
            'accion_url' => route('parcelas.show', $parcela),
        ];

        if ($alerta) {
            return $this->base($parcela, 'riego_estado', Recomendacion::CATEGORIA_RIEGO, [
                'prioridad' => $alerta->prioridad === 'baja' ? Recomendacion::PRIORIDAD_MEDIA : $alerta->prioridad,
                'titulo' => $alerta->titulo,
                'descripcion' => $alerta->mensaje,
                'icono' => 'agua',
                'puntaje_relevancia' => max(60, $alerta->puntaje_urgencia),
                'datos_analisis' => $alerta->datos_adicionales,
            ] + $accion);
        }

        // Sin alerta: plan de riego informativo
        $frecuencia = $this->riego->frecuenciaDias($parcela, $parcela->climaActual);
        $proximo = $parcela->ultimo_riego
            ? Carbon::parse($parcela->ultimo_riego)->addDays($frecuencia)
            : null;
        $calculo = $this->riego->calcular($parcela, $parcela->climaActual);
        $litros = $calculo['litros_total'] ?: $this->riego->litrosPorRiego($parcela, $parcela->climaActual);

        $faltan = $proximo ? (int) round(Carbon::today()->diffInDays($proximo->copy()->startOfDay(), false)) : null;
        $cuando = match (true) {
            $proximo === null => 'registra tu ultimo riego para calcularlo',
            $faltan <= 0 => 'esta por tocar',
            default => 'el ' . $proximo->format('d/m') . ' (en ' . $faltan . ($faltan === 1 ? ' dia)' : ' dias)'),
        };

        return $this->base($parcela, 'riego_estado', Recomendacion::CATEGORIA_RIEGO, [
            'prioridad' => Recomendacion::PRIORIDAD_INFO,
            'titulo' => 'Plan de riego',
            'descripcion' => "Por ahora no hace falta regar {$parcela->nombre}. Proximo riego: {$cuando}. " .
                'Cantidad habitual: ' . number_format($litros, 0, ',', '.') . ' L ' .
                '(unos ' . $this->riego->aBaldes($litros) . ' baldes de ' . RiegoService::LITROS_BALDE . ' L), ' .
                "cada {$frecuencia} dias.",
            'icono' => 'agua',
            'puntaje_relevancia' => 40,
            'datos_analisis' => ['frecuencia_dias' => $frecuencia, 'litros' => $litros],
        ] + $accion);
    }

    private function recomendacionHelada(Parcela $parcela, $alertas): ?array
    {
        /** @var Alerta|null $alerta */
        $alerta = $alertas->get(Alerta::GRUPO_HELADA);

        if (!$alerta) {
            return null;
        }

        return $this->base($parcela, 'helada', Recomendacion::CATEGORIA_CLIMA, [
            'prioridad' => $alerta->prioridad,
            'titulo' => 'Proteger el cultivo del frio',
            'descripcion' => $alerta->mensaje . ' Que hacer: ' .
                '1) Antes de que oscurezca, cubre las plantas con paja, plastico, mantas o malla. ' .
                '2) Un riego ligero a media tarde ayuda a que el suelo guarde calor. ' .
                '3) Si puedes, haz humo o fogatas pequenas en el borde, a favor del viento. ' .
                '4) Al amanecer, no destapes hasta que el sol caliente el cultivo.',
            'icono' => 'helada',
            'puntaje_relevancia' => max(80, $alerta->puntaje_urgencia),
            'datos_analisis' => $alerta->datos_adicionales,
            'vigente_hasta' => $alerta->fecha_expiracion,
        ]);
    }

    private function recomendacionCalor(Parcela $parcela, $alertas): ?array
    {
        /** @var Alerta|null $alerta */
        $alerta = $alertas->get(Alerta::GRUPO_CALOR);

        if (!$alerta) {
            return null;
        }

        return $this->base($parcela, 'calor', Recomendacion::CATEGORIA_CLIMA, [
            'prioridad' => $alerta->prioridad,
            'titulo' => 'Proteger el cultivo del calor',
            'descripcion' => $alerta->mensaje . ' Que hacer: ' .
                '1) Riega al amanecer y al atardecer, nunca al mediodia. ' .
                '2) Cubre el suelo con paja o rastrojo para que no pierda humedad. ' .
                '3) Si tienes malla sombra (30 a 50%), colocala en las horas de mas sol.',
            'icono' => 'calor',
            'puntaje_relevancia' => max(70, $alerta->puntaje_urgencia),
            'datos_analisis' => $alerta->datos_adicionales,
            'vigente_hasta' => $alerta->fecha_expiracion,
        ]);
    }

    private function recomendacionLluvia(Parcela $parcela, $alertas): ?array
    {
        /** @var Alerta|null $alerta */
        $alerta = $alertas->get(Alerta::GRUPO_LLUVIA);

        if (!$alerta) {
            return null;
        }

        $fuerte = $alerta->nivel !== Alerta::NIVEL_INFO;

        return $this->base($parcela, 'lluvia', Recomendacion::CATEGORIA_CLIMA, [
            'prioridad' => $alerta->prioridad,
            'titulo' => $fuerte ? 'Prepararse para lluvia fuerte' : 'Aprovechar la lluvia',
            'descripcion' => $alerta->mensaje . ($fuerte
                ? ' Que hacer: limpia acequias y canales, evita pisar el suelo mojado y revisa que el agua no se acumule.'
                : ' Que hacer: deja el riego para despues de la lluvia y mide cuanto cayo antes de volver a regar.'),
            'icono' => 'lluvia',
            'puntaje_relevancia' => $fuerte ? 75 : 55,
            'datos_analisis' => $alerta->datos_adicionales,
            'vigente_hasta' => $alerta->fecha_expiracion,
        ]);
    }

    /**
     * Consejo de horario cuando hace calor o hay viento fuerte.
     */
    private function recomendacionHorario(Parcela $parcela, $alertas): ?array
    {
        $clima = $parcela->climaActual;

        if (!$clima || $alertas->has(Alerta::GRUPO_LLUVIA)) {
            return null;
        }

        $tmax = $clima->temp_max_prevista ?? $clima->temp_maxima;
        $calor = $tmax !== null && (float) $tmax >= 28;
        $viento = (float) $clima->viento_velocidad >= 6;

        if (!$calor && !$viento) {
            return null;
        }

        $motivo = $calor && $viento
            ? 'Hace calor y hay viento'
            : ($calor ? 'Hace calor' : 'Hay viento fuerte');

        return $this->base($parcela, 'horario_riego', Recomendacion::CATEGORIA_RIEGO, [
            'prioridad' => Recomendacion::PRIORIDAD_BAJA,
            'titulo' => 'Mejor hora para regar',
            'descripcion' => "{$motivo}: gran parte del agua se pierde por evaporacion. " .
                'Riega entre las 5:00 y las 9:00 o despues de las 17:00, y evita regar por aspersion cuando el viento sea fuerte.',
            'icono' => 'agua',
            'puntaje_relevancia' => 45,
            'datos_analisis' => ['temp_max' => $tmax, 'viento' => $clima->viento_velocidad],
            'vigente_hasta' => now()->addHours(18),
        ]);
    }

    private function recomendacionCosecha(Parcela $parcela): ?array
    {
        $ciclo = (int) $parcela->cultivo->cicloProductivo;

        if ($ciclo <= 0 || !$parcela->fecha_siembra) {
            return null;
        }

        $dias = (int) Carbon::parse($parcela->fecha_siembra)->startOfDay()->diffInDays(Carbon::today());
        $progreso = $dias / $ciclo * 100;

        if ($progreso < 85) {
            return null;
        }

        $listo = $progreso >= 100;
        $faltan = max(0, $ciclo - $dias);

        return $this->base($parcela, 'cosecha', Recomendacion::CATEGORIA_COSECHA, [
            'prioridad' => $listo ? Recomendacion::PRIORIDAD_ALTA : Recomendacion::PRIORIDAD_MEDIA,
            'titulo' => $listo ? 'Programar la cosecha' : 'Preparar la cosecha',
            'descripcion' => $listo
                ? "{$parcela->cultivo->nombreCultivo} ya cumplio su ciclo ({$dias} dias). Revisa la madurez y programa la cosecha."
                : "{$parcela->cultivo->nombreCultivo} esta al " . round($progreso) . "% de su ciclo; faltan unos {$faltan} dias. " .
                    'Reduce poco a poco el riego, no abones en esta etapa y revisa los signos de madurez.',
            'icono' => 'ciclo',
            'puntaje_relevancia' => $listo ? 80 : 65,
            'datos_analisis' => ['dias_cultivo' => $dias, 'ciclo_dias' => $ciclo],
            'accion_texto' => 'Ver parcela',
            'accion_url' => route('parcelas.show', $parcela),
        ]);
    }

    private function recomendacionHidrogel(Parcela $parcela): ?array
    {
        if ($parcela->tieneHidrogelAplicado) {
            return null;
        }

        $reduccion = (float) $parcela->cultivo->reduccionConHidrogel;

        if ($reduccion <= 0) {
            return null;
        }

        $reduccion = min(40, $reduccion);

        return $this->base($parcela, 'hidrogel', Recomendacion::CATEGORIA_HIDROGEL, [
            'prioridad' => Recomendacion::PRIORIDAD_BAJA,
            'titulo' => 'Considera usar hidrogel',
            'descripcion' => "En {$parcela->cultivo->nombreCultivo} el hidrogel puede reducir el agua que necesitas en cerca de " .
                round($reduccion) . '% y espaciar los riegos. Es util sobre todo donde el agua escasea. ' .
                'Dosis de referencia: 2 a 5 kg por cada 100 m2, mezclado con la tierra de la zona de raices.',
            'icono' => 'hidrogel',
            'puntaje_relevancia' => 35,
            'datos_analisis' => ['reduccion_pct' => $reduccion, 'area' => $parcela->area],
        ]);
    }

    private function recomendacionFertilizacion(Parcela $parcela): ?array
    {
        if (!$parcela->fecha_siembra) {
            return null;
        }

        $dias = (int) Carbon::parse($parcela->fecha_siembra)->startOfDay()->diffInDays(Carbon::today());

        if ($dias < 15 || $dias > 45) {
            return null;
        }

        return $this->base($parcela, 'fertilizacion', Recomendacion::CATEGORIA_FERTILIZACION, [
            'prioridad' => Recomendacion::PRIORIDAD_MEDIA,
            'titulo' => 'Abonar en etapa de crecimiento',
            'descripcion' => "{$parcela->cultivo->nombreCultivo} esta en crecimiento (dia {$dias}). " .
                'Es buen momento para un abono rico en nitrogeno (por ejemplo NPK 20-10-10 o guano de isla / compost bien maduro), repetido cada 15 a 20 dias. ' .
                'Aplicalo con el suelo humedo, no seco.',
            'icono' => 'parcela',
            'puntaje_relevancia' => 55,
            'datos_analisis' => ['dias_desde_siembra' => $dias],
            'vigente_hasta' => Carbon::parse($parcela->fecha_siembra)->addDays(45),
        ]);
    }

    // =========================================================
    // PERSISTENCIA
    // =========================================================

    private function base(Parcela $parcela, string $fuente, string $categoria, array $datos): array
    {
        // La vista muestra cada dato como un numero o texto: solo valores simples.
        $datos['datos_analisis'] = collect($datos['datos_analisis'] ?? [])
            ->filter(fn ($v) => is_scalar($v))
            ->all();

        return $datos + [
            'parcela_id' => $parcela->id,
            'user_id' => $parcela->user_id,
            'categoria' => $categoria,
            'fuente' => $fuente,
            'icono' => 'consejo',
            'puntaje_relevancia' => 50,
            'vigente_hasta' => null,
            'activa' => true,
            'tipo_generacion' => 'automatica',
        ];
    }

    /**
     * Crea o actualiza segun la clave `fuente`.
     *
     * @return bool true si se creo una recomendacion nueva
     */
    private function guardar(Parcela $parcela, array $c): bool
    {
        $existente = Recomendacion::where('parcela_id', $parcela->id)
            ->where('fuente', $c['fuente'])
            ->where('tipo_generacion', 'automatica')
            ->where('aplicada', false)
            ->where('descartada', false)
            ->latest('id')
            ->first();

        if ($existente) {
            $existente->update($c + ['activa' => true]);

            return false;
        }

        // Si el usuario la cerro hace poco no se vuelve a ofrecer, salvo que sea critica.
        $cerrada = Recomendacion::where('parcela_id', $parcela->id)
            ->where('fuente', $c['fuente'])
            ->where(function ($q) {
                $q->where('aplicada_en', '>', now()->subHours(self::ENFRIAMIENTO_HORAS))
                  ->orWhere('descartada_en', '>', now()->subHours(self::ENFRIAMIENTO_HORAS));
            })
            ->exists();

        if ($cerrada && ($c['prioridad'] ?? '') !== Recomendacion::PRIORIDAD_CRITICA) {
            return false;
        }

        Recomendacion::create($c);

        return true;
    }
}
