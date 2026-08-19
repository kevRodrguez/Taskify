<?php

declare(strict_types=1);

namespace App\Tareas;

use App\Contratos\TareaInterface;
use App\Estado\EstadoTarea;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Clase abstracta con los atributos y comportamiento compartido por todas las tareas.
 *
 * Encapsula título, descripción y fecha de vencimiento. Las subclases concretas
 * definen cómo calcular el avance y el estado.
 */
abstract class TareaBase implements TareaInterface
{
    private string $titulo;
    private string $descripcion;
    private readonly DateTimeImmutable $fechaVencimiento;

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

    /**
     * @throws InvalidArgumentException Si el título está vacío.
     */
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

    /** Traduce un porcentaje de avance al estado correspondiente. */
    protected function determinarEstadoPorAvance(float $avance): EstadoTarea
    {
        return match (true) {
            $avance <= 0.0 => EstadoTarea::PENDIENTE,
            $avance >= 100.0 => EstadoTarea::COMPLETADA,
            default => EstadoTarea::EN_PROGRESO,
        };
    }

    /**
     * Representación base común a toda tarea. Las subclases con datos propios
     * (ej. TareaCompuesta, TareaRecurrente) llaman parent::toArray() y agregan
     * sus claves adicionales en lugar de reescribir los campos comunes.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'titulo' => $this->getTitulo(),
            'descripcion' => $this->getDescripcion(),
            'fechaVencimiento' => $this->getFechaVencimiento()->format('Y-m-d'),
            'avance' => $this->calcularAvance(),
            'estado' => $this->obtenerEstado()->value,
        ];
    }
}
