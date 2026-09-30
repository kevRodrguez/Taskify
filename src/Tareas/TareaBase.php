<?php

declare(strict_types=1);

namespace App\Tareas;

use App\Contratos\TareaInterface;
use App\Estado\EstadoTarea;
use App\Exceptions\DominioException;
use DateTimeImmutable;

/**
 * Clase abstracta con los atributos y comportamiento compartido por todas las tareas.
 *
 * Encapsula título, descripción, fechas e imagen. Las subclases concretas
 * definen cómo calcular el avance y el estado, y qué datos propios persisten.
 *
 * Invariantes del dominio: título no vacío, avance entre 0 y 100 y fecha de
 * vencimiento no anterior a la de creación.
 */
abstract class TareaBase implements TareaInterface
{
    /** Longitud máxima del título (columna VARCHAR(120)). */
    public const TITULO_MAX = 120;

    private ?int $id = null;
    private string $titulo;
    private string $descripcion;
    private ?string $imagen = null;
    private readonly DateTimeImmutable $fechaCreacion;
    private readonly DateTimeImmutable $fechaVencimiento;

    /**
     * @throws DominioException Si el título está vacío o el vencimiento es anterior a la creación.
     */
    public function __construct(
        string $titulo,
        string $descripcion,
        DateTimeImmutable $fechaVencimiento,
        ?DateTimeImmutable $fechaCreacion = null
    ) {
        $creacion = ($fechaCreacion ?? new DateTimeImmutable())->setTime(0, 0);

        if ($fechaVencimiento->setTime(0, 0) < $creacion) {
            throw new DominioException('La fecha de vencimiento no puede ser anterior a la de creación.');
        }

        $this->setTitulo($titulo);
        $this->descripcion = $descripcion;
        $this->fechaCreacion = $creacion;
        $this->fechaVencimiento = $fechaVencimiento;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @throws DominioException Si el identificador no es positivo.
     */
    public function setId(int $id): void
    {
        if ($id <= 0) {
            throw new DominioException('El identificador de la tarea debe ser un entero positivo.');
        }

        $this->id = $id;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    /**
     * @throws DominioException Si el título está vacío o excede la longitud máxima.
     */
    public function setTitulo(string $titulo): void
    {
        if (trim($titulo) === '') {
            throw new DominioException('El título de la tarea no puede estar vacío.');
        }

        if (mb_strlen($titulo) > self::TITULO_MAX) {
            throw new DominioException(
                'El título no puede superar los ' . self::TITULO_MAX . ' caracteres.'
            );
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

    public function getFechaCreacion(): DateTimeImmutable
    {
        return $this->fechaCreacion;
    }

    public function getFechaVencimiento(): DateTimeImmutable
    {
        return $this->fechaVencimiento;
    }

    public function getImagen(): ?string
    {
        return $this->imagen;
    }

    /**
     * @throws DominioException Si el nombre de archivo excede la columna (64 caracteres).
     */
    public function setImagen(?string $imagen): void
    {
        if ($imagen !== null && ($imagen === '' || strlen($imagen) > 64)) {
            throw new DominioException('El nombre de la imagen no es válido.');
        }

        $this->imagen = $imagen;
    }

    abstract public function calcularAvance(): float;

    abstract public function obtenerEstado(): EstadoTarea;

    abstract public function tipoLegible(): string;

    /**
     * Fila con las columnas comunes; `avance` y `periodicidad` quedan en null y
     * cada subclase completa las que le corresponden con parent::aFila().
     *
     * @return array<string, mixed>
     */
    public function aFila(): array
    {
        return [
            'tipo' => static::TIPO,
            'titulo' => $this->getTitulo(),
            'descripcion' => $this->getDescripcion(),
            'fecha_creacion' => $this->getFechaCreacion()->format('Y-m-d'),
            'fecha_vencimiento' => $this->getFechaVencimiento()->format('Y-m-d'),
            'avance' => null,
            'periodicidad' => null,
            'imagen' => $this->getImagen(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function camposEspecificos(): array
    {
        return [];
    }

    /** @return array<string, string> */
    public function detalleEspecifico(): array
    {
        return [];
    }

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
     * Invariante compartida por las tareas con avance propio.
     *
     * @throws DominioException Si el avance está fuera del rango 0–100.
     */
    protected static function validarAvance(float $avance): float
    {
        if ($avance < 0.0 || $avance > 100.0) {
            throw new DominioException('El avance debe estar entre 0 y 100.');
        }

        return $avance;
    }

    /**
     * Convierte el avance recibido (número o texto de formulario) a float.
     *
     * @throws DominioException Si el valor no es numérico.
     */
    protected static function avanceDesdeTexto(mixed $valor): float
    {
        if (is_string($valor)) {
            $valor = trim($valor);
        }

        if (!is_numeric($valor)) {
            throw new DominioException('El avance debe ser un número entre 0 y 100.');
        }

        return (float) $valor;
    }

    /**
     * Descriptor del campo de avance, compartido por los tipos con avance manual.
     *
     * @return array<string, mixed>
     */
    protected static function campoAvance(): array
    {
        return [
            'nombre' => 'avance',
            'etiqueta' => 'Avance (%)',
            'tipo' => 'number',
            'min' => 0,
            'max' => 100,
            'step' => 1,
            'requerido' => true,
        ];
    }

    /**
     * Convierte un texto Y-m-d en fecha, rechazando formatos y fechas imposibles.
     *
     * @throws DominioException Si el valor no es una fecha válida.
     */
    protected static function fechaDesdeTexto(mixed $valor, string $campo): DateTimeImmutable
    {
        $texto = is_string($valor) ? trim($valor) : '';
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $texto);

        if ($fecha === false || $fecha->format('Y-m-d') !== $texto) {
            throw new DominioException("La {$campo} no es una fecha válida (use AAAA-MM-DD).");
        }

        return $fecha;
    }

    /**
     * Datos comunes de un arreglo (fila o formulario) ya convertidos, en el
     * orden de los parámetros del constructor de TareaBase.
     *
     * @param array<string, mixed> $datos
     * @return array{0: string, 1: string, 2: DateTimeImmutable, 3: ?DateTimeImmutable, 4: ?int, 5: ?string}
     */
    protected static function datosComunes(array $datos): array
    {
        $creacion = ($datos['fecha_creacion'] ?? '') === ''
            ? null
            : static::fechaDesdeTexto($datos['fecha_creacion'], 'fecha de creación');

        $id = ($datos['id'] ?? null) === null ? null : (int) $datos['id'];
        $imagen = ($datos['imagen'] ?? null) === '' ? null : ($datos['imagen'] ?? null);

        return [
            (string) ($datos['titulo'] ?? ''),
            (string) ($datos['descripcion'] ?? ''),
            static::fechaDesdeTexto($datos['fecha_vencimiento'] ?? null, 'fecha de vencimiento'),
            $creacion,
            $id,
            $imagen,
        ];
    }

    /** Aplica id e imagen leídos de un arreglo a una tarea recién construida. */
    protected static function completar(self $tarea, ?int $id, ?string $imagen): static
    {
        if ($id !== null) {
            $tarea->setId($id);
        }
        $tarea->setImagen($imagen);

        return $tarea;
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
