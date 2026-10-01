<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Validación del formulario de asignación de subtareas.
 *
 * [VALIDACION] Reglas de servidor antes de delegar en SubtareaRepositorio.
 */
final class ReglasFormularioSubtarea
{
    /**
     * @param array<string, mixed> $datos
     */
    public static function validar(array $datos): Validador
    {
        $validador = new Validador();

        $validador
            ->requerido('tarea_padre_id', $datos['tarea_padre_id'] ?? null)
            ->rangoNumerico('tarea_padre_id', $datos['tarea_padre_id'] ?? null, 1, PHP_INT_MAX)
            ->requerido('tarea_hija_id', $datos['tarea_hija_id'] ?? null)
            ->rangoNumerico('tarea_hija_id', $datos['tarea_hija_id'] ?? null, 1, PHP_INT_MAX);

        return $validador;
    }
}
