<?php

declare(strict_types=1);

namespace App\Tareas;

use App\Exceptions\DominioException;

/**
 * Reglas de negocio para la relación compuesta → subtarea.
 *
 * Valida en el dominio (sin instanceof en vistas) que solo las tareas
 * compuestas acepten subtareas y que padre e hija sean distintas.
 */
final class ReglasSubtarea
{
    public static function padreAceptaSubtareas(string $tipoPadre): bool
    {
        return $tipoPadre === TareaCompuesta::TIPO;
    }

    /**
     * @throws DominioException Si el tipo de padre no admite subtareas.
     */
    public static function exigirPadreCompuesto(string $tipoPadre): void
    {
        if (!self::padreAceptaSubtareas($tipoPadre)) {
            throw new DominioException('Solo las tareas compuestas pueden tener subtareas.');
        }
    }

    /**
     * @throws DominioException Si padre e hija son la misma tarea.
     */
    public static function exigirPadreDistintoDeHija(int $padreId, int $hijaId): void
    {
        if ($padreId === $hijaId) {
            throw new DominioException('Una tarea no puede ser subtarea de sí misma.');
        }
    }
}
