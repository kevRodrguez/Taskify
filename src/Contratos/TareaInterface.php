<?php

declare(strict_types=1);

namespace App\Contratos;

use App\Estado\EstadoTarea;
use DateTimeImmutable;

/**
 * Contrato polimórfico para todos los tipos de tarea del sistema.
 *
 * Permite que el Tablero interactúe con tareas simples, compuestas o recurrentes
 * sin conocer su implementación concreta.
 */
interface TareaInterface
{
    /** Calcula el porcentaje de avance de la tarea (0.0 a 100.0). */
    public function calcularAvance(): float;

    /** Determina el estado actual según las reglas de cada tipo de tarea. */
    public function obtenerEstado(): EstadoTarea;

    public function getTitulo(): string;

    public function getDescripcion(): string;

    public function getFechaVencimiento(): DateTimeImmutable;
}
