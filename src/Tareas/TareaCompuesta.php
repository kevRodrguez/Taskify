<?php

declare(strict_types=1);

namespace App\Tareas;

use App\Contratos\TareaInterface;
use App\Estado\EstadoTarea;
use DateTimeImmutable;

final class TareaCompuesta extends TareaBase
{
    /** @var TareaInterface[] */
    private array $subtareas = [];

    public function __construct(string $titulo, string $descripcion, DateTimeImmutable $fechaVencimiento)
    {
        parent::__construct($titulo, $descripcion, $fechaVencimiento);
    }

    public function agregarSubtarea(TareaInterface $subtarea): void
    {
        $this->subtareas[] = $subtarea;
    }

    public function calcularAvance(): float
    {
        if ($this->subtareas === []) {
            return 0.0;
        }

        $sumaAvances = 0.0;
        foreach ($this->subtareas as $subtarea) {
            $sumaAvances += $subtarea->calcularAvance();
        }

        return $sumaAvances / count($this->subtareas);
    }

    public function obtenerEstado(): EstadoTarea
    {
        return $this->determinarEstadoPorAvance($this->calcularAvance());
    }
}
