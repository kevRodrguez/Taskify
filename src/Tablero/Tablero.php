<?php

declare(strict_types=1);

namespace App\Tablero;

use App\Contratos\TareaInterface;
use App\Estado\EstadoTarea;

final class Tablero
{
    /** @var TareaInterface[] */
    private array $tareas = [];

    public function agregarTarea(TareaInterface $tarea): void
    {
        $this->tareas[] = $tarea;
    }

    /**
     * @return TareaInterface[]
     */
    public function obtenerTareas(): array
    {
        return $this->tareas;
    }

    /**
     * Agrupa las tareas por su estado actual usando el contrato polimórfico.
     *
     * @return array<string, TareaInterface[]>
     */
    public function agruparPorEstado(): array
    {
        $agrupadas = [];

        foreach ($this->tareas as $tarea) {
            $estado = $tarea->obtenerEstado()->value;
            $agrupadas[$estado][] = $tarea;
        }

        return $agrupadas;
    }

    /**
     * @return array<string, TareaInterface[]>
     */
    public function agruparPorEstadoOrdenado(): array
    {
        $agrupadas = $this->agruparPorEstado();
        $ordenadas = [];

        foreach (EstadoTarea::cases() as $estado) {
            if (isset($agrupadas[$estado->value])) {
                $ordenadas[$estado->value] = $agrupadas[$estado->value];
            }
        }

        return $ordenadas;
    }
}
