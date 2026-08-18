<?php

declare(strict_types=1);

namespace App\Tareas;

use App\Contratos\TareaInterface;
use App\Estado\EstadoTarea;
use DateTimeImmutable;

/**
 * Tarea compuesta que agrupa subtareas mediante el patrón Composite.
 *
 * Su avance es el promedio del avance de todas sus subtareas, calculado
 * polimórficamente a través de TareaInterface.
 */
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

    /**
     * Extiende la representación base agregando las subtareas, cada una
     * serializada recursivamente mediante su propio toArray() polimórfico.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return parent::toArray() + [
            'subtareas' => array_map(
                static fn (TareaInterface $subtarea): array => $subtarea->toArray(),
                $this->subtareas
            ),
        ];
    }
}
