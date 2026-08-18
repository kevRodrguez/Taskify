<?php

declare(strict_types=1);

namespace App;

use App\Estado\EstadoTarea;
use DateTimeImmutable;
use InvalidArgumentException;

abstract class TareaBase implements TareaInterface
{
    private string $titulo;
    private string $descripcion;
    private DateTimeImmutable $fechaVencimiento;

    public function __construct(string $titulo, string $descripcion, DateTimeImmutable $fechaVencimiento)
    {
        $this->setTitulo($titulo);
        $this->descripcion = $descripcion;
        $this->fechaVencimiento = $fechaVencimiento;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): void
    {
        if (trim($titulo) === '') {
            throw new InvalidArgumentException('El título de la tarea no puede estar vacío.');
        }

        $this->titulo = $titulo;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function getFechaVencimiento(): DateTimeImmutable
    {
        return $this->fechaVencimiento;
    }

    abstract public function calcularAvance(): float;

    abstract public function obtenerEstado(): EstadoTarea;

    protected function determinarEstadoPorAvance(float $avance): EstadoTarea
    {
        return match (true) {
            $avance <= 0.0 => EstadoTarea::PENDIENTE,
            $avance >= 100.0 => EstadoTarea::COMPLETADA,
            default => EstadoTarea::EN_PROGRESO,
        };
    }
}
