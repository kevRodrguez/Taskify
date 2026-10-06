<?php

declare(strict_types=1);

namespace App\Tareas;

use App\Contratos\TareaInterface;
use App\Exceptions\DominioException;

/**
 * Reglas de negocio para la relación compuesta → subtarea.
 *
 * Valida en el dominio (sin instanceof ni comparar el tipo) que solo las
 * tareas que admiten subtareas las reciban y que padre e hija sean distintas.
 */
final class ReglasSubtarea
{
    /**
     * @throws DominioException Si la tarea padre no admite subtareas.
     */
    public static function exigirPadreAdmiteSubtareas(TareaInterface $padre): void
    {
        // [POLIMORFISMO] la tarea responde por sí misma; no se compara su tipo.
        if (!$padre->admiteSubtareas()) {
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
