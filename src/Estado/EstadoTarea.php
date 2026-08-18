<?php

declare(strict_types=1);

namespace App\Estado;

/**
 * Estados posibles de una tarea según su avance.
 *
 * - PENDIENTE: avance 0%
 * - EN_PROGRESO: avance entre 1% y 99%
 * - COMPLETADA: avance 100%
 */
enum EstadoTarea: string
{
    case PENDIENTE = 'Pendiente';
    case EN_PROGRESO = 'En progreso';
    case COMPLETADA = 'Completada';
}
