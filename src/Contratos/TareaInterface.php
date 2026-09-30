<?php

declare(strict_types=1);

namespace App\Contratos;

use App\Estado\EstadoTarea;
use DateTimeImmutable;

/**
 * Contrato polimórfico para todos los tipos de tarea del sistema.
 *
 * Permite que el Tablero interactúe con tareas simples, compuestas o recurrentes
 * sin conocer su implementación concreta.
 */
interface TareaInterface
{
    /** Calcula el porcentaje de avance de la tarea (0.0 a 100.0). */
    public function calcularAvance(): float;

    /** Determina el estado actual según las reglas de cada tipo de tarea. */
    public function obtenerEstado(): EstadoTarea;

    /** Identificador en base de datos; null mientras la tarea no se ha persistido. */
    public function getId(): ?int;

    public function setId(int $id): void;

    public function getTitulo(): string;

    public function getDescripcion(): string;

    public function getFechaCreacion(): DateTimeImmutable;

    public function getFechaVencimiento(): DateTimeImmutable;

    /** Nombre del archivo de imagen asociado, o null si no tiene. */
    public function getImagen(): ?string;

    public function setImagen(?string $imagen): void;

    /** Nombre del tipo para mostrar en pantalla (ej. "Recurrente"). */
    public function tipoLegible(): string;

    /**
     * Representa la tarea como un arreglo asociativo listo para serializar.
     *
     * Cada tarea concreta sabe describirse a sí misma: así ExportadorJson
     * puede recorrer un Tablero heterogéneo sin instanceof.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Fila lista para persistir, con las columnas de la tabla `tareas`
     * (tipo, titulo, descripcion, fecha_creacion, fecha_vencimiento, avance,
     * periodicidad, imagen).
     *
     * @return array<string, mixed>
     */
    public function aFila(): array;

    /**
     * Construye la tarea a partir de un arreglo con las columnas de `tareas`;
     * sirve igual para una fila de BD que para datos de formulario.
     *
     * @param array<string, mixed> $datos
     * @throws \App\Exceptions\DominioException Si los datos violan una invariante.
     */
    public static function desdeArray(array $datos): static;

    /**
     * Describe los campos de formulario propios de este tipo, para que las
     * vistas los dibujen sin condicionales por tipo. Cada elemento trae:
     * nombre, etiqueta, tipo (number|select), requerido y, según el tipo,
     * min/max/step u opciones (valor => texto).
     *
     * @return list<array<string, mixed>>
     */
    public static function camposEspecificos(): array;

    /**
     * Pares etiqueta => valor con los datos propios de este tipo para la ficha.
     *
     * @return array<string, string>
     */
    public function detalleEspecifico(): array;
}
