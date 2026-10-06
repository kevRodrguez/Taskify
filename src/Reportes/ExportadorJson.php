<?php

declare(strict_types=1);

namespace App\Reportes;

use App\Tablero\Tablero;
use RuntimeException;

/**
 * Exporta e importa el contenido de un Tablero en formato JSON.
 *
 * No usa instanceof: cada tarea sabe representarse mediante su propio
 * TareaInterface::toArray(), así que esta clase solo recorre y serializa.
 */
final class ExportadorJson
{
    /**
     * Recorre las tareas del tablero y escribe su representación JSON en disco.
     *
     * @throws RuntimeException Si el JSON no se puede generar o el archivo no se puede escribir.
     */
    public function exportar(Tablero $tablero, string $rutaArchivo): void
    {
        $datos = array_map(
            static fn ($tarea): array => $tarea->toArray(),
            $tablero->obtenerTareas()
        );

        $json = json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException('No se pudo codificar el tablero a JSON: ' . json_last_error_msg());
        }

        if (file_put_contents($rutaArchivo, $json) === false) {
            throw new RuntimeException("No se pudo escribir el archivo: {$rutaArchivo}");
        }
    }

    /**
     * Lee un archivo JSON de tablero y devuelve los datos crudos (sin reconstruir tareas).
     *
     * Pensado para el entregable final, cuando se defina cómo reconstruir
     * TareaSimple/TareaCompuesta/TareaRecurrente a partir de estos datos.
     *
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException Si el archivo no existe, no se puede leer o no contiene JSON válido.
     */
    public function importar(string $rutaArchivo): array
    {
        if (!is_file($rutaArchivo)) {
            throw new RuntimeException("El archivo no existe: {$rutaArchivo}");
        }

        $contenido = file_get_contents($rutaArchivo);

        if ($contenido === false) {
            throw new RuntimeException("No se pudo leer el archivo: {$rutaArchivo}");
        }

        $datos = json_decode($contenido, true);

        if (!is_array($datos)) {
            throw new RuntimeException("El archivo no contiene un JSON válido: {$rutaArchivo}");
        }

        return $datos;
    }
}
