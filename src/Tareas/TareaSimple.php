<?php

declare(strict_types=1);

namespace App\Tareas;

use App\Estado\EstadoTarea;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Tarea con avance manual definido por el usuario (0% a 100%).
 */
final class TareaSimple extends TareaBase
{
    private float $avance;

    public function __construct(
        string $titulo,
        string $descripcion,
        DateTimeImmutable $fechaVencimiento,
        float $avance = 0.0
    ) {
        parent::__construct($titulo, $descripcion, $fechaVencimiento);
        $this->setAvance($avance);
    }

    /**
     * @throws InvalidArgumentException Si el avance está fuera del rango 0–100.
     */
    public function setAvance(float $avance): void
    {
        if ($avance < 0.0 || $avance > 100.0) {
            throw new InvalidArgumentException('El avance debe estar entre 0 y 100.');
        }

        $this->avance = $avance;
    }

    public function calcularAvance(): float
    {
        return $this->avance;
    }

    public function obtenerEstado(): EstadoTarea
    {
        return $this->determinarEstadoPorAvance($this->calcularAvance());
    }
}
