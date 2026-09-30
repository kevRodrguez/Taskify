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
    public const TIPO = 'compuesta';

    /** @var TareaInterface[] */
    private array $subtareas = [];

    public function __construct(
        string $titulo,
        string $descripcion,
        DateTimeImmutable $fechaVencimiento,
        ?DateTimeImmutable $fechaCreacion = null
    ) {
        parent::__construct($titulo, $descripcion, $fechaVencimiento, $fechaCreacion);
    }

    public function agregarSubtarea(TareaInterface $subtarea): void
    {
        $this->subtareas[] = $subtarea;
    }

    /**
     * @return TareaInterface[]
     */
    public function getSubtareas(): array
    {
        return $this->subtareas;
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

    public function tipoLegible(): string
    {
        return 'Compuesta';
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

    /**
     * El avance no se persiste (avance = NULL): se deriva de las subtareas.
     *
     * @return array<string, mixed>
     */
    public function aFila(): array
    {
        return parent::aFila();
    }

    /** @param array<string, mixed> $datos */
    public static function desdeArray(array $datos): static
    {
        [$titulo, $descripcion, $vencimiento, $creacion, $id, $imagen] = self::datosComunes($datos);

        return self::completar(
            new self($titulo, $descripcion, $vencimiento, $creacion),
            $id,
            $imagen
        );
    }

    /**
     * Sin campos propios: su avance se calcula a partir de las subtareas.
     *
     * @return list<array<string, mixed>>
     */
    public static function camposEspecificos(): array
    {
        return [];
    }

    /** @return array<string, string> */
    public function detalleEspecifico(): array
    {
        return ['Subtareas' => (string) count($this->subtareas)];
    }
}
