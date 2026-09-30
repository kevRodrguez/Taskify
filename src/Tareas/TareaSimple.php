<?php

declare(strict_types=1);

namespace App\Tareas;

use App\Estado\EstadoTarea;
use App\Exceptions\DominioException;
use DateTimeImmutable;

/**
 * Tarea con avance manual definido por el usuario (0% a 100%).
 */
final class TareaSimple extends TareaBase
{
    public const TIPO = 'simple';

    private float $avance;

    public function __construct(
        string $titulo,
        string $descripcion,
        DateTimeImmutable $fechaVencimiento,
        float $avance = 0.0,
        ?DateTimeImmutable $fechaCreacion = null
    ) {
        parent::__construct($titulo, $descripcion, $fechaVencimiento, $fechaCreacion);
        $this->setAvance($avance);
    }

    /**
     * @throws DominioException Si el avance está fuera del rango 0–100.
     */
    public function setAvance(float $avance): void
    {
        $this->avance = self::validarAvance($avance);
    }

    public function calcularAvance(): float
    {
        return $this->avance;
    }

    public function obtenerEstado(): EstadoTarea
    {
        return $this->determinarEstadoPorAvance($this->calcularAvance());
    }

    public static function etiquetaTipo(): string
    {
        return 'Simple';
    }

    /** @return array<string, mixed> */
    public function aFila(): array
    {
        return array_merge(parent::aFila(), ['avance' => (int) round($this->avance)]);
    }

    /** @param array<string, mixed> $datos */
    public static function desdeArray(array $datos): static
    {
        [$titulo, $descripcion, $vencimiento, $creacion, $id, $imagen] = self::datosComunes($datos);
        $avance = self::avanceDesdeTexto($datos['avance'] ?? 0);

        return self::completar(
            new self($titulo, $descripcion, $vencimiento, $avance, $creacion),
            $id,
            $imagen
        );
    }

    /** @return list<array<string, mixed>> */
    public static function camposEspecificos(): array
    {
        return [self::campoAvance()];
    }

    /** @return array<string, string> */
    public function detalleEspecifico(): array
    {
        return ['Avance manual' => rtrim(rtrim(number_format($this->avance, 1, '.', ''), '0'), '.') . '%'];
    }
}
