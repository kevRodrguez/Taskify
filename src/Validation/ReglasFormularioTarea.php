<?php

declare(strict_types=1);

namespace App\Validation;

use App\Factories\TareaFactory;
use App\Tareas\Periodicidad;
use App\Tareas\TareaBase;
use App\Tareas\TareaRecurrente;
use App\Tareas\TareaSimple;

/**
 * Reglas del caso C aplicadas al formulario de tareas.
 *
 * [VALIDACION] Centraliza validación de servidor para título, fechas, avance,
 * periodicidad y tipo antes de delegar en el dominio (TareaFactory / TareaBase).
 */
final class ReglasFormularioTarea
{
    /**
     * @param array<string, mixed> $datos
     */
    public static function validar(array $datos): Validador
    {
        $validador = new Validador();
        $tipo = is_string($datos['tipo'] ?? null) ? trim($datos['tipo']) : '';

        $validador
            ->requerido('titulo', $datos['titulo'] ?? null)
            ->longitudMaxima('titulo', is_string($datos['titulo'] ?? null) ? $datos['titulo'] : '', TareaBase::TITULO_MAX)
            ->requerido('tipo', $datos['tipo'] ?? null)
            ->enLista('tipo', $tipo, array_keys(TareaFactory::tipos()))
            ->requerido('fecha_vencimiento', $datos['fecha_vencimiento'] ?? null)
            ->fechaValida('fecha_vencimiento', $datos['fecha_vencimiento'] ?? null);

        $fechaCreacion = $datos['fecha_creacion'] ?? date('Y-m-d');
        $validador->fechaNoAnterior('fecha_vencimiento', $datos['fecha_vencimiento'] ?? null, $fechaCreacion);

        if ($tipo === TareaSimple::TIPO || $tipo === TareaRecurrente::TIPO) {
            $validador
                ->requerido('avance', $datos['avance'] ?? null)
                ->rangoNumerico('avance', $datos['avance'] ?? null, 0, 100);
        }

        if ($tipo === TareaRecurrente::TIPO) {
            $periodicidades = array_map(
                static fn (Periodicidad $p): string => $p->value,
                Periodicidad::cases()
            );
            $validador
                ->requerido('periodicidad', $datos['periodicidad'] ?? null)
                ->enLista('periodicidad', $datos['periodicidad'] ?? null, $periodicidades);
        }

        return $validador;
    }
}
