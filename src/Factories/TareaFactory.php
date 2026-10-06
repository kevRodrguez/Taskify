<?php

declare(strict_types=1);

namespace App\Factories;

use App\Contratos\TareaInterface;
use App\Exceptions\DominioException;
use App\Tareas\TareaCompuesta;
use App\Tareas\TareaRecurrente;
use App\Tareas\TareaSimple;

/**
 * [FABRICA] Único lugar del sistema que conoce la correspondencia
 * tipo (columna `tipo`) → clase concreta.
 *
 * Repositorios y páginas piden tareas a la fábrica y las manejan solo por
 * TareaInterface. Agregar un tipo nuevo implica tocar únicamente esta clase
 * (más la propia clase de tarea).
 */
final class TareaFactory
{
    /** @var array<string, class-string<TareaInterface>> */
    private const CLASES = [
        'simple' => TareaSimple::class,
        'compuesta' => TareaCompuesta::class,
        'recurrente' => TareaRecurrente::class,
    ];

    /**
     * Tipos disponibles con su nombre legible, para selectores y enlaces.
     *
     * @return array<string, string> clave de tipo => etiqueta
     */
    public static function tipos(): array
    {
        return array_map(
            static fn (string $clase): string => $clase::etiquetaTipo(),
            self::CLASES
        );
    }

    public static function existe(string $tipo): bool
    {
        return isset(self::CLASES[$tipo]);
    }

    /**
     * Campos de formulario propios del tipo indicado (ver camposEspecificos()).
     *
     * @return list<array<string, mixed>>
     * @throws DominioException Si el tipo no existe.
     */
    public static function camposDe(string $tipo): array
    {
        return self::clase($tipo)::camposEspecificos();
    }

    /**
     * Reconstruye una tarea desde una fila de la tabla `tareas` (incluye id e imagen).
     *
     * @param array<string, mixed> $fila
     * @throws DominioException Si el tipo es desconocido o la fila viola el dominio.
     */
    public static function desdeFila(array $fila): TareaInterface
    {
        return self::clase((string) ($fila['tipo'] ?? ''))::desdeArray($fila);
    }

    /**
     * Crea una tarea a partir de datos de formulario.
     *
     * Ignora `id` e `imagen`: el usuario no decide el identificador ni el
     * nombre de archivo (eso lo asigna el sistema). Para editar, la página
     * puede añadir `fecha_creacion` desde el registro original.
     *
     * @param array<string, mixed> $datos
     * @throws DominioException Si el tipo es desconocido o los datos violan el dominio.
     */
    public static function desdeFormulario(array $datos): TareaInterface
    {
        unset($datos['id'], $datos['imagen']);

        return self::clase((string) ($datos['tipo'] ?? ''))::desdeArray($datos);
    }

    /**
     * @return class-string<TareaInterface>
     * @throws DominioException Si el tipo no existe.
     */
    private static function clase(string $tipo): string
    {
        return self::CLASES[$tipo] ?? throw new DominioException('El tipo de tarea indicado no es válido.');
    }
}
