<?php

declare(strict_types=1);

namespace App\Tareas;

use App\Estado\EstadoTarea;
use App\Exceptions\DominioException;
use DateTimeImmutable;

/**
 * La fecha de TareaBase es readonly (ancla de la serie).
 * La fecha vigente se guarda en $proximaFecha y se recorre al completar un ciclo.
 */
final class TareaRecurrente extends TareaBase
{
    public const TIPO = 'recurrente';

    private float $avance;
    private DateTimeImmutable $proximaFecha;

    public function __construct(
        string $titulo,
        string $descripcion,
        DateTimeImmutable $fechaVencimiento,
        private readonly Periodicidad $periodicidad,
        float $avance = 0.0,
        ?DateTimeImmutable $fechaCreacion = null
    ) {
        parent::__construct($titulo, $descripcion, $fechaVencimiento, $fechaCreacion);
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

    /**
     * @throws DominioException Si el avance está fuera del rango 0–100.
     */
    public function setAvance(float $avance): void
    {
        $this->avance = self::validarAvance($avance);
    }

    /**
     * @throws DominioException Si el ciclo aún no está completo.
     */
    public function completarCiclo(): void
    {
        if ($this->calcularAvance() < 100.0) {
            throw new DominioException(
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

    public function tipoLegible(): string
    {
        return 'Recurrente';
    }

    /**
     * Extiende la representación base agregando la periodicidad propia de esta tarea.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return parent::toArray() + [
            'periodicidad' => $this->periodicidad->value,
        ];
    }

    /**
     * Se persiste la fecha vigente (no el ancla), que es la que ve el usuario.
     *
     * @return array<string, mixed>
     */
    public function aFila(): array
    {
        return array_merge(parent::aFila(), [
            'avance' => (int) round($this->avance),
            'periodicidad' => $this->periodicidad->value,
        ]);
    }

    /** @param array<string, mixed> $datos */
    public static function desdeArray(array $datos): static
    {
        [$titulo, $descripcion, $vencimiento, $creacion, $id, $imagen] = self::datosComunes($datos);

        $periodicidad = Periodicidad::tryFrom((string) ($datos['periodicidad'] ?? ''));
        if ($periodicidad === null) {
            throw new DominioException('La periodicidad debe ser Diaria, Semanal o Mensual.');
        }

        $avance = self::avanceDesdeTexto($datos['avance'] ?? 0);

        return self::completar(
            new self($titulo, $descripcion, $vencimiento, $periodicidad, $avance, $creacion),
            $id,
            $imagen
        );
    }

    /** @return list<array<string, mixed>> */
    public static function camposEspecificos(): array
    {
        $opciones = [];
        foreach (Periodicidad::cases() as $caso) {
            $opciones[$caso->value] = $caso->value;
        }

        return [
            [
                'nombre' => 'periodicidad',
                'etiqueta' => 'Periodicidad',
                'tipo' => 'select',
                'opciones' => $opciones,
                'requerido' => true,
            ],
            self::campoAvance(),
        ];
    }

    /** @return array<string, string> */
    public function detalleEspecifico(): array
    {
        return [
            'Periodicidad' => $this->periodicidad->value,
            'Próximo vencimiento' => $this->proximaFecha->format('Y-m-d'),
        ];
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
