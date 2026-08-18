<?php

declare(strict_types=1);

namespace App\Tareas;

use App\Estado\EstadoTarea;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * La fecha de TareaBase es readonly (ancla de la serie).
 * La fecha vigente se guarda en $proximaFecha y se recorre al completar un ciclo.
 */
final class TareaRecurrente extends TareaBase
{
    private float $avance;
    private DateTimeImmutable $proximaFecha;

    public function __construct(
        string $titulo,
        string $descripcion,
        DateTimeImmutable $fechaVencimiento,
        private readonly Periodicidad $periodicidad,
        float $avance = 0.0
    ) {
        parent::__construct($titulo, $descripcion, $fechaVencimiento);
        $this->proximaFecha = $fechaVencimiento;
        $this->setAvance($avance);
    }

    public function getFechaVencimiento(): DateTimeImmutable
    {
        return $this->proximaFecha;
    }

    public function getPeriodicidad(): Periodicidad
    {
        return $this->periodicidad;
    }

    public function setAvance(float $avance): void
    {
        if ($avance < 0.0 || $avance > 100.0) {
            throw new InvalidArgumentException('El avance debe estar entre 0 y 100.');
        }

        $this->avance = $avance;
    }

    public function completarCiclo(): void
    {
        if ($this->calcularAvance() < 100.0) {
            throw new InvalidArgumentException(
                'Solo se puede avanzar la fecha cuando el ciclo está completo (avance 100).'
            );
        }

        $this->proximaFecha = $this->calcularSiguienteFecha($this->proximaFecha);
        $this->avance = 0.0;
    }

    public function calcularAvance(): float
    {
        return $this->avance;
    }

    public function obtenerEstado(): EstadoTarea
    {
        return $this->determinarEstadoPorAvance($this->calcularAvance());
    }

    private function calcularSiguienteFecha(DateTimeImmutable $fecha): DateTimeImmutable
    {
        return match ($this->periodicidad) {
            Periodicidad::DIARIA => $fecha->modify('+1 day'),
            Periodicidad::SEMANAL => $fecha->modify('+1 week'),
            Periodicidad::MENSUAL => $this->avanzarUnMesCalendario($fecha),
        };
    }

    /**
     * Avanza al mes calendario siguiente. Si el día ancla no existe (ej. 31 ene → feb),
     * recorta al último día válido en lugar de desbordar a marzo con modify('+1 month').
     */
    private function avanzarUnMesCalendario(DateTimeImmutable $fecha): DateTimeImmutable
    {
        $diaAncla = (int) parent::getFechaVencimiento()->format('j');
        $inicioSiguienteMes = $fecha->modify('first day of next month');
        $ultimoDiaSiguienteMes = (int) $inicioSiguienteMes->format('t');

        return $inicioSiguienteMes->setDate(
            (int) $inicioSiguienteMes->format('Y'),
            (int) $inicioSiguienteMes->format('n'),
            min($diaAncla, $ultimoDiaSiguienteMes)
        );
    }
}
