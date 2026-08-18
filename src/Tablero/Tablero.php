<?php

declare(strict_types=1);

namespace App\Tablero;

use App\Contratos\TareaInterface;
use App\Estado\EstadoTarea;

/**
 * Tablero que agrupa tareas heterogéneas por su estado actual.
 *
 * Recorre un arreglo de TareaInterface y delega en cada tarea el cálculo
 * de su estado, sin usar instanceof ni condicionales por tipo concreto.
 */
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
     * @return array<string, TareaInterface[]> Clave = nombre del estado, valor = tareas en ese estado.
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
     * Igual que agruparPorEstado(), pero ordenado: Pendiente → En progreso → Completada.
     *
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
